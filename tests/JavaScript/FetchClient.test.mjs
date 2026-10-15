import assert from "node:assert/strict";
import test from "node:test";

import FetchClient from "../../assets/js/core/http/FetchClient.js";
import { HttpError } from "../../assets/js/core/http/HttpClient.js";
import createSessionExpiryRedirect from "../../assets/js/core/auth/SessionExpiryRedirect.js";
import uploadMedia from "../../assets/js/modules/dogsDiary/repositories/MediaUpload.js";

function jsonResponse(body, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { "Content-Type": "application/json" },
    });
}

test("uses same-origin credentials and adds CSRF only to mutations", async (context) => {
    const requests = [];
    const originalFetch = globalThis.fetch;
    context.after(() => {
        globalThis.fetch = originalFetch;
    });

    globalThis.fetch = async (url, options) => {
        requests.push({ url, options });

        return jsonResponse({ ok: true });
    };

    const client = new FetchClient({ baseUrl: "/api", csrfToken: "csrf-value" });

    await client.get("/dogs");
    await client.post("/dogs", { name: "Rusty" });

    assert.equal(requests[0].url, "/api/dogs");
    assert.equal(requests[0].options.credentials, "same-origin");
    assert.equal(requests[0].options.headers["X-CSRF-TOKEN"], undefined);
    assert.equal(requests[1].options.credentials, "same-origin");
    assert.equal(requests[1].options.headers["X-CSRF-TOKEN"], "csrf-value");
    assert.equal(requests[1].options.headers["Content-Type"], "application/json");
    assert.equal(requests[1].options.body, JSON.stringify({ name: "Rusty" }));
});

test("preserves structured validation errors without treating them as an expired session", async (context) => {
    const originalFetch = globalThis.fetch;
    context.after(() => {
        globalThis.fetch = originalFetch;
    });

    globalThis.fetch = async () => jsonResponse({
        error: {
            code: "validation_failed",
            message: "The request contains invalid values.",
            violations: [{ field: "name", message: "Name is required." }],
        },
    }, 422);

    let unauthorizedCalls = 0;
    const client = new FetchClient({
        onUnauthorized: () => {
            unauthorizedCalls += 1;
        },
    });

    await assert.rejects(
        client.post("/dogs", {}),
        (error) => {
            assert.ok(error instanceof HttpError);
            assert.equal(error.status, 422);
            assert.equal(error.code, "validation_failed");
            assert.deepEqual(error.violations, [{ field: "name", message: "Name is required." }]);

            return true;
        },
    );
    assert.equal(unauthorizedCalls, 0);
});

test("notifies the shared session handler for an API 401", async (context) => {
    const originalFetch = globalThis.fetch;
    context.after(() => {
        globalThis.fetch = originalFetch;
    });

    globalThis.fetch = async () => jsonResponse({
        error: {
            code: "authentication_required",
            message: "Authentication is required.",
        },
    }, 401);

    let unauthorizedError = null;
    const client = new FetchClient({
        onUnauthorized: (error) => {
            unauthorizedError = error;
        },
    });

    await assert.rejects(client.get("/dogs"), HttpError);
    assert.ok(unauthorizedError instanceof HttpError);
    assert.equal(unauthorizedError.status, 401);
    assert.equal(unauthorizedError.code, "authentication_required");
});

test("redirects concurrent expired-session notifications to login only once", () => {
    const redirects = [];
    const redirectExpiredSession = createSessionExpiryRedirect("/login", {
        origin: "https://dogs-diary.test",
        replace: (url) => redirects.push(url),
    });

    redirectExpiredSession();
    redirectExpiredSession();

    assert.deepEqual(redirects, [
        "https://dogs-diary.test/login?reason=session_expired",
    ]);
});

test("direct media upload sends bytes to R2 without application credentials before finalizing", async (context) => {
    const requests = [];
    const originalFetch = globalThis.fetch;
    context.after(() => { globalThis.fetch = originalFetch; });
    globalThis.fetch = async (url, options) => {
        requests.push({ url, options });
        if (url.endsWith('/uploads')) return jsonResponse({
            mode: 'direct', uploadId: 'a'.repeat(32),
            uploadUrl: 'https://account.r2.cloudflarestorage.com/media/staging/file?signature=test',
            contentType: 'image/png',
        });
        if (url.includes('.r2.cloudflarestorage.com/')) return new Response(null, { status: 200 });
        return jsonResponse({ id: 7 }, 201);
    };
    const api = new FetchClient({ baseUrl: '/api', csrfToken: 'csrf-value' });
    const file = new File(['image'], 'photo.png', { type: 'image/png' });
    assert.deepEqual(await uploadMedia(api, '/dogs/1/media', file), { id: 7 });
    assert.equal(requests[1].options.method, 'PUT');
    assert.equal(requests[1].options.body, file);
    assert.equal(requests[1].options.credentials, 'omit');
    assert.equal(requests[1].options.headers['X-CSRF-TOKEN'], undefined);
    assert.equal(requests[2].url, `/api/dogs/1/media/uploads/${'a'.repeat(32)}/complete`);
});

test("local media upload retains the multipart upload contract", async () => {
    const calls = [];
    const api = { post: async (path, body) => {
        calls.push({ path, body });
        return path.endsWith('/uploads') ? { mode: 'multipart' } : { id: 8 };
    } };
    const file = new File(['image'], 'photo.png', { type: 'image/png' });
    assert.deepEqual(await uploadMedia(api, '/dogs/1/treatments/2/media', file), { id: 8 });
    assert.equal(calls[1].path, '/dogs/1/treatments/2/media');
    assert.equal(calls[1].body.get('file'), file);
});

test("a failed R2 upload never finalizes a media record", async (context) => {
    const originalFetch = globalThis.fetch;
    context.after(() => { globalThis.fetch = originalFetch; });
    globalThis.fetch = async () => new Response(null, { status: 403 });
    let calls = 0;
    const api = { post: async () => {
        calls++;
        return { mode: 'direct', uploadId: 'b'.repeat(32), contentType: 'image/png',
            uploadUrl: 'https://account.r2.cloudflarestorage.com/staging/file' };
    } };
    await assert.rejects(uploadMedia(api, '/dogs/1/media', new File(['x'], 'x.png')), /Unable to upload/);
    assert.equal(calls, 1);
});

test("media upload rejects an unexpected storage endpoint", async () => {
    const api = { post: async () => ({ mode: 'direct', uploadId: 'c'.repeat(32),
        uploadUrl: 'https://untrusted.example/upload', contentType: 'image/png' }) };
    await assert.rejects(uploadMedia(api, '/dogs/1/media', new File(['x'], 'x.png')), /Unable to prepare/);
});

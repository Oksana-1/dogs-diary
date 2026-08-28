import assert from "node:assert/strict";
import test from "node:test";

import FetchClient from "../../assets/js/core/http/FetchClient.js";
import { HttpError } from "../../assets/js/core/http/HttpClient.js";

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

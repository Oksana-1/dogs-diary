import FetchClient from '../../../../assets/js/core/http/FetchClient.js';
import { HttpError } from '../../../../assets/js/core/http/HttpClient.js';
import { jsonResponse } from '../../helpers/jsonResponse.js';

const { module, test } = window.QUnit;

module('FetchClient', hooks => {
    let originalFetch;
    hooks.beforeEach(() => { originalFetch = globalThis.fetch; });
    hooks.afterEach(() => { globalThis.fetch = originalFetch; });

    test("uses same-origin credentials and adds CSRF only to mutations", async assert => {
        const requests = [];

        globalThis.fetch = async (url, options) => {
            requests.push({ url, options });

            return jsonResponse({ ok: true });
        };

        const client = new FetchClient({ baseUrl: "/api", csrfToken: "csrf-value" });

        await client.get("/dogs");
        await client.post("/dogs", { name: "Rusty" });

        assert.strictEqual(requests[0].url, "/api/dogs");
        assert.strictEqual(requests[0].options.credentials, "same-origin");
        assert.strictEqual(requests[0].options.headers["X-CSRF-TOKEN"], undefined);
        assert.strictEqual(requests[1].options.credentials, "same-origin");
        assert.strictEqual(requests[1].options.headers["X-CSRF-TOKEN"], "csrf-value");
        assert.strictEqual(requests[1].options.headers["Content-Type"], "application/json");
        assert.strictEqual(requests[1].options.body, JSON.stringify({ name: "Rusty" }));
    });

    test("preserves structured validation errors without treating them as an expired session", async assert => {

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
                assert.strictEqual(error.status, 422);
                assert.strictEqual(error.code, "validation_failed");
                assert.deepEqual(error.violations, [{ field: "name", message: "Name is required." }]);

                return true;
            },
        );
        assert.strictEqual(unauthorizedCalls, 0);
    });

    test("notifies the shared session handler for an API 401", async assert => {

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
        assert.strictEqual(unauthorizedError.status, 401);
        assert.strictEqual(unauthorizedError.code, "authentication_required");
    });
});

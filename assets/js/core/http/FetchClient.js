import HttpClient, { HttpError } from "./HttpClient.js";
import CanceledError from "./CancelledError.js";

/**
 * Build a usable message for an HTTP failure.
 *
 * `response.statusText` is empty on HTTP/2 — the protocol has no reason phrase —
 * which is the norm in production behind TLS. Using it alone produced
 * `HttpError(500, "")`, so every real failure logged and rendered as a blank
 * message while the server's own words sat unread on `.body`.
 */
function errorMessage(status, statusText, body) {

    if (body && typeof body === "object") {
        if (typeof body.error?.message === "string" && body.error.message) {
            return body.error.message;
        }
        if (typeof body.message === "string" && body.message) {
            return body.message;
        }
        if (typeof body.msg === "string" && body.msg) {
            return body.msg;
        }
    }

    if (statusText) {
        return statusText;
    }

    if (typeof body === "string" && body.trim()) {
        return body.trim().slice(0, 200);
    }

    return `HTTP ${status}`;
}

export default class FetchClient extends HttpClient {

    constructor({
                    baseUrl = "",
                    defaultHeaders = {},
                    timeout = 15000
                } = {}) {

        super();

        this.baseUrl = baseUrl;
        this.defaultHeaders = defaultHeaders;
        this.timeout = timeout;
    }

    async request({
                      url,
                      method,
                      headers = {},
                      body = null,
                      signal = null
                  }) {

        const controller = new AbortController();

        const timeout = setTimeout(() => {
            controller.abort();
        }, this.timeout);

        // A caller-supplied signal must not replace ours, or the timeout above
        // would fire against a signal nothing is listening to. Forward instead.
        // Kept in a named reference so it can be detached in `finally` — on the
        // success path the listener would otherwise stay attached to a signal
        // that may outlive this request, pinning a settled controller per call.
        let forwardAbort = null;

        if (signal) {
            if (signal.aborted) {
                controller.abort();
            } else {
                forwardAbort = () => controller.abort();
                signal.addEventListener("abort", forwardAbort, { once: true });
            }
        }

        // URLSearchParams and FormData must reach fetch untouched — the browser
        // derives the Content-Type (and multipart boundary) from them. Only
        // plain objects get serialised, and only those declare JSON.
        const isEncodedBody =
            body instanceof URLSearchParams
            || body instanceof FormData
            || body instanceof Blob
            || typeof body === "string";

        const hasJsonBody = body !== null
            && body !== undefined
            && !isEncodedBody;

        try {

            const response = await fetch(
                this.baseUrl + url,
                {
                    method,

                    // The JSON content type sits AFTER defaultHeaders so instance
                    // config cannot strip it; a per-call header still wins, which
                    // is intentional. Note this is a correctness measure only —
                    // it is NOT a CSRF defence, because the server never checks
                    // it (see ADR-004).
                    headers: {
                        ...this.defaultHeaders,
                        ...(hasJsonBody
                            ? { "Content-Type": "application/json" }
                            : {}),
                        ...headers
                    },

                    body: hasJsonBody
                        ? JSON.stringify(body)
                        : (body ?? undefined),

                    signal: controller.signal
                }
            );

            // An API call must never be redirected. When the admin session has
            // expired, the `backend` firewall answers 302 → /backend/login, and
            // fetch follows it (turning POST into GET per spec) onto a 200 HTML
            // login page. Without this check that page parsed as a successful
            // response body, so a save reported success and persisted nothing.
            if (response.redirected) {
                throw new HttpError(
                    response.status,
                    `Unexpected redirect to ${response.url} — session likely expired`
                );
            }

            const contentType =
                response.headers.get("content-type");

            let data = null;

            if (contentType?.includes("application/json")) {
                data = await response.json();
            } else {
                data = await response.text();
            }

            if (!response.ok) {
                throw new HttpError(
                    response.status,
                    errorMessage(response.status, response.statusText, data),
                    data
                );
            }

            return data;

        } catch (error) {

            if (error.name === "AbortError") {

                // Both the timeout above and a forwarded caller abort call the
                // same controller.abort(), so the AbortError alone cannot say
                // which happened. The caller's own signal can: if it is aborted,
                // the cancellation was deliberate and must not be reported as a
                // slow server. Checked first, so a cancellation that races the
                // timeout is still treated as a cancellation.
                if (signal?.aborted) {
                    throw new CanceledError();
                }

                throw new HttpError(
                    408,
                    "Request timeout"
                );
            }

            throw error;

        } finally {

            // Cleared here rather than straight after `fetch` resolves: headers
            // arriving is not the end of the request. A server that sends headers
            // then stalls mid-body would otherwise leave `response.json()` pending
            // with the timer already cancelled, hanging the caller forever —
            // exactly the case the timeout exists for.
            clearTimeout(timeout);

            if (signal && forwardAbort) {
                signal.removeEventListener("abort", forwardAbort);
            }
        }
    }

}

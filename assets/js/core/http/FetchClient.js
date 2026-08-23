import HttpClient, { HttpError } from "./HttpClient.js";
import CanceledError from "./CancelledError.js";
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
                    csrfToken = null,
                    timeout = 15000
                } = {}) {

        super();

        this.baseUrl = baseUrl;
        this.defaultHeaders = defaultHeaders;
        this.csrfToken = csrfToken;
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
        let forwardAbort = null;

        if (signal) {
            if (signal.aborted) {
                controller.abort();
            } else {
                forwardAbort = () => controller.abort();
                signal.addEventListener("abort", forwardAbort, { once: true });
            }
        }
        const isEncodedBody =
            body instanceof URLSearchParams
            || body instanceof FormData
            || body instanceof Blob
            || typeof body === "string";

        const hasJsonBody = body !== null
            && body !== undefined
            && !isEncodedBody;
        const normalizedMethod = method.toUpperCase();
        const needsCsrfToken = ["POST", "PUT", "PATCH", "DELETE"].includes(normalizedMethod);

        try {

            const response = await fetch(
                this.baseUrl + url,
                {
                    method,
                    headers: {
                        ...this.defaultHeaders,
                        ...(hasJsonBody
                            ? { "Content-Type": "application/json" }
                            : {}),
                        ...(needsCsrfToken && this.csrfToken
                            ? { "X-CSRF-TOKEN": this.csrfToken }
                            : {}),
                        ...headers
                    },

                    body: hasJsonBody
                        ? JSON.stringify(body)
                        : (body ?? undefined),

                    signal: controller.signal
                }
            );
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
            clearTimeout(timeout);

            if (signal && forwardAbort) {
                signal.removeEventListener("abort", forwardAbort);
            }
        }
    }

}

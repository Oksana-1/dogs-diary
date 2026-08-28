import FetchClient from "../http/FetchClient.js";
class ApiClient {

    constructor(httpClient) {
        this.http = httpClient;
    }

    get(endpoint, options = {}) {
        return this.http.get(endpoint, options);
    }

    post(endpoint, body, options = {}) {
        return this.http.post(endpoint, body, options);
    }

    put(endpoint, body, options = {}) {
        return this.http.put(endpoint, body, options);
    }

    patch(endpoint, body, options = {}) {
        return this.http.patch(endpoint, body, options);
    }

    delete(endpoint, options = {}) {
        return this.http.delete(endpoint, options);
    }

}

let sessionRedirectStarted = false;

function redirectExpiredSession() {
    if (sessionRedirectStarted) {
        return;
    }

    sessionRedirectStarted = true;

    const configuredLoginUrl = document.querySelector('meta[name="login-url"]')?.content ?? "/login";
    const loginUrl = new URL(configuredLoginUrl, window.location.origin);
    loginUrl.searchParams.set("reason", "session_expired");

    window.location.replace(loginUrl.toString());
}

const apiClient = new ApiClient(
    new FetchClient({
        baseUrl: "/api",
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? null,
        onUnauthorized: redirectExpiredSession,
        timeout: 15000
    })
);

export default apiClient;

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

const apiClient = new ApiClient(
    new FetchClient({
        baseUrl: "/api",
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? null,
        timeout: 15000
    })
);

export default apiClient;

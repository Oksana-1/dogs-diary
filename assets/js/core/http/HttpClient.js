export default class HttpClient {
    async request(config) {
        throw new Error("request() must be implemented");
    }

    get(url, options = {}) {
        return this.request({
            ...options,
            method: "GET",
            url
        });
    }

    post(url, body, options = {}) {
        return this.request({
            ...options,
            method: "POST",
            url,
            body
        });
    }

    put(url, body, options = {}) {
        return this.request({
            ...options,
            method: "PUT",
            url,
            body
        });
    }

    patch(url, body, options = {}) {
        return this.request({
            ...options,
            method: "PATCH",
            url,
            body
        });
    }

    delete(url, options = {}) {
        return this.request({
            ...options,
            method: "DELETE",
            url
        });
    }
}
export class HttpError extends Error {

    constructor(status, message, body = null) {
        super(message);

        this.name = "HttpError";
        this.status = status;
        this.body = body;
    }
}

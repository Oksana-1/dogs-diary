export default class CanceledError extends Error {

    constructor(message = "Request canceled") {
        super(message);

        this.name = "CanceledError";
    }
}

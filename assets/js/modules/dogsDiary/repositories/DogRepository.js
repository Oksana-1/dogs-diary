import DogCollection from "../entities/DogCollection.js";
import Dog from "../entities/Dog.js";

export default class DogRepository {

    constructor(api) {
        this.api = api;
    }

    async find(id) {
        return new Dog(await this.api.get(`/dogs/${id}`));
    }

    async list() {
        const dogs = await this.api.get("/dogs");
        return new DogCollection(dogs);
    }

    async create(data) {
        return new Dog(await this.api.post("/dogs", data));
    }

    async update(id, data) {
        return new Dog(await this.api.put(`/dogs/${id}`, data));
    }

    async delete(id) {
        return this.api.delete(`/dogs/${id}`);
    }

}

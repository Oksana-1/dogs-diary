import TreatmentCollection from "../entities/TreatmentCollection.js";
import Treatment from "../entities/Treatment.js";

export default class TreatmentRepository {

    constructor(api) {
        this.api = api;
    }

    async list(id) {
        const treatments = await this.api.get(`/dogs/${id}/treatments`);
        return new TreatmentCollection(treatments);
    }

    async create(dogId, data) {
        return new Treatment(await this.api.post(`/dogs/${dogId}/treatments`, data));
    }

    async update(dogId, id, data) {
        return new Treatment(await this.api.put(`/dogs/${dogId}/treatments/${id}`, data));
    }

    async delete(dogId, id) {
        return this.api.delete(`/dogs/${dogId}/treatments/${id}`);
    }

}

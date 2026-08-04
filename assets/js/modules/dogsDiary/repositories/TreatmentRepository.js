export default class TreatmentRepository {

    constructor(api) {
        this.api = api;
    }

    async list(id) {
        return this.api.get(`/dogs/${id}/treatments`);
    }

    async create(dogId, data) {
        return this.api.post(`/dogs/${dogId}/treatments`, data);
    }

    async update(dogId, id, data) {
        return this.api.put(`/dogs/${dogId}/treatments/${id}`, data);
    }

    async delete(dogId, id) {
        return this.api.delete(`/dogs/${dogId}/treatments/${id}`);
    }

}

import TreatmentMedia from '../entities/TreatmentMedia.js';

export default class TreatmentMediaRepository {
    constructor(api) {
        this.api = api;
    }

    async upload(dogId, treatmentId, file) {
        const body = new FormData();
        body.append('file', file);

        return new TreatmentMedia(
            await this.api.post(`/dogs/${dogId}/treatments/${treatmentId}/media`, body),
        );
    }

    async delete(dogId, treatmentId, mediaId) {
        return this.api.delete(`/dogs/${dogId}/treatments/${treatmentId}/media/${mediaId}`);
    }
}

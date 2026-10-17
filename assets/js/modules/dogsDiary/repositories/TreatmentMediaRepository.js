import TreatmentMedia from '../entities/TreatmentMedia.js';
import uploadMedia from './MediaUpload.js';

export default class TreatmentMediaRepository {
    constructor(api) {
        this.api = api;
    }

    async upload(dogId, treatmentId, file) {
        return new TreatmentMedia(
            await uploadMedia(this.api, `/dogs/${dogId}/treatments/${treatmentId}/media`, file),
        );
    }

    async delete(dogId, treatmentId, mediaId) {
        return this.api.delete(`/dogs/${dogId}/treatments/${treatmentId}/media/${mediaId}`);
    }
}

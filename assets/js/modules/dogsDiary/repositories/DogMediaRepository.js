import DogMedia from '../entities/DogMedia.js';
import uploadMedia from './MediaUpload.js';

export default class DogMediaRepository {
    constructor(api) {
        this.api = api;
    }

    async list(dogId) {
        const media = await this.api.get(`/dogs/${dogId}/media`);

        return media.map(item => new DogMedia(item));
    }

    async upload(dogId, file) {
        return new DogMedia(await uploadMedia(this.api, `/dogs/${dogId}/media`, file));
    }

    async delete(dogId, mediaId) {
        return this.api.delete(`/dogs/${dogId}/media/${mediaId}`);
    }

    async setThumbnail(dogId, mediaId) {
        return new DogMedia(await this.api.put(`/dogs/${dogId}/media/thumbnail`, { mediaId }));
    }

    async clearThumbnail(dogId) {
        return this.api.delete(`/dogs/${dogId}/media/thumbnail`);
    }

    async setProfile(dogId, mediaId) {
        return new DogMedia(await this.api.put(`/dogs/${dogId}/media/profile`, { mediaId }));
    }

    async clearProfile(dogId) {
        return this.api.delete(`/dogs/${dogId}/media/profile`);
    }
}

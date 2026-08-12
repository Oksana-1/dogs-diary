import DogMedia from '../entities/DogMedia.js';

export default class DogMediaRepository {
    constructor(api) {
        this.api = api;
    }

    async list(dogId) {
        const media = await this.api.get(`/dogs/${dogId}/media`);

        return media.map(item => new DogMedia(item));
    }

    async upload(dogId, file) {
        const body = new FormData();
        body.append('file', file);

        return new DogMedia(await this.api.post(`/dogs/${dogId}/media`, body));
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

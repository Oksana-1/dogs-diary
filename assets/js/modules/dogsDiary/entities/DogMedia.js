export default class DogMedia {
    constructor(media) {
        this.id = media.id;
        this.type = media.type;
        this.url = media.url;
        this.originalName = media.originalName;
        this.mimeType = media.mimeType;
        this.sizeBytes = media.sizeBytes;
        this.width = media.width;
        this.height = media.height;
        this.isThumbnail = Boolean(media.isThumbnail);
        this.isProfile = Boolean(media.isProfile);
        this.createdAt = media.createdAt;
    }
}

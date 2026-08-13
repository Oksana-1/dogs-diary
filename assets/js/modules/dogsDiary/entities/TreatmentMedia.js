export default class TreatmentMedia {
    constructor(media) {
        this.id = media.id;
        this.type = media.type;
        this.url = media.url;
        this.originalName = media.originalName;
        this.mimeType = media.mimeType;
        this.sizeBytes = media.sizeBytes;
        this.width = media.width;
        this.height = media.height;
        this.position = media.position;
        this.createdAt = media.createdAt;
    }
}

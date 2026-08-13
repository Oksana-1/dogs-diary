import TreatmentMedia from './TreatmentMedia.js';

export default class Treatment {
    constructor(treatment) {
        this.id = treatment.id;
        this.dogId = treatment.dogId;
        this.types = treatment.types;
        this.productName = treatment.productName;
        this.treatmentDate = treatment.treatmentDate;
        this.dueDate = treatment.dueDate;
        this.note = treatment.note;
        this.photo = treatment.photo ? new TreatmentMedia(treatment.photo) : null;
    }
}

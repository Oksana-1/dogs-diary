import Treatment from "./Treatment.js";

export default class TreatmentCollection {
    constructor(treatments) {
        return treatments.map((treatment) => new Treatment(treatment));
    }
}

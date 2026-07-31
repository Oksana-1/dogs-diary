import { startStimulusApp } from '@symfony/stimulus-bundle';
import TreatmentEditController from './controllers/treatment_edit_controller.js';
import TreatmentDeleteController from './controllers/treatment_delete_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('treatment-edit', TreatmentEditController);
app.register('treatment-delete', TreatmentDeleteController);

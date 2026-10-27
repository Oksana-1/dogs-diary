import Treatment from '../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';
import TreatmentMedia from '../../../../../assets/js/modules/dogsDiary/entities/TreatmentMedia.js';
const { module, test } = window.QUnit;
module('Treatment', () => {
    test('represents a missing photo as null', assert => {
        assert.strictEqual(new Treatment({ id: 9 }).photo, null);
        assert.strictEqual(new Treatment({ id: 9, photo: null }).photo, null);
    });
    test('hydrates a supplied photo as treatment media', assert => {
        const treatment = new Treatment({ id: 9, photo: { id: 12, originalName: 'record.png' } });
        assert.true(treatment.photo instanceof TreatmentMedia);
        assert.strictEqual(treatment.photo.originalName, 'record.png');
    });
});

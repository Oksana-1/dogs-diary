import Dog from '../../../../../assets/js/modules/dogsDiary/entities/Dog.js';
import Treatment from '../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';
import TreatmentMedia from '../../../../../assets/js/modules/dogsDiary/entities/TreatmentMedia.js';
const { module, test } = window.QUnit;
module('Dog', () => {
    test('normalizes absent treatments to an empty list', assert => {
        assert.deepEqual(new Dog({ id: 7 }).treatments, []);
        assert.deepEqual(new Dog({ id: 7, treatments: null }).treatments, []);
    });
    test('hydrates nested treatment photos without changing the API data', assert => {
        const data = { id: 7, treatments: [{ id: 9, photo: { id: 12, url: '/photo' } }] };
        const dog = new Dog(data);
        assert.true(dog.treatments[0] instanceof Treatment);
        assert.true(dog.treatments[0].photo instanceof TreatmentMedia);
        assert.strictEqual(dog.treatments[0].photo.url, '/photo');
        assert.notStrictEqual(dog.treatments, data.treatments);
        assert.false(data.treatments[0] instanceof Treatment);
    });
});

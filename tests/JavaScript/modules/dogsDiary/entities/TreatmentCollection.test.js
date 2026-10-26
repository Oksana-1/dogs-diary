import TreatmentCollection from '../../../../../assets/js/modules/dogsDiary/entities/TreatmentCollection.js';
import Treatment from '../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';
const { module, test } = window.QUnit;
module('TreatmentCollection', () => {
    test('hydrates records in server order without replacing the input records', assert => {
        const data = [{ id: 9 }, { id: 2 }];
        const collection = new TreatmentCollection(data);
        assert.deepEqual(collection.map(item => item.id), [9, 2]);
        assert.true(collection.every(item => item instanceof Treatment));
        assert.notStrictEqual(collection[0], data[0]);
    });
    test('preserves an empty result', assert => {
        assert.deepEqual(new TreatmentCollection([]), []);
    });
});

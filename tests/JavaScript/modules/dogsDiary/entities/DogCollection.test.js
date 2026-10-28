import DogCollection from '../../../../../assets/js/modules/dogsDiary/entities/DogCollection.js';
import Dog from '../../../../../assets/js/modules/dogsDiary/entities/Dog.js';
const { module, test } = window.QUnit;
module('DogCollection', () => {
    test('hydrates records in server order without replacing the input records', assert => {
        const data = [{ id: 9 }, { id: 2 }];
        const collection = new DogCollection(data);
        assert.deepEqual(collection.map(item => item.id), [9, 2]);
        assert.true(collection.every(item => item instanceof Dog));
        assert.notStrictEqual(collection[0], data[0]);
    });
    test('preserves an empty result', assert => {
        assert.deepEqual(new DogCollection([]), []);
    });
});

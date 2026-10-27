import DogMedia from '../../../../../assets/js/modules/dogsDiary/entities/DogMedia.js';
const { module, test } = window.QUnit;
module('DogMedia', () => {
    test('normalizes independent thumbnail and profile role flags', assert => {
        const thumbnail = new DogMedia({ id: 1, isThumbnail: 1, isProfile: null });
        assert.strictEqual(thumbnail.isThumbnail, true);
        assert.strictEqual(thumbnail.isProfile, false);
        const profile = new DogMedia({ id: 2, isThumbnail: 0, isProfile: true });
        assert.strictEqual(profile.isThumbnail, false);
        assert.strictEqual(profile.isProfile, true);
        const unselected = new DogMedia({ id: 3 });
        assert.strictEqual(unselected.isThumbnail, false);
        assert.strictEqual(unselected.isProfile, false);
    });
});

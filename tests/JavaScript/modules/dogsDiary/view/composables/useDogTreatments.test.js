import { flushPromises } from '@vue/test-utils';
import Treatment from '../../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';
import TreatmentMedia from '../../../../../../assets/js/modules/dogsDiary/entities/TreatmentMedia.js';
import { createComponent } from '../../../../helpers/createComponent.js';
import DogTreatmentsHarness from './fixtures/DogTreatmentsHarness.js';

const { module, test } = window.QUnit;
const data = { productName: 'Protection', types: ['anti_worm'], treatmentDate: '2026-10-01' };

module('useDogTreatments', () => {
    test('creates a treatment and prepends it to the displayed list', async assert => {
        const calls = [];
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [new Treatment({ id: 2, productName: 'Earlier' })],
            payload: { data }, mediaRepository: {},
            repository: { create: async (...args) => { calls.push(args); return new Treatment({ id: 9, ...data }); } },
        } });
        await wrapper.get('[aria-label="Add"]').trigger('click');
        await wrapper.get('[aria-label="Save"]').trigger('click');
        await flushPromises();
        assert.deepEqual(calls, [[7, data]]);
        assert.true(wrapper.findAll('li')[0].text().includes('Protection'));
        assert.strictEqual(wrapper.findAll('li').length, 2);
        assert.false(wrapper.find('[role="dialog"]').exists());
    });

    test('retries a failed photo upload by updating the saved treatment instead of creating a duplicate', async assert => {
        const creates = [], updates = [], uploads = [];
        const file = new File(['photo'], 'photo.png', { type: 'image/png' });
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [], payload: { data, photoFile: file },
            repository: {
                create: async (...args) => { creates.push(args); return new Treatment({ id: 9, ...data }); },
                update: async (...args) => { updates.push(args); return new Treatment({ id: 9, ...data }); },
            },
            mediaRepository: { upload: async (...args) => {
                uploads.push(args);
                if (uploads.length === 1) throw new Error('Photo upload failed.');
                return new TreatmentMedia({ id: 12, originalName: 'photo.png' });
            } },
        } });
        await wrapper.get('[aria-label="Add"]').trigger('click');
        await wrapper.get('[aria-label="Save"]').trigger('click');
        await flushPromises();
        assert.strictEqual(wrapper.get('[role="alert"]').text(), 'Photo upload failed.');
        assert.strictEqual(wrapper.findAll('li').length, 1);
        await wrapper.get('[aria-label="Save"]').trigger('click');
        await flushPromises();
        assert.deepEqual(creates, [[7, data]]);
        assert.deepEqual(updates, [[7, 9, data]]);
        assert.deepEqual(uploads, [[7, 9, file], [7, 9, file]]);
        assert.strictEqual(wrapper.findAll('li').length, 1);
        assert.true(wrapper.get('li').text().includes('photo.png'));
        assert.false(wrapper.find('[role="dialog"]').exists());
    });

    test('failed creation keeps the form open and leaves the list unchanged', async assert => {
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [], payload: { data }, mediaRepository: {},
            repository: { create: async () => { throw new Error('Save failed.'); } },
        } });
        await wrapper.get('[aria-label="Add"]').trigger('click');
        await wrapper.get('[aria-label="Save"]').trigger('click');
        await flushPromises();
        assert.strictEqual(wrapper.get('[role="alert"]').text(), 'Save failed.');
        assert.strictEqual(wrapper.findAll('li').length, 0);
        assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'false');
    });

    test('pending saves block repeated requests and cancellation', async assert => {
        let resolve;
        const pending = new Promise(done => { resolve = done; });
        let calls = 0;
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [], payload: { data }, mediaRepository: {},
            repository: { create: () => { calls++; return pending; } },
        } });
        await wrapper.get('[aria-label="Add"]').trigger('click');
        try {
            await wrapper.get('[aria-label="Save"]').trigger('click');
            await wrapper.get('[aria-label="Save"]').trigger('click');
            await wrapper.get('[aria-label="Cancel"]').trigger('click');
            assert.strictEqual(calls, 1);
            assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'true');
        } finally {
            resolve(new Treatment({ id: 9, ...data }));
            await flushPromises();
        }
        assert.false(wrapper.find('[role="dialog"]').exists());
    });

    test('removing a photo deletes the media and updates the rendered treatment', async assert => {
        const treatment = new Treatment({ id: 9, ...data, photo: { id: 12, originalName: 'photo.png' } });
        const calls = [];
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [treatment], payload: { data, removePhoto: true },
            repository: { update: async () => new Treatment(treatment) },
            mediaRepository: { delete: async (...args) => { calls.push(args); } },
        } });
        await wrapper.get('[aria-label="Edit 9"]').trigger('click');
        await wrapper.get('[aria-label="Save"]').trigger('click');
        await flushPromises();
        assert.deepEqual(calls, [[7, 9, 12]]);
        assert.false(wrapper.get('li').text().includes('photo.png'));
    });

    test('failed deletion preserves the treatment and confirmation can be retried', async assert => {
        const calls = [];
        const wrapper = createComponent(DogTreatmentsHarness, { props: {
            initialTreatments: [new Treatment({ id: 9, ...data })], payload: { data }, mediaRepository: {},
            repository: { delete: async (...args) => {
                calls.push(args);
                if (calls.length === 1) throw new Error('Delete failed.');
            } },
        } });
        await wrapper.get('[aria-label="Delete 9"]').trigger('click');
        assert.true(wrapper.get('[role="dialog"]').text().includes('Protection'));
        await wrapper.get('[aria-label="Confirm delete"]').trigger('click');
        await flushPromises();
        assert.true(wrapper.get('[role="dialog"]').text().includes('Delete failed.'));
        assert.strictEqual(wrapper.findAll('li').length, 1);
        await wrapper.get('[aria-label="Confirm delete"]').trigger('click');
        await flushPromises();
        assert.deepEqual(calls, [[7, 9], [7, 9]]);
        assert.strictEqual(wrapper.findAll('li').length, 0);
        assert.false(wrapper.find('[role="dialog"]').exists());
    });
});

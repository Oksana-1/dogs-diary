import TreatmentFormModal from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/TreatmentFormModal.js';
import Treatment from '../../../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';
import { createComponent } from '../../../../../helpers/createComponent.js';
import { getByLabel, getButton } from '../../../../../helpers/dom.js';
import { DOMWrapper } from '@vue/test-utils';

const { module, test } = window.QUnit;

module('TreatmentFormModal', () => {
    test('submits selected categories and normalized optional values', async assert => {
        const wrapper = createComponent(TreatmentFormModal, { props: { isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        await getByLabel(dialog, 'Treatment types').setValue(['flea_tick', 'anti_worm']);
        await getByLabel(dialog, 'Product / brand name').setValue('  Protection  ');
        await getByLabel(dialog, 'Date of treatment').setValue('2026-10-01');
        await getByLabel(dialog, 'Notes').setValue('  After food  ');
        getButton(dialog, 'Save').element.click();
        assert.deepEqual(wrapper.emitted('submit'), [[{
            data: { types: ['flea_tick', 'anti_worm'], productName: 'Protection', treatmentDate: '2026-10-01', dueDate: null, note: 'After food' },
            photoFile: null, removePhoto: false,
        }]]);
    });

    test('native validation blocks missing category and product', assert => {
        const wrapper = createComponent(TreatmentFormModal, { props: { isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        getButton(dialog, 'Save').element.click();
        assert.false(dialog.querySelector('form').checkValidity());
        assert.strictEqual(wrapper.emitted('submit'), undefined);
    });

    test('preserves the draft after an error without modifying the original treatment', async assert => {
        const treatment = new Treatment({ id: 9, productName: 'Original', types: ['anti_worm'], treatmentDate: '2026-10-01' });
        const wrapper = createComponent(TreatmentFormModal, { props: { treatment, isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        await getByLabel(dialog, 'Product / brand name').setValue('Updated');
        await getByLabel(dialog, 'Treatment types').setValue(['flea_tick']);
        await wrapper.setProps({ error: 'Upload failed. Retry saving.' });
        assert.strictEqual(getByLabel(dialog, 'Product / brand name').element.value, 'Updated');
        assert.strictEqual(dialog.querySelector('[role="alert"]').textContent, 'Upload failed. Retry saving.');
        assert.strictEqual(treatment.productName, 'Original');
        assert.deepEqual(treatment.types, ['anti_worm']);
    });

    test('disables saving, cancellation and file selection while pending', assert => {
        const wrapper = createComponent(TreatmentFormModal, { props: { isOpen: true, disabled: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        for (const name of ['Saving…', 'Cancel']) {
            const button = getButton(dialog, name).element;
            assert.true(button.disabled);
            button.click();
        }
        assert.true(dialog.querySelector('input[type="file"]').disabled);
        assert.strictEqual(wrapper.emitted('submit'), undefined);
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('removes an existing photo in the submitted change and restores it on reopening', async assert => {
        const treatment = new Treatment({
            id: 9, productName: 'Protection', types: ['anti_worm'], treatmentDate: '2026-10-01',
            photo: { id: 12, url: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg"/%3E' },
        });
        const wrapper = createComponent(TreatmentFormModal, { props: { treatment, isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        await getButton(dialog, 'Remove treatment photo').trigger('click');
        assert.strictEqual(dialog.querySelector('img[alt="Treatment photo preview"]'), null);
        getButton(dialog, 'Save').element.click();
        assert.true(wrapper.emitted('submit')[0][0].removePhoto);
        assert.strictEqual(treatment.photo.id, 12);
        await wrapper.setProps({ isOpen: false });
        await wrapper.setProps({ isOpen: true });
        assert.ok(document.querySelector('img[alt="Treatment photo preview"]'));
    });

    test('passes a selected file and releases its preview when removed', async assert => {
        const treatment = new Treatment({ productName: 'Protection', types: ['anti_worm'], treatmentDate: '2026-10-01' });
        const wrapper = createComponent(TreatmentFormModal, { props: { treatment, isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        const file = new File(['<svg xmlns="http://www.w3.org/2000/svg"/>'], 'photo.svg', { type: 'image/svg+xml' });
        const transfer = new DataTransfer();
        transfer.items.add(file);
        const input = dialog.querySelector('input[type="file"]');
        input.files = transfer.files;
        await new DOMWrapper(input).trigger('change');
        const preview = dialog.querySelector('img[alt="Treatment photo preview"]').src;
        assert.true(preview.startsWith('blob:'));
        getButton(dialog, 'Save').element.click();
        assert.strictEqual(wrapper.emitted('submit')[0][0].photoFile, file);
        const revoke = URL.revokeObjectURL;
        const revoked = [];
        URL.revokeObjectURL = url => { revoked.push(url); revoke.call(URL, url); };
        try {
            await getButton(dialog, 'Remove treatment photo').trigger('click');
            assert.deepEqual(revoked, [preview]);
            assert.strictEqual(input.value, '');
            assert.strictEqual(dialog.querySelector('img[alt="Treatment photo preview"]'), null);
        } finally {
            URL.revokeObjectURL = revoke;
        }
    });
});

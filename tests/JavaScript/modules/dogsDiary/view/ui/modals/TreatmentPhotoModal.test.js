import TreatmentPhotoModal from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/TreatmentPhotoModal.js';
import { createComponent } from '../../../../../helpers/createComponent.js';
import { getButton } from '../../../../../helpers/dom.js';

const { module, test } = window.QUnit;
const url = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg"/%3E';

module('TreatmentPhotoModal', () => {
    test('shows the photo filename as alternative text and emits close', async assert => {
        const wrapper = createComponent(TreatmentPhotoModal, {
            props: { isOpen: true, photo: { url, originalName: 'Prescription' } }, mount: true,
        });
        const dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(dialog.querySelector('img[alt="Prescription"]').getAttribute('src'), url);
        await getButton(dialog, 'Close').trigger('click');
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('uses a fallback image description and removes content when closed', async assert => {
        const wrapper = createComponent(TreatmentPhotoModal, { props: { isOpen: true, photo: { url } }, mount: true });
        assert.ok(document.querySelector('img[alt="Treatment photo"]'));
        await wrapper.setProps({ isOpen: false });
        assert.strictEqual(document.querySelector('[role="dialog"]'), null);
    });
});

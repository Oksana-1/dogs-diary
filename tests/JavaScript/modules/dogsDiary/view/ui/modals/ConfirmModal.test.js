import { nextTick } from 'vue';
import ConfirmModal from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/ConfirmModal.js';
import { createComponent } from '../../../../../helpers/createComponent.js';

const { module, test } = window.QUnit;
const props = { title: 'Delete treatment?', text: 'This treatment will be removed.', isOpen: true };

module('ConfirmModal', hooks => {
    hooks.beforeEach(assert => {
        assert.strictEqual(document.querySelector('[role="dialog"]'), null, 'previous teleported dialog was cleaned up');
    });

    test('renders the title and text in the open dialog', assert => {
        createComponent(ConfirmModal, { props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        assert.ok(dialog, 'real ModalDialog renders through native Teleport');
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-labelledby')).textContent, props.title);
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-describedby')).textContent, props.text);
    });

    test('Confirm emits exactly one confirm and no close', async assert => {
        const wrapper = createComponent(ConfirmModal, { props, mount: true });
        const button = [...document.querySelectorAll('[role="dialog"] button')].find(button => button.textContent.trim() === 'Confirm');
        button.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('confirm')?.length, 1);
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('Cancel emits exactly one close and no confirm', async assert => {
        const wrapper = createComponent(ConfirmModal, { props, mount: true });
        const button = [...document.querySelectorAll('[role="dialog"] button')].find(button => button.textContent.trim() === 'Cancel');
        button.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
        assert.strictEqual(wrapper.emitted('confirm'), undefined);
    });

    test('native disabled buttons suppress both events', async assert => {
        const wrapper = createComponent(ConfirmModal, { props: { ...props, disabled: true }, mount: true });
        const buttons = [...document.querySelectorAll('[role="dialog"] button')];
        assert.strictEqual(buttons.length, 2);
        for (const button of buttons) {
            assert.true(button.disabled, `${button.textContent.trim()} is disabled`);
            button.click();
        }
        await nextTick();
        assert.strictEqual(wrapper.emitted('confirm'), undefined);
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('renders no dialog or action buttons when closed', assert => {
        const wrapper = createComponent(ConfirmModal, { props: { ...props, isOpen: false }, mount: true });
        assert.strictEqual(document.querySelector('[role="dialog"]'), null);
        assert.strictEqual(wrapper.findAll('button').length, 0);
    });

    test('honors array global stubs and lets explicit stubs restore the real dialog', assert => {
        const stubbed = createComponent(ConfirmModal, {
            props,
            mount: true,
            global: { stubs: ['ModalDialog'] },
        });
        assert.strictEqual(stubbed.findComponent({ name: 'ModalDialog' }).props('title'), props.title);
        assert.strictEqual(document.querySelector('[role="dialog"]'), null, 'array entry stubs the child during full mounting');

        createComponent(ConfirmModal, {
            props,
            mount: true,
            global: { stubs: ['ModalDialog'] },
            stubs: { ModalDialog: false },
        });
        assert.strictEqual(document.querySelectorAll('[role="dialog"]').length, 1, 'explicit option restores the real child');
    });
});

import { nextTick } from 'vue';
import ModalDialog from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/ModalDialog.js';
import { createComponent } from '../../../../../helpers/createComponent.js';

const { module, test } = window.QUnit;
const props = { title: 'Edit dog', isOpen: true };

module('ModalDialog', () => {
    test('exposes its title and description as accessible dialog labels', assert => {
        createComponent(ModalDialog, {
            props: { ...props, description: 'Update your dog’s details.' },
            mount: true,
        });
        const dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(dialog.getAttribute('aria-modal'), 'true');
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-labelledby')).textContent, props.title);
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-describedby')).textContent, 'Update your dog’s details.');
    });

    test('omits the description relationship when no description is supplied', assert => {
        createComponent(ModalDialog, { props, mount: true });
        assert.false(document.querySelector('[role="dialog"]').hasAttribute('aria-describedby'));
    });

    test('keeps labels distinct when two dialogs are mounted', assert => {
        createComponent(ModalDialog, { props, mount: true });
        createComponent(ModalDialog, { props: { ...props, title: 'Edit treatment' }, mount: true });
        const dialogs = [...document.querySelectorAll('[role="dialog"]')];
        assert.strictEqual(dialogs.length, 2);
        assert.deepEqual(dialogs.map(dialog => document.getElementById(dialog.getAttribute('aria-labelledby')).textContent), [
            'Edit dog', 'Edit treatment',
        ]);
    });

    test('adds and removes the teleported dialog when the open prop changes', async assert => {
        const wrapper = createComponent(ModalDialog, { props: { ...props, isOpen: false }, mount: true });
        assert.strictEqual(document.querySelector('[role="dialog"]'), null);
        await wrapper.setProps({ isOpen: true });
        assert.ok(document.querySelector('[role="dialog"]'));
        await wrapper.setProps({ isOpen: false });
        assert.strictEqual(document.querySelector('[role="dialog"]'), null);
        assert.strictEqual(wrapper.emitted('close'), undefined, 'parent changes do not emit dismissal events');
    });

    test('clicking the backdrop requests dismissal', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        document.querySelector('[role="dialog"]').click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('clicking dialog content does not request dismissal', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        document.querySelector('[role="dialog"] h2').click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('Escape requests dismissal while other keys do not', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
        dialog.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('disabled dialogs block backdrop and Escape dismissal until enabled', async assert => {
        const wrapper = createComponent(ModalDialog, { props: { ...props, disabled: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.click();
        dialog.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
        await wrapper.setProps({ disabled: false });
        dialog.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });
});

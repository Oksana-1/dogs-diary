import { nextTick } from 'vue';
import ModalDialog from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/ModalDialog.js';
import ModalDialogHarness from './fixtures/ModalDialogHarness.js';
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

    test('updates the accessible description and clears it when reopened without one', async assert => {
        const wrapper = createComponent(ModalDialog, {
            props: { ...props, description: 'Original instructions' }, mount: true,
        });
        await wrapper.setProps({ description: 'Updated instructions' });
        let dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-describedby')).textContent, 'Updated instructions');
        await wrapper.setProps({ isOpen: false });
        await wrapper.setProps({ isOpen: true, title: 'New editor', description: null });
        dialog = document.querySelector('[role="dialog"]');
        assert.false(dialog.hasAttribute('aria-describedby'), 'old instructions are no longer referenced');
        assert.strictEqual(document.getElementById(dialog.getAttribute('aria-labelledby')).textContent, 'New editor');
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
        const dialog = document.querySelector('[role="dialog"]');
        dialog.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        dialog.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('clicking dialog content does not request dismissal', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        document.querySelector('[role="dialog"] h2').click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('native cancellation requests dismissal without closing the controlled dialog', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
        const cancel = new Event('cancel', { cancelable: true });
        dialog.dispatchEvent(cancel);
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
        assert.true(cancel.defaultPrevented);
        assert.true(dialog.open, 'parent retains control of the open state');
    });

    test('disabled dialogs block backdrop and Escape dismissal until enabled', async assert => {
        const wrapper = createComponent(ModalDialog, { props: { ...props, disabled: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        dialog.click();
        const cancel = new Event('cancel', { cancelable: true });
        dialog.dispatchEvent(cancel);
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
        assert.true(cancel.defaultPrevented);
        assert.true(dialog.open);
        await wrapper.setProps({ disabled: false });
        dialog.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        dialog.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('uses showModal and focuses a heading when there are no controls', assert => {
        createComponent(ModalDialog, { props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        assert.true(dialog.matches(':modal'), 'browser puts the dialog in its modal top layer');
        assert.strictEqual(document.activeElement, dialog.querySelector('h2'));
        const tab = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
        dialog.dispatchEvent(tab);
        assert.true(tab.defaultPrevented);
        assert.strictEqual(document.activeElement, dialog.querySelector('h2'));
    });

    test('honors autofocus and wraps Tab around currently enabled controls', async assert => {
        const wrapper = createComponent(ModalDialogHarness, { attrs: props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(document.activeElement, dialog.querySelector('input'));
        let buttons = [...dialog.querySelectorAll('button')];
        buttons.at(-1).focus();
        const tab = new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true });
        document.activeElement.dispatchEvent(tab);
        assert.true(tab.defaultPrevented);
        assert.strictEqual(document.activeElement, buttons[0]);
        await wrapper.setProps({ firstDisabled: true, lastDisabled: true, extra: true });
        buttons = [...dialog.querySelectorAll('button')];
        dialog.querySelector('input').focus();
        const backTab = new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true });
        document.activeElement.dispatchEvent(backTab);
        assert.strictEqual(document.activeElement, buttons.at(-1), 'new retry action participates and disabled actions are excluded');
    });

    test('keeps focus inside when pending or validation updates remove the focused control', async assert => {
        const wrapper = createComponent(ModalDialogHarness, { attrs: props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.querySelector('button').focus();
        await wrapper.setProps({ firstDisabled: true });
        await nextTick();
        assert.strictEqual(document.activeElement, dialog.querySelector('input'));
        await wrapper.setProps({ empty: true });
        await nextTick();
        assert.strictEqual(document.activeElement, dialog.querySelector('h2'));
    });

    test('a pointer gesture beginning inside and ending on the backdrop does not dismiss', async assert => {
        const wrapper = createComponent(ModalDialog, { props, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        dialog.querySelector('h2').dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        dialog.click();
        await nextTick();
        assert.strictEqual(wrapper.emitted('close'), undefined);
    });

    test('restores the opener after close, reopening, and unmount', async assert => {
        const opener = document.createElement('button');
        opener.textContent = 'Open editor';
        document.body.append(opener);
        try {
            opener.focus();
            const wrapper = createComponent(ModalDialog, { props, mount: true });
            await wrapper.setProps({ isOpen: false });
            assert.strictEqual(document.activeElement, opener);
            await wrapper.setProps({ isOpen: true });
            assert.strictEqual(document.activeElement, document.querySelector('[role="dialog"] h2'));
            wrapper.unmount();
            assert.strictEqual(document.activeElement, opener);
        } finally {
            opener.remove();
        }
    });

    test('uses a visible page fallback if the opener was removed', async assert => {
        const page = document.createElement('main');
        page.innerHTML = '<h1>Treatments</h1><button>Delete treatment</button>';
        document.body.append(page);
        try {
            page.querySelector('button').focus();
            const wrapper = createComponent(ModalDialog, { props, mount: true });
            page.querySelector('button').remove();
            await wrapper.setProps({ isOpen: false });
            assert.strictEqual(document.activeElement, page.querySelector('h1'), `Focus returned to ${document.activeElement?.tagName}: ${document.activeElement?.textContent?.slice(0, 80)}`);
            assert.strictEqual(page.querySelector('h1').getAttribute('tabindex'), '-1', 'fallback remains focusable while focused');
            page.querySelector('h1').blur();
            assert.false(page.querySelector('h1').hasAttribute('tabindex'), 'temporary focus attribute is cleaned up on blur');
        } finally {
            page.remove();
        }
    });

    test('uses a visible page fallback when the opener becomes hidden', async assert => {
        const page = document.createElement('main');
        page.innerHTML = '<h1>Dog diary</h1><button>Edit dog</button>';
        document.body.append(page);
        try {
            const opener = page.querySelector('button');
            opener.focus();
            const wrapper = createComponent(ModalDialog, { props, mount: true });
            opener.hidden = true;
            await wrapper.setProps({ isOpen: false });
            assert.strictEqual(document.activeElement, page.querySelector('h1'));
            assert.notStrictEqual(document.activeElement, opener);
        } finally {
            page.remove();
        }
    });

    test('closing the top dialog restores focus within the still-open dialog', async assert => {
        createComponent(ModalDialogHarness, { attrs: props, mount: true });
        const firstDialog = document.querySelector('[role="dialog"]');
        const opener = [...firstDialog.querySelectorAll('button')].at(-1);
        opener.focus();
        const second = createComponent(ModalDialog, { props: { ...props, title: 'Confirm change' }, mount: true });
        await second.setProps({ isOpen: false });
        assert.strictEqual(document.activeElement, opener);
        assert.true(firstDialog.matches(':modal'), 'the remaining dialog keeps native modality');
        assert.strictEqual(document.body.style.position, 'fixed', 'background remains locked');
    });

    test('retains scroll lock until the last dialog closes and restores inline values', async assert => {
        const bodyStyle = document.body.getAttribute('style');
        const rootStyle = document.documentElement.getAttribute('style');
        try {
            document.body.style.setProperty('position', 'relative', 'important');
            document.body.style.setProperty('top', '7px');
            document.body.style.setProperty('color', 'rgb(12, 34, 56)');
            document.documentElement.style.setProperty('overflow', 'scroll', 'important');
            const first = createComponent(ModalDialog, { props, mount: true });
            const second = createComponent(ModalDialog, { props: { ...props, title: 'Second' }, mount: true });
            assert.strictEqual(document.body.style.position, 'fixed');
            await first.setProps({ isOpen: false });
            assert.strictEqual(document.body.style.position, 'fixed');
            second.unmount();
            assert.strictEqual(document.body.style.position, 'relative');
            assert.strictEqual(document.body.style.getPropertyPriority('position'), 'important');
            assert.strictEqual(document.body.style.top, '7px');
            assert.strictEqual(document.body.style.color, 'rgb(12, 34, 56)');
            assert.strictEqual(document.documentElement.style.overflow, 'scroll');
            assert.strictEqual(document.documentElement.style.getPropertyPriority('overflow'), 'important');
        } finally {
            if (bodyStyle === null) document.body.removeAttribute('style');
            else document.body.setAttribute('style', bodyStyle);
            if (rootStyle === null) document.documentElement.removeAttribute('style');
            else document.documentElement.setAttribute('style', rootStyle);
        }
    });
});

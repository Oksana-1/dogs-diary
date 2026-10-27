import { flushPromises } from '@vue/test-utils';
import { createComponent } from '../../../../helpers/createComponent.js';
import AsyncModalHarness from './fixtures/AsyncModalHarness.js';

const { module, test } = window.QUnit;

module('useAsyncModal', () => {
    test('opens with the selected subject and closes on cancellation', async assert => {
        let calls = 0;
        const wrapper = createComponent(AsyncModalHarness, {
            props: { subject: { name: 'Milo' }, action: () => { calls++; } },
        });
        assert.false(wrapper.find('[role="dialog"]').exists());
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        assert.strictEqual(wrapper.get('[role="status"]').text(), 'Milo');
        await wrapper.get('button[aria-label="Close"]').trigger('click');
        assert.false(wrapper.find('[role="dialog"]').exists());
        assert.strictEqual(calls, 0);
    });

    test('blocks duplicate submission and dismissal while pending, then closes on success', async assert => {
        const subjects = [];
        let complete;
        const pending = new Promise(resolve => { complete = resolve; });
        const wrapper = createComponent(AsyncModalHarness, {
            props: {
                subject: { name: 'Milo' },
                action: subject => { subjects.push(subject.name); return pending; },
            },
        });
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        try {
            await wrapper.get('button[aria-label="Submit"]').trigger('click');
            assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'true');
            await wrapper.get('button[aria-label="Submit"]').trigger('click');
            await wrapper.get('button[aria-label="Close"]').trigger('click');
            assert.true(wrapper.find('[role="dialog"]').exists());
            assert.deepEqual(subjects, ['Milo']);
        } finally {
            complete();
            await flushPromises();
        }
        assert.false(wrapper.find('[role="dialog"]').exists());
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'false');
    });

    test('keeps the subject and displays a failed action, then allows a successful retry', async assert => {
        const subjects = [];
        const wrapper = createComponent(AsyncModalHarness, {
            props: {
                subject: { name: 'Milo' },
                action: async subject => {
                    subjects.push(subject.name);
                    if (subjects.length === 1) throw new Error('Unable to save.');
                },
            },
        });
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        await wrapper.get('button[aria-label="Submit"]').trigger('click');
        await flushPromises();
        assert.strictEqual(wrapper.get('[role="alert"]').text(), 'Unable to save.');
        assert.strictEqual(wrapper.get('[role="status"]').text(), 'Milo');
        assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'false');
        await wrapper.get('button[aria-label="Submit"]').trigger('click');
        await flushPromises();
        assert.deepEqual(subjects, ['Milo', 'Milo']);
        assert.false(wrapper.find('[role="dialog"]').exists());
    });

    for (const fallbackError of [undefined, 'Try saving again.']) {
        test(`uses ${fallbackError ? 'the configured' : 'the default'} message for errors without a message`, async assert => {
            const wrapper = createComponent(AsyncModalHarness, {
                props: { fallbackError, action: () => { throw null; } },
            });
            await wrapper.get('button[aria-label="Open"]').trigger('click');
            await wrapper.get('button[aria-label="Submit"]').trigger('click');
            await flushPromises();
            assert.strictEqual(wrapper.get('[role="alert"]').text(), fallbackError ?? 'Please try again.');
            assert.strictEqual(wrapper.get('[role="dialog"]').attributes('aria-busy'), 'false');
        });
    }

    test('clears an earlier error when reopened for another subject', async assert => {
        const wrapper = createComponent(AsyncModalHarness, {
            props: { subject: { name: 'Milo' }, action: () => { throw new Error('Failed.'); } },
        });
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        await wrapper.get('button[aria-label="Submit"]').trigger('click');
        await flushPromises();
        assert.true(wrapper.find('[role="alert"]').exists());
        await wrapper.get('button[aria-label="Close"]').trigger('click');
        await wrapper.setProps({ subject: { name: 'Luna' } });
        await wrapper.get('button[aria-label="Open"]').trigger('click');
        assert.false(wrapper.find('[role="alert"]').exists());
        assert.strictEqual(wrapper.get('[role="status"]').text(), 'Luna');
    });
});

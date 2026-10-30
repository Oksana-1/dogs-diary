import Component from '../../../../../assets/js/modules/dogsDiary/view/AppSignUp.js';
import { createLocaleI18n } from '../../../../../assets/js/core/i18n/locale.js';
import { createComponent } from '../../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('AppSignUp translations');
for (const locale of ['en', 'uk']) {
    QUnit.test('renders registration controls and terms in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: { formAction: '/sign-up', loginUrl: '/login', csrfToken: 'registration-token' },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages[locale]['auth.signup_title']);
        assert.false(wrapper.text().includes('auth.'), 'no unresolved translation keys');
        for (const [field, key] of [['name', 'name'], ['email', 'email'], ['password', 'password'], ['password-confirmation', 'confirm_password']]) {
            assert.strictEqual(wrapper.get(`label[for="sign-up-${field}"]`).text(), messages[locale][`auth.${key}`]);
        }
        assert.strictEqual(wrapper.get('input[name="name"]').attributes('placeholder'), messages[locale]['auth.name_placeholder']);
        assert.strictEqual(wrapper.get('input[name="password"]').attributes('placeholder'), messages[locale]['auth.create_password']);
        assert.strictEqual(wrapper.get('input[name="password_confirmation"]').attributes('placeholder'), messages[locale]['auth.repeat_password']);
        assert.strictEqual(wrapper.get('input[name="terms"]').element.labels[0].textContent.trim(), messages[locale]['auth.terms']);
        assert.strictEqual(wrapper.get('button[type="submit"]').text(), messages[locale]['auth.create']);
        assert.strictEqual(wrapper.get('a[href="/login"]').text(), messages[locale]['auth.login']);
        assert.strictEqual(wrapper.get('form').attributes('action'), '/sign-up');
        assert.strictEqual(wrapper.get('form').attributes('method'), 'post');
        assert.strictEqual(wrapper.get('input[name="_csrf_token"]').element.value, 'registration-token');
    });

    QUnit.test('keeps safe registration values and accessible server errors in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: {
                formAction: '/sign-up', loginUrl: '/login', csrfToken: 'registration-token',
                values: { name: 'Test owner', email: 'owner@example.test', terms: true },
                errors: { global: [messages[locale]['auth.registration_expired']], email: [messages[locale]['auth.email_exists']] },
            },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('[role="alert"]').text(), messages[locale]['auth.registration_expired']);
        const email = wrapper.get('input[name="email"]');
        assert.strictEqual(email.attributes('aria-invalid'), 'true');
        assert.strictEqual(wrapper.get(`[id="${email.attributes('aria-describedby')}"]`).text(), messages[locale]['auth.email_exists']);
        assert.strictEqual(email.element.value, 'owner@example.test');
        assert.strictEqual(wrapper.get('input[name="name"]').element.value, 'Test owner');
        assert.true(wrapper.get('input[name="terms"]').element.checked);
        assert.strictEqual(wrapper.get('input[name="password"]').element.value, '');
        assert.strictEqual(wrapper.get('input[name="password_confirmation"]').element.value, '');
    });
}

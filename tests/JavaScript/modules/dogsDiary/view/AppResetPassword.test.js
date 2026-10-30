import Component from '../../../../../assets/js/modules/dogsDiary/view/AppResetPassword.js';
import { createLocaleI18n } from '../../../../../assets/js/core/i18n/locale.js';
import { createComponent } from '../../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('AppResetPassword translations');
for (const locale of ['en', 'uk']) {
    QUnit.test('renders password replacement controls in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: { formAction: '/reset-password/test-token', loginUrl: '/login', csrfToken: 'reset-token' },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages[locale]['auth.choose_password']);
        assert.false(wrapper.text().includes('auth.'), 'no unresolved translation keys');
        assert.strictEqual(wrapper.get('label[for="reset-password"]').text(), messages[locale]['auth.new_password']);
        assert.strictEqual(wrapper.get('label[for="reset-password-confirmation"]').text(), messages[locale]['auth.confirm_new']);
        assert.strictEqual(wrapper.get('input[name="password"]').attributes('placeholder'), messages[locale]['auth.new_password_placeholder']);
        assert.strictEqual(wrapper.get('input[name="password_confirmation"]').attributes('placeholder'), messages[locale]['auth.repeat_new']);
        const helpId = wrapper.get('input[name="password"]').attributes('aria-describedby');
        assert.strictEqual(wrapper.get(`[id="${helpId}"]`).text(), messages[locale]['auth.password_help']);
        assert.strictEqual(wrapper.get('button[type="submit"]').text(), messages[locale]['auth.reset']);
        assert.strictEqual(wrapper.get('a[href="/login"]').text(), messages[locale]['auth.login_lower']);
        assert.strictEqual(wrapper.get('form').attributes('action'), '/reset-password/test-token');
        assert.strictEqual(wrapper.get('form').attributes('method'), 'post');
        assert.strictEqual(wrapper.get('input[name="_csrf_token"]').element.value, 'reset-token');
    });

    QUnit.test('shows server validation errors associated with password fields in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: {
                formAction: '/reset-password/test-token', loginUrl: '/login', csrfToken: 'reset-token',
                errors: {
                    global: [messages[locale]['auth.reset_expired']], password: [messages[locale]['auth.password_short']],
                    password_confirmation: [messages[locale]['auth.password_mismatch']],
                },
            },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.true(wrapper.findAll('[role="alert"]').some(alert => alert.text() === messages[locale]['auth.reset_expired']));
        for (const [field, key] of [['password', 'password_short'], ['password_confirmation', 'password_mismatch']]) {
            const input = wrapper.get(`input[name="${field}"]`);
            assert.strictEqual(input.attributes('aria-invalid'), 'true');
            assert.strictEqual(wrapper.get(`[id="${input.attributes('aria-describedby')}"]`).text(), messages[locale][`auth.${key}`]);
            assert.strictEqual(input.element.value, '');
        }
    });
}

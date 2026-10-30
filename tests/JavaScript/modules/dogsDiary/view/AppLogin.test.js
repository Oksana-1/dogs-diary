import Component from '../../../../../assets/js/modules/dogsDiary/view/AppLogin.js';
import { createLocaleI18n } from '../../../../../assets/js/core/i18n/locale.js';
import { createComponent } from '../../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('AppLogin translations');
for (const locale of ['en', 'uk']) {
    QUnit.test('renders login and recovery controls in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: { formAction: '/login', signUpUrl: '/sign-up', forgotPasswordUrl: '/reset-password', csrfToken: 'login-token' },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages[locale]['auth.login_title']);
        assert.false(wrapper.text().includes('auth.'), 'no unresolved translation keys');
        assert.strictEqual(wrapper.get('label[for="login-email"]').text(), messages[locale]['auth.email']);
        assert.strictEqual(wrapper.get('label[for="login-password"]').text(), messages[locale]['auth.password']);
        assert.strictEqual(wrapper.get('input[name="password"]').attributes('placeholder'), messages[locale]['auth.password_placeholder']);
        assert.strictEqual(wrapper.get('input[name="remember_me"]').element.labels[0].textContent.trim(), messages[locale]['auth.remember']);
        assert.strictEqual(wrapper.get('button[type="submit"]').text(), messages[locale]['auth.login']);
        assert.strictEqual(wrapper.get('a[href="/reset-password"]').text(), messages[locale]['auth.forgot']);
        assert.strictEqual(wrapper.get('a[href="/sign-up"]').text(), messages[locale]['auth.create_account']);
        assert.strictEqual(wrapper.get('form').attributes('action'), '/login');
        assert.strictEqual(wrapper.get('form').attributes('method'), 'post');
        assert.strictEqual(wrapper.get('input[name="_csrf_token"]').element.value, 'login-token');
        assert.false(wrapper.find('[role="alert"]').exists());
    });

    QUnit.test('preserves translated authentication errors and notices in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: {
                formAction: '/login', signUpUrl: '/sign-up', forgotPasswordUrl: '/reset-password', csrfToken: 'login-token',
                lastEmail: 'owner@example.test', error: messages[locale]['auth.invalid_credentials'],
                notices: [messages[locale]['auth.session_expired']],
            },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('[role="alert"]').text(), messages[locale]['auth.invalid_credentials']);
        assert.strictEqual(wrapper.get('[role="status"]').text(), messages[locale]['auth.session_expired']);
        assert.strictEqual(wrapper.get('input[name="email"]').element.value, 'owner@example.test');
        for (const name of ['email', 'password']) {
            const input = wrapper.get(`input[name="${name}"]`);
            assert.strictEqual(input.attributes('aria-invalid'), 'true');
            assert.strictEqual(input.attributes('aria-describedby'), wrapper.get('[role="alert"]').attributes('id'));
        }
        assert.strictEqual(wrapper.get('input[name="password"]').element.value, '');
    });
}

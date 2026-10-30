import Component from '../../../../../assets/js/modules/dogsDiary/view/AppResetPasswordRequest.js';
import { createLocaleI18n } from '../../../../../assets/js/core/i18n/locale.js';
import { createComponent } from '../../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('AppResetPasswordRequest translations');
for (const locale of ['en', 'uk']) {
    QUnit.test('renders recovery request and navigation controls in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: { formAction: '/reset-password', loginUrl: '/login', csrfToken: 'request-token' },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages[locale]['auth.forgot']);
        assert.false(wrapper.text().includes('auth.'), 'no unresolved translation keys');
        assert.strictEqual(wrapper.get('label[for="reset-email"]').text(), messages[locale]['auth.email']);
        assert.strictEqual(wrapper.get('button[type="submit"]').text(), messages[locale]['auth.send_link']);
        assert.deepEqual(wrapper.findAll('a[href="/login"]').map(link => link.text()), [messages[locale]['auth.back_arrow'], messages[locale]['auth.login']]);
        assert.strictEqual(wrapper.get('form').attributes('action'), '/reset-password');
        assert.strictEqual(wrapper.get('form').attributes('method'), 'post');
        assert.strictEqual(wrapper.get('input[name="_csrf_token"]').element.value, 'request-token');
        assert.false(wrapper.find('[role="alert"]').exists());
    });

    QUnit.test('keeps the email and translated recovery errors in ' + locale, assert => {
        const errors = [messages[locale]['auth.reset_expired'], messages[locale]['auth.invalid_email']];
        const wrapper = createComponent(Component, {
            props: { formAction: '/reset-password', loginUrl: '/login', csrfToken: 'request-token', email: 'invalid@', errors },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.deepEqual(wrapper.get('[role="alert"]').findAll('p').map(error => error.text()), errors);
        const email = wrapper.get('input[name="email"]');
        assert.strictEqual(email.element.value, 'invalid@');
        assert.strictEqual(email.attributes('aria-invalid'), 'true');
        assert.strictEqual(email.attributes('aria-describedby'), wrapper.get('[role="alert"]').attributes('id'));
    });
}

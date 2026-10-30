import Component from '../../../../../assets/js/modules/dogsDiary/view/AppResetPasswordCheckEmail.js';
import { createLocaleI18n } from '../../../../../assets/js/core/i18n/locale.js';
import { createComponent } from '../../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('AppResetPasswordCheckEmail translations');
for (const locale of ['en', 'uk']) {
    QUnit.test('renders neutral confirmation, expiry and recovery actions in ' + locale, assert => {
        const wrapper = createComponent(Component, {
            props: { loginUrl: '/login', requestPasswordUrl: '/reset-password', tokenLifetimeMinutes: 37 },
            global: { plugins: [createLocaleI18n({ locale, fallbackLocale: 'en', messages: structuredClone(messages) })] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages[locale]['auth.check_email']);
        assert.false(wrapper.text().includes('auth.'), 'no unresolved translation keys');
        assert.true(wrapper.text().includes(messages[locale]['auth.neutral_notice']));
        assert.strictEqual(wrapper.get('[role="status"]').text(), messages[locale]['auth.expiry_notice'].replace('{minutes}', '37'));
        assert.strictEqual(wrapper.get('a[href="/login"]').text(), messages[locale]['auth.back']);
        assert.strictEqual(wrapper.get('a[href="/reset-password"]').text(), messages[locale]['auth.retry']);
        assert.false(wrapper.find('form').exists());
    });
}

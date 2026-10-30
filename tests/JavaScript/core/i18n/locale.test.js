import { createLocaleI18n, formatDateOnly, readLocaleContext } from '../../../../assets/js/core/i18n/locale.js';
import AppLogin from '../../../../assets/js/modules/dogsDiary/view/AppLogin.js';
import TreatmentTable from '../../../../assets/js/modules/dogsDiary/view/islands/dogDetailPage/TreatmentTable.js';
import { createComponent } from '../../helpers/createComponent.js';

const { QUnit } = window;
const messages = JSON.parse(document.getElementById('locale-test-catalogues').textContent);

QUnit.module('Locale foundation');

QUnit.test('login island uses the server locale and falls back to the shared English catalogue', assert => {
    const fallbackMessages = { ...messages, uk: { ...messages.uk } };
    delete fallbackMessages.uk['auth.login_intro'];
    const context = { locale: 'uk', fallbackLocale: 'en', messages: fallbackMessages };
    const meta = document.createElement('meta');
    meta.name = 'diary-i18n';
    meta.content = JSON.stringify(context);
    document.head.append(meta);
    try {
        assert.deepEqual(readLocaleContext(), context);
        const wrapper = createComponent(AppLogin, {
            props: { formAction: '/login', forgotPasswordUrl: '/reset-password', signUpUrl: '/sign-up', csrfToken: 'csrf' },
            global: { plugins: [createLocaleI18n()] },
        });
        assert.strictEqual(wrapper.get('h1').text(), messages.uk['auth.login_title']);
        assert.true(wrapper.text().includes(messages.en['auth.login_intro']));
    } finally {
        meta.remove();
    }
});

QUnit.test('Ukrainian quantities select one, few, many and fractional forms', assert => {
    const i18n = createLocaleI18n({ locale: 'uk', fallbackLocale: 'en', messages });
    for (const [count, noun] of [[1, 'процедура'], [2, 'процедури'], [5, 'процедур'], [11, 'процедур'], [21, 'процедура'], [22, 'процедури'], [25, 'процедур'], [1.5, 'процедури']]) {
        assert.strictEqual(i18n.global.t('diary.treatments_count', count), `${count} ${noun}`, `quantity ${count}`);
    }
    i18n.dispose();
});

QUnit.test('treatment and due dates render in the server locale without changing date-only values', assert => {
    const meta = document.createElement('meta');
    meta.name = 'diary-i18n';
    meta.content = JSON.stringify({ locale: 'uk', fallbackLocale: 'en', messages });
    document.head.append(meta);
    const treatment = { id: 1, types: ['vaccination'], productName: 'Test vaccine', treatmentDate: '2024-02-29', dueDate: '2025-01-01' };
    try {
        const wrapper = createComponent(TreatmentTable, { props: { treatments: [treatment] } });
        assert.true(wrapper.get('table').text().includes('29 лютого 2024 р.'));
        assert.true(wrapper.get('table').text().includes('1 січня 2025 р.'));
        assert.strictEqual(treatment.treatmentDate, '2024-02-29');
        assert.strictEqual(treatment.dueDate, '2025-01-01');
    } finally {
        meta.remove();
    }
});

QUnit.test('business dates keep their calendar day, including leap and year boundaries', assert => {
    for (const value of ['2024-02-29', '2025-01-01', '2025-12-31']) {
        for (const locale of ['en', 'uk']) {
            const expected = new Intl.DateTimeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' }).format(new Date(`${value}T00:00:00Z`));
            assert.strictEqual(formatDateOnly(value, locale), expected, `${locale} ${value}`);
        }
    }
    assert.strictEqual(formatDateOnly(null, 'uk'), '—');
    assert.strictEqual(formatDateOnly('2024-02-29', 'en'), 'February 29, 2024');
    assert.strictEqual(formatDateOnly('2024-02-29', 'uk'), '29 лютого 2024 р.');
});

QUnit.test('date-only display survives browser defaults on both sides of UTC', assert => {
    const NativeDateTimeFormat = Intl.DateTimeFormat;
    try {
        for (const timeZone of ['Pacific/Kiritimati', 'America/Los_Angeles']) {
            Intl.DateTimeFormat = function (locale, options) {
                return new NativeDateTimeFormat(locale, { timeZone, ...options });
            };
            assert.strictEqual(formatDateOnly('2024-02-29', 'en'), 'February 29, 2024', timeZone);
            assert.strictEqual(formatDateOnly('2025-01-01', 'en'), 'January 1, 2025', timeZone);
        }
    } finally {
        Intl.DateTimeFormat = NativeDateTimeFormat;
    }
});

import { createI18n } from 'vue-i18n';

export function readLocaleContext() {
    const content = document.querySelector('meta[name="diary-i18n"]')?.content;
    return content ? JSON.parse(content) : { locale: 'en', fallbackLocale: 'en', messages: {} };
}

export function createLocaleI18n(context = readLocaleContext()) {
    const ukrainianPlurals = new Intl.PluralRules('uk');
    return createI18n({
        legacy: false,
        locale: context.locale,
        fallbackLocale: context.fallbackLocale,
        messages: context.messages,
        flatJson: true,
        pluralRules: {
            uk: count => ({ one: 0, few: 1, many: 2, other: 3 })[ukrainianPlurals.select(count)],
        },
    });
}

export function formatDateOnly(value, locale = readLocaleContext().locale) {
    return value
        ? new Intl.DateTimeFormat(locale, {
            year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC',
        }).format(new Date(`${value}T00:00:00Z`))
        : '—';
}

# Browser language and catalogue contract

Accepted for issue #15. Supported locales are `en` and `uk`; English is the
fallback. `src/Localization/LocalePolicy.php` owns these values and cookie policy.
Framework configuration and the browser bootstrap derive from them.

## Request and switching

For every main request, including APIs and password recovery, the locale is:

1. A supported `diary_locale` cookie, if present.
2. Without a cookie, the first supported `Accept-Language` preference by quality,
   reducing regional tags such as `uk-UA` and `en-GB` to their base language.
   Quality zero is refused; unsupported preferences and wildcards are skipped.
3. English if no preference matches. An invalid or empty stored cookie also uses
   English, rather than re-running browser detection.

Detection alone does not write a preference. The visible public/authenticated
language forms POST `locale`, `_csrf_token` (token ID `language`) and `returnTo`
to `/language`. Supported choices return 303 with a root-relative same-app
redirect and a one-year cookie. Unsafe redirect values fall back to `/`.
Invalid CSRF returns 403; unsupported locale returns 422; neither writes a cookie.
The cookie has path `/`, HttpOnly, SameSite=Lax, and Secure on HTTPS. It is a
browser preference shared across tabs and authentication changes, not user data.
No session or localStorage locale copy is maintained.

The redirect reloads the page to synchronize Twig and every Vue island. Normal
same-origin requests send the cookie automatically; API payloads, enum codes,
validation contracts and date serialization stay unchanged. Responses declare
`Content-Language` and vary on `Cookie` and `Accept-Language`. The selector is a compact native select at the right edge of the header, outside
the mobile menu. Its two options read ENG/UKR in English and АНГЛ/УКР in
Ukrainian, with an accessible name but no visible label or submit button.
JavaScript submits the CSRF-protected form when the selection changes.

## Rendering and messages

The request subscriber resolves locale before Symfony's locale consumers.
`html[lang]` and the escaped `meta[name="diary-i18n"]` JSON derive from that
request. `LocaleExtension` exports the Symfony `messages` catalogues, supported
locales, locale and fallbackLocale. Each island installs vue-i18n with that
bootstrap. No browser language detection competes with the server decision.

`translations/messages.en.json` and `messages.uk.json` are the only message
sources. Use stable dotted keys. Symfony Translation loads them; Vue I18n uses
`flatJson` to consume them. Missing Ukrainian keys fall back to English in both
runtimes. Missing keys in both languages remain visible as keys, so additions
should always provide English. `auth.login_intro` demonstrates English fallback.
Messages are plain text; do not introduce HTML or render translations with
`v-html`. Shared named interpolation can use `{name}`: Vue uses named arguments;
Symfony callers supply the literal replacement key `{'{name}': value}`.

No ICU catalogues or custom PHP translator are introduced. Vue plural messages
use vue-i18n's native `|` choices. Ukrainian choices are ordered **one, few, many,
other**, selected by `Intl.PluralRules('uk')`, including fractional counts. English
uses vue-i18n's standard singular/plural choices. `diary.treatments_count` supplies
the foundation; these choice messages are for Vue's plural API, not Symfony's
plain `trans` call. Future server plurals should use explicit category keys with
the standard translator rather than passing Vue choice syntax to Twig.

The selected native `vue-i18n.esm-browser.prod.js` build includes its compiler and
imports the existing bare `vue` entry. AssetMapper/importmap serves both; no Node
build, bundler, or additional Vue runtime is required.

## Dates and recovery

Date-only API values remain `YYYY-MM-DD`. `formatDateOnly` owns localized display,
using UTC explicitly so browser timezone never moves a business date. Existing
`formatDate` imports re-export it; dog milestones and treatment/due dates share it.
Form values, sorting and API contracts remain date-only strings. Empty dates show
an em dash. This is presentation formatting, not a timestamp conversion policy.

Password recovery pages resolve the locale of the browser opening the page,
including when a reset link opens in another browser. The request locale is
passed explicitly through `TemplatedEmail::locale()` for #16 (available to the
template as `email.locale`); this issue does not
translate the email body or bind the token to a language. Full authentication and
diary text translation remain #16 and #17; selector and login heading demonstrate
the shared translation boundary now.

## Verification

`LanguageTest` covers public/authenticated switch, reload/navigation, API locale,
cookie flags/precedence, regional detection, invalid preference/CSRF, safe redirect,
and Twig/bootstrap catalogue agreement. `LocalePolicyTest` covers preference order
and quality zero. `PasswordResetTest` checks browser-dependent reset pages and
email locale context. `core/i18n/locale.test.js` mounts the real login island with
the production catalogues, tests English fallback, Ukrainian plural counts
1/2/5/11/21/22/25/1.5 and leap/year-boundary dates with positive/negative timezone
defaults. This is component and HTTP coverage; a full browser journey remains a
separate manual/QA check.

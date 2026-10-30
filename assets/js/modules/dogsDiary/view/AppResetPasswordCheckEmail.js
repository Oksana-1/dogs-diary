import { mdiEmailCheckOutline } from '@mdi/js';
import AuthMdiIcon from './ui/AuthMdiIcon.js';

export default {
    name: 'AppResetPasswordCheckEmail',

    components: {
        AuthMdiIcon,
    },

    props: {
        loginUrl: { type: String, required: true },
        requestPasswordUrl: { type: String, required: true },
        tokenLifetimeMinutes: { type: Number, required: true },
    },

    setup() {
        return { mdiEmailCheckOutline };
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="check-email-title">
            <div class="auth-card">
                <div class="auth-heading auth-heading-spacious">
                    <AuthMdiIcon :path="mdiEmailCheckOutline" />
                    <p class="auth-eyebrow">{{ $t('auth.almost') }}</p>
                    <h1 id="check-email-title">{{ $t('auth.check_email') }}</h1>
                    <p>{{ $t('auth.neutral_notice') }}</p>
                </div>

                <div class="auth-notice" role="status">
                    <p>{{ $t('auth.expiry_notice', { minutes: tokenLifetimeMinutes }) }}</p>
                </div>

                <a class="btn btn-black auth-submit" :href="loginUrl">{{ $t('auth.back') }}</a>

                <p class="auth-switch">
                    {{ $t('auth.no_email') }}
                    <a :href="requestPasswordUrl">{{ $t('auth.retry') }}</a>
                </p>
            </div>
        </section>
    `,
};

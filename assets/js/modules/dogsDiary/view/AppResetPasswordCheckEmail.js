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
                    <p class="auth-eyebrow">Almost there</p>
                    <h1 id="check-email-title">Check your email</h1>
                    <p>If an account matches the email you entered, a password reset link is on its way.</p>
                </div>

                <div class="auth-notice" role="status">
                    <p>The link expires in {{ tokenLifetimeMinutes }} minutes. Check your spam folder if you don't see the message.</p>
                </div>

                <a class="btn btn-black auth-submit" :href="loginUrl">Back to login</a>

                <p class="auth-switch">
                    Didn't receive an email?
                    <a :href="requestPasswordUrl">Try again</a>
                </p>
            </div>
        </section>
    `,
};

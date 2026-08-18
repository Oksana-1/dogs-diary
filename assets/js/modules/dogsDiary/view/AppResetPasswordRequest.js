import { mdiEmailOutline } from '@mdi/js';
import AuthMdiIcon from './ui/AuthMdiIcon.js';

export default {
    name: 'AppResetPasswordRequest',

    components: {
        AuthMdiIcon,
    },

    props: {
        loginUrl: { type: String, required: true },
    },

    setup() {
        return { mdiEmailOutline };
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="reset-request-title">
            <div class="auth-card">
                <a class="auth-back" :href="loginUrl">← Back to login</a>

                <div class="auth-heading">
                    <AuthMdiIcon :path="mdiEmailOutline" />
                    <p class="auth-eyebrow">Password recovery</p>
                    <h1 id="reset-request-title">Forgot password?</h1>
                    <p>Enter the email address connected to your account and we'll send you a reset link.</p>
                </div>

                <form class="auth-form">
                    <div class="auth-field">
                        <label for="reset-email">Email address</label>
                        <input
                            id="reset-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                        >
                    </div>

                    <button class="btn btn-black auth-submit" type="button">Send reset link</button>
                </form>

                <p class="auth-switch">
                    Remembered your password?
                    <a :href="loginUrl">Login</a>
                </p>
            </div>
        </section>
    `,
};

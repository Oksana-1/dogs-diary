import { mdiLockReset } from '@mdi/js';
import AuthMdiIcon from './ui/AuthMdiIcon.js';

export default {
    name: 'AppResetPassword',

    components: {
        AuthMdiIcon,
    },

    props: {
        loginUrl: { type: String, required: true },
    },

    setup() {
        return { mdiLockReset };
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="reset-password-title">
            <div class="auth-card">
                <div class="auth-heading">
                    <AuthMdiIcon :path="mdiLockReset" />
                    <p class="auth-eyebrow">Secure your account</p>
                    <h1 id="reset-password-title">Choose a new password</h1>
                    <p>Create a password you haven't used for this account before.</p>
                </div>

                <form class="auth-form">
                    <div class="auth-field">
                        <label for="reset-password">New password</label>
                        <input
                            id="reset-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Enter a new password"
                        >
                        <p class="auth-help">Use at least 8 characters.</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password-confirmation">Confirm new password</label>
                        <input
                            id="reset-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Repeat your new password"
                        >
                    </div>

                    <button class="btn btn-black auth-submit" type="button">Reset password</button>
                </form>

                <p class="auth-switch">
                    Return to
                    <a :href="loginUrl">login</a>
                </p>
            </div>
        </section>
    `,
};

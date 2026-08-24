import { mdiLockReset } from '@mdi/js';
import AuthMdiIcon from './ui/AuthMdiIcon.js';

export default {
    name: 'AppResetPassword',

    components: {
        AuthMdiIcon,
    },

    props: {
        formAction: { type: String, required: true },
        loginUrl: { type: String, required: true },
        csrfToken: { type: String, required: true },
        errors: { type: Object, default: () => ({}) },
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

                <form class="auth-form" :action="formAction" method="post">
                    <input type="hidden" name="_csrf_token" :value="csrfToken">

                    <div v-if="errors.global" class="auth-errors" role="alert">
                        <p v-for="error in errors.global" :key="error">{{ error }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password">New password</label>
                        <input
                            id="reset-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Enter a new password"
                            required
                            minlength="12"
                            :aria-invalid="errors.password ? 'true' : undefined"
                            :aria-describedby="errors.password ? 'reset-password-error' : undefined"
                        >
                        <p v-if="errors.password" id="reset-password-error" class="auth-field-error" role="alert">
                            {{ errors.password.join(' ') }}
                        </p>
                        <p v-else class="auth-help">Use at least 12 characters.</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password-confirmation">Confirm new password</label>
                        <input
                            id="reset-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Repeat your new password"
                            required
                            minlength="12"
                            :aria-invalid="errors.password_confirmation ? 'true' : undefined"
                            :aria-describedby="errors.password_confirmation ? 'reset-password-confirmation-error' : undefined"
                        >
                        <p v-if="errors.password_confirmation" id="reset-password-confirmation-error" class="auth-field-error" role="alert">
                            {{ errors.password_confirmation.join(' ') }}
                        </p>
                    </div>

                    <button class="btn btn-black auth-submit" type="submit">Reset password</button>
                </form>

                <p class="auth-switch">
                    Return to
                    <a :href="loginUrl">login</a>
                </p>
            </div>
        </section>
    `,
};

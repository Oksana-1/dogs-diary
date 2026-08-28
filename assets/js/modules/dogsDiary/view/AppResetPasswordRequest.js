import { mdiEmailOutline } from '@mdi/js';
import AuthMdiIcon from './ui/AuthMdiIcon.js';

export default {
    name: 'AppResetPasswordRequest',

    components: {
        AuthMdiIcon,
    },

    props: {
        formAction: { type: String, required: true },
        loginUrl: { type: String, required: true },
        csrfToken: { type: String, required: true },
        email: { type: String, default: '' },
        errors: { type: Array, default: () => [] },
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

                <form class="auth-form" :action="formAction" method="post">
                    <input type="hidden" name="_csrf_token" :value="csrfToken">

                    <div v-if="errors.length" id="reset-request-errors" class="auth-errors" role="alert">
                        <p v-for="error in errors" :key="error">{{ error }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-email">Email address</label>
                        <input
                            id="reset-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            :value="email"
                            required
                            autofocus
                            :aria-invalid="errors.length ? 'true' : undefined"
                            :aria-describedby="errors.length ? 'reset-request-errors' : undefined"
                        >
                    </div>

                    <button class="btn btn-black auth-submit" type="submit">Send reset link</button>
                </form>

                <p class="auth-switch">
                    Remembered your password?
                    <a :href="loginUrl">Login</a>
                </p>
            </div>
        </section>
    `,
};

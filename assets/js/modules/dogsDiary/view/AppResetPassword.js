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
                    <p class="auth-eyebrow">{{ $t('auth.secure') }}</p>
                    <h1 id="reset-password-title">{{ $t('auth.choose_password') }}</h1>
                    <p>{{ $t('auth.reset_intro') }}</p>
                </div>

                <form class="auth-form" :action="formAction" method="post">
                    <input type="hidden" name="_csrf_token" :value="csrfToken">

                    <div v-if="errors.global" class="auth-errors" role="alert">
                        <p v-for="error in errors.global" :key="error">{{ error }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password">{{ $t('auth.new_password') }}</label>
                        <input
                            id="reset-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="$t('auth.new_password_placeholder')"
                            required
                            minlength="12"
                            :aria-invalid="errors.password ? 'true' : undefined"
                            :aria-describedby="errors.password ? 'reset-password-error' : 'reset-password-help'"
                        >
                        <p v-if="errors.password" id="reset-password-error" class="auth-field-error" role="alert">
                            {{ errors.password.join(' ') }}
                        </p>
                        <p v-else id="reset-password-help" class="auth-help">{{ $t('auth.password_help') }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password-confirmation">{{ $t('auth.confirm_new') }}</label>
                        <input
                            id="reset-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="$t('auth.repeat_new')"
                            required
                            minlength="12"
                            :aria-invalid="errors.password_confirmation ? 'true' : undefined"
                            :aria-describedby="errors.password_confirmation ? 'reset-password-confirmation-error' : undefined"
                        >
                        <p v-if="errors.password_confirmation" id="reset-password-confirmation-error" class="auth-field-error" role="alert">
                            {{ errors.password_confirmation.join(' ') }}
                        </p>
                    </div>

                    <button class="btn btn-black auth-submit" type="submit">{{ $t('auth.reset') }}</button>
                </form>

                <p class="auth-switch">
                    {{ $t('auth.return') }}
                    <a :href="loginUrl">{{ $t('auth.login_lower') }}</a>
                </p>
            </div>
        </section>
    `,
};

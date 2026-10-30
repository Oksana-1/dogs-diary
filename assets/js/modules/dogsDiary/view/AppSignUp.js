export default {
    name: 'AppSignUp',

    props: {
        formAction: { type: String, required: true },
        loginUrl: { type: String, required: true },
        csrfToken: { type: String, required: true },
        values: { type: Object, default: () => ({}) },
        errors: { type: Object, default: () => ({}) },
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="sign-up-title">
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="auth-icon auth-icon-emoji" aria-hidden="true">🐾</span>
                    <p class="auth-eyebrow">{{ $t('auth.join') }}</p>
                    <h1 id="sign-up-title">{{ $t('auth.signup_title') }}</h1>
                    <p>{{ $t('auth.signup_intro') }}</p>
                </div>

                <form class="auth-form" :action="formAction" method="post">
                    <input type="hidden" name="_csrf_token" :value="csrfToken">

                    <div v-if="errors.global?.length" class="auth-errors" role="alert">
                        <p v-for="error in errors.global" :key="error">{{ error }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-name">{{ $t('auth.name') }}</label>
                        <input
                            id="sign-up-name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            :placeholder="$t('auth.name_placeholder')"
                            :value="values.name"
                            required
                            autofocus
                            :aria-invalid="errors.name?.length ? 'true' : undefined"
                            :aria-describedby="errors.name?.length ? 'sign-up-name-error' : undefined"
                        >
                        <p v-if="errors.name?.length" id="sign-up-name-error" class="auth-field-error">{{ errors.name[0] }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-email">{{ $t('auth.email') }}</label>
                        <input
                            id="sign-up-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            :value="values.email"
                            required
                            :aria-invalid="errors.email?.length ? 'true' : undefined"
                            :aria-describedby="errors.email?.length ? 'sign-up-email-error' : undefined"
                        >
                        <p v-if="errors.email?.length" id="sign-up-email-error" class="auth-field-error">{{ errors.email[0] }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-password">{{ $t('auth.password') }}</label>
                        <input
                            id="sign-up-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="$t('auth.create_password')"
                            required
                            minlength="12"
                            :aria-invalid="errors.password?.length ? 'true' : undefined"
                            :aria-describedby="errors.password?.length ? 'sign-up-password-error' : undefined"
                        >
                        <p v-if="errors.password?.length" id="sign-up-password-error" class="auth-field-error">{{ errors.password[0] }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-password-confirmation">{{ $t('auth.confirm_password') }}</label>
                        <input
                            id="sign-up-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="$t('auth.repeat_password')"
                            required
                            minlength="12"
                            :aria-invalid="errors.password_confirmation?.length ? 'true' : undefined"
                            :aria-describedby="errors.password_confirmation?.length ? 'sign-up-password-confirmation-error' : undefined"
                        >
                        <p v-if="errors.password_confirmation?.length" id="sign-up-password-confirmation-error" class="auth-field-error">{{ errors.password_confirmation[0] }}</p>
                    </div>

                    <label class="auth-check">
                        <input
                            name="terms"
                            type="checkbox"
                            value="1"
                            required
                            :checked="values.terms"
                            :aria-invalid="errors.terms?.length ? 'true' : undefined"
                            :aria-describedby="errors.terms?.length ? 'sign-up-terms-error' : undefined"
                        >
                        <span>{{ $t('auth.terms') }}</span>
                    </label>
                    <p v-if="errors.terms?.length" id="sign-up-terms-error" class="auth-field-error">{{ errors.terms[0] }}</p>

                    <button class="btn btn-black auth-submit" type="submit">{{ $t('auth.create') }}</button>
                </form>

                <p class="auth-switch">
                    {{ $t('auth.existing') }}
                    <a :href="loginUrl">{{ $t('auth.login') }}</a>
                </p>
            </div>
        </section>
    `,
};

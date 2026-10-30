export default {
    name: 'AppLogin',

    props: {
        formAction: { type: String, required: true },
        forgotPasswordUrl: { type: String, required: true },
        signUpUrl: { type: String, required: true },
        csrfToken: { type: String, required: true },
        lastEmail: { type: String, default: '' },
        error: { type: String, default: null },
        notices: { type: Array, default: () => [] },
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="login-title">
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="auth-icon auth-icon-emoji" aria-hidden="true">🐾</span>
                    <p class="auth-eyebrow">{{ $t('auth.welcome') }}</p>
                    <h1 id="login-title">{{ $t('auth.login_title') }}</h1>
                    <p>{{ $t('auth.login_intro') }}</p>
                </div>

                <form class="auth-form" :action="formAction" method="post">
                    <input type="hidden" name="_csrf_token" :value="csrfToken">

                    <div v-if="notices.length" class="auth-notice" role="status">
                        <p v-for="notice in notices" :key="notice">{{ notice }}</p>
                    </div>

                    <div v-if="error" id="login-error" class="auth-errors" role="alert">
                        <p>{{ error }}</p>
                    </div>

                    <div class="auth-field">
                        <label for="login-email">{{ $t('auth.email') }}</label>
                        <input
                            id="login-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            :value="lastEmail"
                            required
                            autofocus
                            :aria-invalid="error ? 'true' : undefined"
                            :aria-describedby="error ? 'login-error' : undefined"
                        >
                    </div>

                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="login-password">{{ $t('auth.password') }}</label>
                            <a :href="forgotPasswordUrl">{{ $t('auth.forgot') }}</a>
                        </div>
                        <input
                            id="login-password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            :placeholder="$t('auth.password_placeholder')"
                            required
                            :aria-invalid="error ? 'true' : undefined"
                            :aria-describedby="error ? 'login-error' : undefined"
                        >
                    </div>

                    <label class="auth-check">
                        <input name="remember_me" type="checkbox" value="1">
                        <span>{{ $t('auth.remember') }}</span>
                    </label>

                    <button class="btn btn-black auth-submit" type="submit">{{ $t('auth.login') }}</button>
                </form>

                <p class="auth-switch">
                    {{ $t('auth.new_here') }}
                    <a :href="signUpUrl">{{ $t('auth.create_account') }}</a>
                </p>
            </div>
        </section>
    `,
};

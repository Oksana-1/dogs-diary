export default {
    name: 'AppLogin',

    props: {
        forgotPasswordUrl: { type: String, required: true },
        signUpUrl: { type: String, required: true },
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="login-title">
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="auth-icon auth-icon-emoji" aria-hidden="true">🐾</span>
                    <p class="auth-eyebrow">Welcome back</p>
                    <h1 id="login-title">Login to Dogs Diary</h1>
                    <p>Keep every walk, treatment, and happy memory close at hand.</p>
                </div>

                <form class="auth-form">
                    <div class="auth-field">
                        <label for="login-email">Email address</label>
                        <input
                            id="login-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                        >
                    </div>

                    <div class="auth-field">
                        <div class="auth-label-row">
                            <label for="login-password">Password</label>
                            <a :href="forgotPasswordUrl">Forgot password?</a>
                        </div>
                        <input
                            id="login-password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                    </div>

                    <label class="auth-check">
                        <input name="remember_me" type="checkbox">
                        <span>Remember me</span>
                    </label>

                    <button class="btn btn-black auth-submit" type="button">Login</button>
                </form>

                <p class="auth-switch">
                    New to Dogs Diary?
                    <a :href="signUpUrl">Create an account</a>
                </p>
            </div>
        </section>
    `,
};

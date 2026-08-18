export default {
    name: 'AppSignUp',

    props: {
        loginUrl: { type: String, required: true },
    },

    template: /*language=HTML*/ `
        <section class="auth-page" aria-labelledby="sign-up-title">
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="auth-icon auth-icon-emoji" aria-hidden="true">🐾</span>
                    <p class="auth-eyebrow">Join the pack</p>
                    <h1 id="sign-up-title">Create your account</h1>
                    <p>Start one simple diary for your dog's health and happiest days.</p>
                </div>

                <form class="auth-form">
                    <div class="auth-field">
                        <label for="sign-up-name">Name</label>
                        <input
                            id="sign-up-name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            placeholder="Your name"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-email">Email address</label>
                        <input
                            id="sign-up-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-password">Password</label>
                        <input
                            id="sign-up-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Create a password"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="sign-up-password-confirmation">Confirm password</label>
                        <input
                            id="sign-up-password-confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Repeat your password"
                        >
                    </div>

                    <label class="auth-check">
                        <input name="terms" type="checkbox">
                        <span>I agree to the Terms of Service and Privacy Policy.</span>
                    </label>

                    <button class="btn btn-black auth-submit" type="button">Create account</button>
                </form>

                <p class="auth-switch">
                    Already have an account?
                    <a :href="loginUrl">Login</a>
                </p>
            </div>
        </section>
    `,
};

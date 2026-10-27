import createSessionExpiryRedirect from '../../../../assets/js/core/auth/SessionExpiryRedirect.js';

const { module, test } = window.QUnit;

module('SessionExpiryRedirect', () => {
    test("redirects concurrent expired-session notifications to login only once", assert => {
        const redirects = [];
        const redirectExpiredSession = createSessionExpiryRedirect("/login", {
            origin: "https://dogs-diary.test",
            replace: (url) => redirects.push(url),
        });

        redirectExpiredSession();
        redirectExpiredSession();

        assert.deepEqual(redirects, [
            "https://dogs-diary.test/login?reason=session_expired",
        ]);
    });
});

export default function createSessionExpiryRedirect(
    loginPath = "/login",
    location = window.location,
) {
    let redirectStarted = false;

    return () => {
        if (redirectStarted) {
            return;
        }

        redirectStarted = true;

        const loginUrl = new URL(loginPath, location.origin);
        loginUrl.searchParams.set("reason", "session_expired");

        location.replace(loginUrl.toString());
    };
}

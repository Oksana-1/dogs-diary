export default {
    name: 'AuthMdiIcon',

    props: {
        path: { type: String, required: true },
    },

    template: /*language=HTML*/ `
        <span class="auth-icon" aria-hidden="true">
            <svg class="auth-icon-svg" viewBox="0 0 24 24" focusable="false">
                <path :d="path"></path>
            </svg>
        </span>
    `,
};

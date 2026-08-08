import { reactive } from 'vue';

export default function useAsyncModal({ fallbackError = 'Please try again.' } = {}) {
    const modal = reactive({
        isOpen: false,
        subject: null,
        isPending: false,
        error: null,

        open(subject = null) {
            modal.subject = subject;
            modal.error = null;
            modal.isOpen = true;
        },

        close() {
            if (modal.isPending) {
                return;
            }

            modal.isOpen = false;
            modal.subject = null;
            modal.error = null;
        },

        async execute(action) {
            if (modal.isPending) {
                return null;
            }

            modal.isPending = true;
            modal.error = null;

            try {
                const result = await action(modal.subject);
                modal.isOpen = false;
                modal.subject = null;

                return result;
            } catch (error) {
                modal.error = error?.message || fallbackError;

                return null;
            } finally {
                modal.isPending = false;
            }
        },
    });

    return modal;
}

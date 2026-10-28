let modalId = 0;
const openDialogs = [];
let scrollLock = null;
const controlSelector = 'a[href], area[href], button, input, select, textarea, [tabindex], [contenteditable="true"]';

function usable(element) {
    return element instanceof HTMLElement && element.isConnected
        && !element.matches(':disabled') && !element.closest('[hidden], [inert]')
        && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';
}

function controls(dialog) {
    return [...dialog.querySelectorAll(controlSelector)].filter(element => usable(element) && element.tabIndex >= 0)
        .sort((a, b) => (a.tabIndex || Infinity) - (b.tabIndex || Infinity));
}

function lockScroll() {
    if (scrollLock) return;
    const properties = [
        [document.documentElement, 'overflow-x', 'hidden'],
        [document.documentElement, 'overflow-y', 'hidden'],
        [document.body, 'position', 'fixed'],
        [document.body, 'top', `${-window.scrollY}px`],
        [document.body, 'left', `${-window.scrollX}px`],
        [document.body, 'width', '100%'],
        [document.body, 'overflow-x', 'hidden'],
        [document.body, 'overflow-y', 'hidden'],
    ];
    scrollLock = {
        x: window.scrollX,
        y: window.scrollY,
        properties: properties.map(([element, name, value]) => {
            const original = [element, name, element.style.getPropertyValue(name), element.style.getPropertyPriority(name)];
            element.style.setProperty(name, value);
            return original;
        }),
    };
}

function unlockScroll() {
    if (openDialogs.length || !scrollLock) return;
    const { x, y, properties } = scrollLock;
    scrollLock = null;
    for (const [element, name, value, priority] of properties) {
        if (value) element.style.setProperty(name, value, priority);
        else element.style.removeProperty(name);
    }
    window.scrollTo({ left: x, top: y, behavior: 'instant' });
}

function focusFallback() {
    const dialog = openDialogs.at(-1);
    if (dialog) {
        (controls(dialog)[0] || dialog.querySelector('h2')).focus({ preventScroll: true });
        return;
    }
    const target = [...document.querySelectorAll('main h1, main button, main a[href]')].find(usable)
        || [...document.querySelectorAll('main, h1')].find(usable) || document.body;
    const tabindex = target.getAttribute('tabindex');
    if (target.tabIndex < 0) target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
    if (tabindex === null) {
        target.addEventListener('blur', () => target.removeAttribute('tabindex'), { once: true });
    }
}

export default {
    name: 'ModalDialog',

    props: {
        title: { type: String, required: true },
        description: { type: String, default: null },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        contentClass: { type: String, default: '' },
    },

    emits: ['close'],

    data() {
        modalId += 1;
        return {
            titleId: `modal-title-${modalId}`,
            descriptionId: `modal-description-${modalId}`,
            backdropPointer: false,
        };
    },

    mounted() {
        this.syncDialog();
    },

    watch: {
        isOpen: { handler: 'syncDialog', flush: 'post' },
    },

    beforeUnmount() {
        this.releaseDialog();
    },

    methods: {
        syncDialog() {
            if (!this.isOpen) {
                this.releaseDialog();
                return;
            }
            const dialog = this.$refs.dialog;
            if (!dialog || this.activeDialog) return;
            this.opener = document.activeElement === document.body ? null : document.activeElement;
            this.activeDialog = dialog;
            lockScroll();
            dialog.showModal();
            openDialogs.push(dialog);
            const initial = [...dialog.querySelectorAll('[autofocus]')].find(usable)
                || controls(dialog)[0] || this.$refs.heading;
            initial.focus({ preventScroll: true });
            this.focusObserver = new MutationObserver(() => this.ensureFocus());
            this.focusObserver.observe(dialog, {
                subtree: true, childList: true, attributes: true,
                attributeFilter: ['disabled', 'hidden', 'style', 'class', 'tabindex', 'type'],
            });
        },

        releaseDialog() {
            const dialog = this.activeDialog;
            if (!dialog) return;
            const wasTopmost = openDialogs.at(-1) === dialog;
            this.focusObserver.disconnect();
            this.activeDialog = null;
            openDialogs.splice(openDialogs.indexOf(dialog), 1);
            dialog.close();
            unlockScroll();
            if (wasTopmost) {
                const top = openDialogs.at(-1);
                if (usable(this.opener) && (!top || top.contains(this.opener))) {
                    this.opener.focus({ preventScroll: true });
                }
                if (!this.opener || document.activeElement !== this.opener) focusFallback();
            }
            this.opener = null;
            this.backdropPointer = false;
        },

        ensureFocus() {
            const dialog = this.activeDialog;
            if (dialog && openDialogs.at(-1) === dialog
                && (!dialog.contains(document.activeElement) || !usable(document.activeElement))) {
                (controls(dialog)[0] || this.$refs.heading).focus({ preventScroll: true });
            }
        },

        trapTab(event) {
            if (event.key !== 'Tab') return;
            const items = controls(this.activeDialog);
            const first = items[0];
            const last = items.at(-1);
            const current = document.activeElement;
            if (!first || !items.includes(current) || (event.shiftKey ? current === first : current === last)) {
                event.preventDefault();
                (event.shiftKey ? last : first)?.focus({ preventScroll: true });
                if (!first) this.$refs.heading.focus({ preventScroll: true });
            }
        },

        close() {
            if (!this.disabled) this.$emit('close');
        },

        backdropClick(event) {
            if (event.target === event.currentTarget && this.backdropPointer) this.close();
            this.backdropPointer = false;
        },
    },

    template: /*language=HTML*/ `
        <Teleport to="body">
            <dialog v-if="isOpen" ref="dialog"
                    class="modal" role="dialog" aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    @cancel.prevent="close"
                    @keydown="trapTab"
                    @pointerdown="backdropPointer = $event.target === $event.currentTarget"
                    @pointerup="backdropPointer = backdropPointer && $event.target === $event.currentTarget"
                    @pointercancel="backdropPointer = false"
                    @click="backdropClick">
                <div :class="['modal-content', contentClass]">
                    <h2 ref="heading" :id="titleId" tabindex="-1">{{ title }}</h2>
                    <p v-if="description" :id="descriptionId">{{ description }}</p>
                    <slot></slot>
                </div>
            </dialog>
        </Teleport>
    `,
};

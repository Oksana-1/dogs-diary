import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog'];

    open() {
        this.dialogTarget.style.display = 'flex';
        // Force reflow
        this.dialogTarget.offsetHeight;
        this.dialogTarget.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.dialogTarget.classList.remove('modal-open');
        setTimeout(() => {
            this.dialogTarget.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 200);
    }

    // Close when clicking on the backdrop
    clickOutside(event) {
        if (event.target === this.dialogTarget) {
            this.close();
        }
    }

    submit(event) {
        event.preventDefault();

        const formData = new FormData(event.target);
        console.log('Form submitted with Stimulus:', Object.fromEntries(formData.entries()));

        this.close();
        event.target.reset();
    }
}

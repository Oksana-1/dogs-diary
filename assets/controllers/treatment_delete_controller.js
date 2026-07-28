import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets = ['dialog'];

    open(event) {
        const button = event.currentTarget;
        const treatmentId = button.dataset.treatmentId;
        const treatmentProduct = button.dataset.treatmentProduct;

        // Update confirmation message
        document.getElementById('delete-treatment-message').textContent =
            `Are you sure you want to delete "${treatmentProduct}"?`;

        // Store treatment ID for delete action
        const deleteButton = document.querySelector('#delete-treatment-modal .btn-danger');
        deleteButton.dataset.treatmentId = treatmentId;

        // Open modal
        this.dialogTarget.style.display = 'flex';
        this.dialogTarget.offsetHeight;
        this.dialogTarget.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }
}

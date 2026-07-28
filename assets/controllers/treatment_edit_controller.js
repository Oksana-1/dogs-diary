import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dialog'];

    open(event) {
        const button = event.currentTarget;
        const treatmentId = button.dataset.treatmentId;
        const treatmentType = button.dataset.treatmentType;
        const treatmentProduct = button.dataset.treatmentProduct;
        const treatmentDate = button.dataset.treatmentDate;
        const treatmentDue = button.dataset.treatmentDue;
        const treatmentNote = button.dataset.treatmentNote;

        // Populate form fields
        document.getElementById('edit-treatment-id').value = treatmentId;
        document.getElementById('edit-treatment-type').value = treatmentType;
        document.getElementById('edit-product-name').value = treatmentProduct;
        document.getElementById('edit-treatment-date').value = treatmentDate;
        document.getElementById('edit-next-due-date').value = treatmentDue || '';
        document.getElementById('edit-notes').value = treatmentNote || '';

        // Open modal
        this.dialogTarget.style.display = 'flex';
        this.dialogTarget.offsetHeight;
        this.dialogTarget.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }
}

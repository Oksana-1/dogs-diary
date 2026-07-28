// Simple modal management for treatments
document.addEventListener('DOMContentLoaded', function() {
    // Edit treatment handlers
    document.querySelectorAll('.action-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const treatmentId = this.dataset.treatmentId;
            const treatmentType = this.dataset.treatmentType;
            const treatmentProduct = this.dataset.treatmentProduct;
            const treatmentDate = this.dataset.treatmentDate;
            const treatmentDue = this.dataset.treatmentDue;
            const treatmentNote = this.dataset.treatmentNote;

            // Populate form fields
            document.getElementById('edit-treatment-id').value = treatmentId;
            document.getElementById('edit-treatment-type').value = treatmentType;
            document.getElementById('edit-product-name').value = treatmentProduct;
            document.getElementById('edit-treatment-date').value = treatmentDate;
            document.getElementById('edit-next-due-date').value = treatmentDue || '';
            document.getElementById('edit-notes').value = treatmentNote || '';

            // Open modal
            const modal = document.getElementById('edit-treatment-modal');
            modal.style.display = 'flex';
            modal.offsetHeight; // Force reflow
            modal.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        });
    });

    // Delete treatment handlers
    document.querySelectorAll('.action-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const treatmentId = this.dataset.treatmentId;
            const treatmentProduct = this.dataset.treatmentProduct;

            // Update confirmation message
            document.getElementById('delete-treatment-message').textContent = 
                `Are you sure you want to delete "${treatmentProduct}"?`;

            // Store treatment ID for delete action
            const deleteBtn = document.querySelector('#delete-treatment-modal .btn-danger');
            deleteBtn.dataset.treatmentId = treatmentId;

            // Open modal
            const modal = document.getElementById('delete-treatment-modal');
            modal.style.display = 'flex';
            modal.offsetHeight; // Force reflow
            modal.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal handlers
    document.querySelectorAll('[data-action*="modal#close"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('modal-open');
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }, 200);
            }
        });
    });

    // Click outside modal to close
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('modal-open');
                setTimeout(() => {
                    this.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }, 200);
            }
        });
    });
});

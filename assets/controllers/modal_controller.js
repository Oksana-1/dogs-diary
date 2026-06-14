import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'dialog',
        'title',
        'id',
        'name',
        'birthDate',
        'status',
        'avatar',
        'weight',
        'height',
    ];

    open() {
        this.dialogTarget.style.display = 'flex';
        // Force reflow
        this.dialogTarget.offsetHeight;
        this.dialogTarget.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    openEdit(event) {
        const params = event.params;

        this.titleTarget.textContent = `Edit ${params.dogName}`;
        this.idTarget.value = params.dogId ?? '';
        this.nameTarget.value = params.dogName ?? '';
        this.birthDateTarget.value = params.dogBirthDate ?? '';
        this.statusTarget.value = params.dogStatus ?? '';
        this.avatarTarget.value = params.dogAvatar ?? '';
        this.weightTarget.value = params.dogWeight ?? '';
        this.heightTarget.value = params.dogHeight ?? '';

        this.open();
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

    async submit(event) {
        event.preventDefault();

        if (!this.hasIdTarget) {
            const formData = new FormData(event.target);
            console.log('Form submitted with Stimulus:', Object.fromEntries(formData.entries()));

            this.close();
            event.target.reset();

            return;
        }

        const saveButton = event.submitter;
        if (saveButton) {
            saveButton.disabled = true;
        }

        try {
            await this.updateDog();
            window.location.reload();
        } catch (error) {
            console.error('Dog update failed:', error);
            alert('Dog update failed. Please check the form and try again.');
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
            }
        }
    }

    async updateDog() {
        const id = this.idTarget.value;
        const response = await fetch(`/api/dogs/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: this.nameTarget.value,
                birthDate: this.birthDateTarget.value,
                status: this.optionalString(this.statusTarget.value),
                avatar: this.optionalString(this.avatarTarget.value),
                weight: this.optionalNumber(this.weightTarget.value),
                height: this.optionalNumber(this.heightTarget.value),
            }),
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        return response.json();
    }

    optionalString(value) {
        const trimmedValue = value.trim();

        return trimmedValue === '' ? null : trimmedValue;
    }

    optionalNumber(value) {
        if (value === '') {
            return null;
        }

        return Number(value);
    }

}

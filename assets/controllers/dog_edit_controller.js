import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'dialog',
        'name',
        'birthDate',
        'adoptDate',
        'status',
        'avatar',
        'weight',
        'height',
    ];

    static values = {
        dogId: Number,
    };

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

    async submit(event) {
        event.preventDefault();

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
        const response = await fetch(`/api/dogs/${this.dogIdValue}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: this.nameTarget.value,
                birthDate: this.birthDateTarget.value,
                adoptDate: this.optionalString(this.adoptDateTarget.value),
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

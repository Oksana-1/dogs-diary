import { computed, reactive, ref } from 'vue';
import useAsyncModal from './useAsyncModal.js';

export default function useDogDetails(dogId, repository) {
    const dog = ref(null);
    const isLoading = ref(false);
    const loadError = ref(null);
    const editModal = useAsyncModal({
        fallbackError: 'Unable to update the dog. Please try again.',
    });
    const deleteModal = useAsyncModal();

    const deleteText = computed(() => {
        if (deleteModal.error) {
            return `Unable to delete this dog: ${deleteModal.error}`;
        }

        const name = deleteModal.subject?.name;

        return name
            ? `Are you sure you want to delete “${name}”?`
            : 'Are you sure you want to delete this dog?';
    });

    async function load() {
        isLoading.value = true;
        loadError.value = null;

        try {
            dog.value = await repository.find(dogId);

            return dog.value;
        } catch (error) {
            loadError.value = error?.message || 'Unable to load the dog. Please try again.';

            return null;
        } finally {
            isLoading.value = false;
        }
    }

    function openEdit() {
        editModal.open(dog.value);
    }

    async function update(data) {
        await editModal.execute(async () => {
            dog.value = await repository.update(dog.value.id, data);
        });
    }

    async function remove() {
        if (!deleteModal.subject) {
            return;
        }

        const deleted = await deleteModal.execute(async (dogToDelete) => {
            await repository.delete(dogToDelete.id);

            return true;
        });

        if (deleted) {
            window.location.assign('/');
        }
    }

    function avatarUrl(avatar) {
        return avatar?.startsWith('images/') ? `/assets/${avatar}` : avatar;
    }

    function formatDate(value, options = { year: 'numeric', month: 'long', day: 'numeric' }) {
        return value ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', options) : '—';
    }

    function formatGender(gender) {
        return gender ? gender.charAt(0).toUpperCase() + gender.slice(1) : '—';
    }

    return reactive({
        dog,
        isLoading,
        loadError,
        editModal,
        deleteModal,
        deleteText,
        load,
        openEdit,
        update,
        remove,
        avatarUrl,
        formatDate,
        formatGender,
    });
}

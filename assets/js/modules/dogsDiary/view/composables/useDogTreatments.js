import { computed, reactive, ref } from 'vue';
import useAsyncModal from './useAsyncModal.js';

export default function useDogTreatments(getDogId, initialTreatments, repository, mediaRepository) {
    const treatments = ref([...(initialTreatments ?? [])]);
    const formModal = useAsyncModal({
        fallbackError: 'Unable to save the treatment. Please try again.',
    });
    const deleteModal = useAsyncModal();

    const deleteText = computed(() => {
        if (deleteModal.error) {
            return `Unable to delete this treatment: ${deleteModal.error}`;
        }

        const productName = deleteModal.subject?.productName;

        return productName
            ? `Are you sure you want to delete “${productName}”?`
            : 'Are you sure you want to delete this treatment?';
    });

    function openCreate() {
        formModal.open();
    }

    function openEdit(treatment) {
        formModal.open(treatment);
    }

    function replace(nextTreatments) {
        treatments.value = [...(nextTreatments ?? [])];
    }

    async function save({ data, photoFile = null, removePhoto = false }) {
        await formModal.execute(async (selectedTreatment) => {
            let treatment;

            if (selectedTreatment) {
                treatment = await repository.update(
                    getDogId(),
                    selectedTreatment.id,
                    data,
                );
            } else {
                treatment = await repository.create(getDogId(), data);

                // If the following photo upload fails, keep the newly created
                // treatment as the modal subject so retrying performs an update
                // instead of creating a duplicate treatment.
                formModal.subject = treatment;
                treatments.value = [treatment, ...treatments.value];
            }

            if (photoFile) {
                treatment.photo = await mediaRepository.upload(
                    getDogId(),
                    treatment.id,
                    photoFile,
                );
            } else if (removePhoto && treatment.photo) {
                await mediaRepository.delete(getDogId(), treatment.id, treatment.photo.id);
                treatment.photo = null;
            }

            treatments.value = treatments.value.map(
                (current) => current.id === treatment.id ? treatment : current,
            );

            return treatment;
        });
    }

    async function remove() {
        if (!deleteModal.subject) {
            return;
        }

        await deleteModal.execute(async (treatment) => {
            await repository.delete(getDogId(), treatment.id);
            treatments.value = treatments.value.filter((current) => current.id !== treatment.id);
        });
    }

    return reactive({
        treatments,
        formModal,
        deleteModal,
        deleteText,
        openCreate,
        openEdit,
        replace,
        save,
        remove,
    });
}

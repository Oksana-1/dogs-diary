import { computed, reactive, ref } from 'vue';
import useAsyncModal from './useAsyncModal.js';

export default function useDogMedia(dogId, repository, getDog) {
    const media = ref([]);
    const isLoading = ref(false);
    const isUploading = ref(false);
    const pendingAction = ref(null);
    const error = ref(null);
    const status = ref('');
    const deleteModal = useAsyncModal({
        fallbackError: 'Unable to delete this media item. Please try again.',
    });

    const deleteText = computed(() => {
        if (deleteModal.error) {
            return `Unable to delete this media item: ${deleteModal.error}`;
        }

        const item = deleteModal.subject;
        if (!item) {
            return 'Are you sure you want to delete this media item?';
        }

        const roles = [];
        if (item.isThumbnail) roles.push('thumbnail');
        if (item.isProfile) roles.push('profile media');

        const roleWarning = roles.length
            ? ` It is currently selected as ${roles.join(' and ')}; deleting it will clear ${roles.length === 1 ? 'that role' : 'those roles'}.`
            : '';

        return `Delete “${item.originalName || 'this media item'}”?${roleWarning}`;
    });

    function messageFor(requestError, fallback) {
        return requestError?.message || fallback;
    }

    function updateDogRole(role, item) {
        const dog = getDog?.();
        if (dog) {
            dog[role] = item;
        }
    }

    function replaceRole(flag, selected) {
        media.value = media.value.map(item => {
            item[flag] = selected ? item.id === selected.id : false;
            return item;
        });
    }

    async function load() {
        isLoading.value = true;
        error.value = null;
        status.value = '';

        try {
            media.value = await repository.list(dogId);
        } catch (requestError) {
            error.value = messageFor(requestError, 'Unable to load the media library. Please try again.');
        } finally {
            isLoading.value = false;
        }
    }

    async function upload(file) {
        if (!file || isUploading.value || pendingAction.value) return;

        isUploading.value = true;
        error.value = null;
        status.value = '';

        try {
            const uploaded = await repository.upload(dogId, file);
            media.value = [uploaded, ...media.value];
            status.value = `Uploaded ${uploaded.originalName || 'media'}.`;
        } catch (requestError) {
            error.value = messageFor(requestError, 'Unable to upload this file. Please try again.');
        } finally {
            isUploading.value = false;
        }
    }

    async function mutate(action, operation) {
        if (pendingAction.value || isUploading.value) return;

        pendingAction.value = action;
        error.value = null;
        status.value = '';
        try {
            return await operation();
        } catch (requestError) {
            error.value = messageFor(requestError, 'Unable to update the media library. Please try again.');
            return null;
        } finally {
            pendingAction.value = null;
        }
    }

    async function setThumbnail(item) {
        const selected = await mutate(`thumbnail-${item.id}`, () => repository.setThumbnail(dogId, item.id));
        if (selected) {
            replaceRole('isThumbnail', selected);
            updateDogRole('thumbnail', selected);
            status.value = 'Thumbnail updated.';
        }
    }

    async function clearThumbnail() {
        const succeeded = await mutate('clear-thumbnail', async () => {
            await repository.clearThumbnail(dogId);
            return true;
        });
        if (succeeded) {
            replaceRole('isThumbnail', null);
            updateDogRole('thumbnail', null);
            status.value = 'Thumbnail cleared.';
        }
    }

    async function setProfile(item) {
        const selected = await mutate(`profile-${item.id}`, () => repository.setProfile(dogId, item.id));
        if (selected) {
            replaceRole('isProfile', selected);
            updateDogRole('profileMedia', selected);
            status.value = 'Profile media updated.';
        }
    }

    async function clearProfile() {
        const succeeded = await mutate('clear-profile', async () => {
            await repository.clearProfile(dogId);
            return true;
        });
        if (succeeded) {
            replaceRole('isProfile', null);
            updateDogRole('profileMedia', null);
            status.value = 'Profile media cleared.';
        }
    }

    async function remove() {
        const item = deleteModal.subject;
        if (!item) return;

        const deleted = await deleteModal.execute(async () => {
            await repository.delete(dogId, item.id);
            return true;
        });

        if (deleted) {
            media.value = media.value.filter(candidate => candidate.id !== item.id);
            if (item.isThumbnail) updateDogRole('thumbnail', null);
            if (item.isProfile) updateDogRole('profileMedia', null);
            error.value = null;
            status.value = 'Media deleted.';
        }
    }

    return reactive({
        media,
        isLoading,
        isUploading,
        pendingAction,
        error,
        status,
        deleteModal,
        deleteText,
        load,
        upload,
        setThumbnail,
        clearThumbnail,
        setProfile,
        clearProfile,
        remove,
    });
}

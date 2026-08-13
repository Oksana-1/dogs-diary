import ConfirmModal from '../../ui/modals/ConfirmModal.js';
import { mdiUpload } from '@mdi/js';

let inputId = 0;

export default {
    name: 'DogMediaLibrary',

    components: { ConfirmModal },

    props: {
        dogName: { type: String, default: '' },
        state: { type: Object, required: true },
    },

    data() {
        inputId += 1;
        return {
            fileInputId: `dog-media-upload-${inputId}`,
            mdiUpload,
        };
    },

    methods: {
        async selectFile(event) {
            const input = event.target;
            const file = input.files?.[0];
            if (file) await this.state.upload(file);
            input.value = '';
        },

        formatSize(bytes) {
            if (!Number.isFinite(Number(bytes))) return '';
            if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        },
    },

    template: /*language=HTML*/ `
        <section class="dog-media-library" aria-labelledby="dog-media-library-title">
            <div class="dog-media-library-heading">
                <div>
                    <h2 id="dog-media-library-title" class="h2">Media library</h2>
                    <p>Upload a photo or video, then choose how it appears for {{ dogName || 'this dog' }}.</p>
                </div>
                <div class="dog-media-upload media-upload-control">
                    <label :for="fileInputId" class="btn btn-black media-upload-button">
                        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path :d="mdiUpload"></path>
                        </svg>
                        <span>{{ state.isUploading ? 'Uploading…' : 'Upload media' }}</span>
                    </label>
                    <input :id="fileInputId" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                           :disabled="state.isUploading || Boolean(state.pendingAction)" @change="selectFile">
                </div>
            </div>

            <p class="dog-media-help">JPEG, PNG, or WebP up to 10 MB; MP4 or WebM up to 100 MB.</p>
            <div class="dog-media-status" aria-live="polite">
                <p v-if="state.error" class="dog-media-error" role="alert">{{ state.error }}</p>
                <p v-else-if="state.isUploading">Uploading media…</p>
                <p v-else-if="state.isLoading">Loading media…</p>
                <p v-else-if="state.status">{{ state.status }}</p>
            </div>

            <div v-if="!state.isLoading && state.media.length" class="dog-media-grid">
                <article v-for="item in state.media" :key="item.id" class="dog-media-card"
                         :class="{ 'is-pending': state.pendingAction?.endsWith('-' + item.id) }">
                    <div class="dog-media-preview">
                        <img v-if="item.type === 'image'" :src="item.url" alt="" loading="lazy">
                        <video v-else :src="item.url" preload="metadata" muted playsinline
                               :aria-label="'Video preview: ' + item.originalName"></video>
                        <div v-if="item.isThumbnail || item.isProfile" class="dog-media-badges">
                            <span v-if="item.isThumbnail" class="dog-media-badge">✓ Thumbnail</span>
                            <span v-if="item.isProfile" class="dog-media-badge">✓ Profile</span>
                        </div>
                    </div>
                    <div class="dog-media-card-content">
                        <p class="dog-media-name" :title="item.originalName">{{ item.originalName }}</p>
                        <p class="dog-media-meta">{{ item.type === 'image' ? 'Image' : 'Video' }} · {{ formatSize(item.sizeBytes) }}</p>
                        <div class="dog-media-actions">
                            <button v-if="item.type === 'image' && !item.isThumbnail" type="button" class="btn btn-white"
                                    :disabled="state.isUploading || Boolean(state.pendingAction)" @click="state.setThumbnail(item)">
                                Set thumbnail
                            </button>
                            <button v-else-if="item.isThumbnail" type="button" class="btn btn-white"
                                    :disabled="state.isUploading || Boolean(state.pendingAction)" @click="state.clearThumbnail">
                                Clear thumbnail
                            </button>
                            <button v-if="!item.isProfile" type="button" class="btn btn-white"
                                    :disabled="state.isUploading || Boolean(state.pendingAction)" @click="state.setProfile(item)">
                                Set as profile
                            </button>
                            <button v-else type="button" class="btn btn-white"
                                    :disabled="state.isUploading || Boolean(state.pendingAction)" @click="state.clearProfile">
                                Clear profile
                            </button>
                            <button type="button" class="btn btn-white dog-media-delete"
                                    :disabled="state.isUploading || Boolean(state.pendingAction)" @click="state.deleteModal.open(item)">
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <p v-else-if="!state.isLoading && !state.error" class="dog-media-empty">
                No media yet. Upload the first photo or video.
            </p>

            <ConfirmModal title="Delete media" :text="state.deleteText"
                          :is-open="state.deleteModal.isOpen" :disabled="state.deleteModal.isPending"
                          @confirm="state.remove" @close="state.deleteModal.close" />
        </section>
    `,
};

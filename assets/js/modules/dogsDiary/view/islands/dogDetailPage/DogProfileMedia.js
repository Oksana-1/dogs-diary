export default {
    name: 'DogProfileMedia',

    props: {
        media: { type: Object, default: null },
        dogName: { type: String, default: '' },
    },

    data() {
        return { failed: false };
    },

    watch: {
        media() {
            this.failed = false;
        },
    },

    template: /*language=HTML*/ `
        <div class="dog-profile-media">
            <img
                v-if="media?.type === 'image' && !failed"
                class="dog-profile-media-element"
                :src="media.url"
                :alt="'Profile photo of ' + (dogName || 'the dog')"
                @error="failed = true"
            >
            <video
                v-else-if="media?.type === 'video' && !failed"
                class="dog-profile-media-element"
                :src="media.url"
                :aria-label="'Profile video of ' + (dogName || 'the dog')"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                @error="failed = true"
            ></video>
            <div v-else class="image-placeholder dog-profile-media-placeholder" role="img"
                 :aria-label="'No profile media for ' + (dogName || 'this dog')"></div>
        </div>
    `,
};

import Dog from "../../../modules/dogsDiary/entities/Dog.js";

export default {
    name: 'DogItem',
    props: {
        dog: Dog,
    },
    data() {
        return {
            avatarFailed: false,
        };
    },
    methods: {
        avatarUrl(avatar) {
            if (avatar.startsWith('/') || avatar.startsWith('http://') || avatar.startsWith('https://')) {
                return avatar;
            }

            return avatar.startsWith('images/') ? `/assets/${avatar}` : avatar;
        },
    },
    template: `
        <div class="dog-card dog-card-row">
            <a :href="'/dog/' + dog.id" class="dog-card-link dog-card-main">
            <div class="dog-avatar-column">
                <div class="dog-avatar">
                    <img v-if="dog.avatar && !avatarFailed"
                         :src="avatarUrl(dog.avatar)"
                         :alt="dog.name"
                         @error="avatarFailed = true" />
                    <div v-else class="image-placeholder" aria-hidden="true"></div>
                </div>
            </div>
        <div class="dog-info">
            <div class="dog-info-header">
                <h2 class="dog-card-title">{{ dog.name }}</h2>
            </div>
            <span class="breed-tag">{{ dog.status ?? 'No status' }}</span>
            <p class="dog-card-meta">
                Born: {{ dog.birthDate ? dog.birthDate : 'Unknown' }}<br>
                Gender {{ dog.gender || 'Unknown' }}<br></br>
                Adopted: {{ dog.adoptDate ? dog.adoptDate : 'Unknown' }}<br>
                Weight: {{ dog.weight ?? 'Unknown' }} kg
            </p>
        </div>
    </a>
</div>
`
}

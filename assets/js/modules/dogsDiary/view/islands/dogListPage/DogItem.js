import Dog from "../../../entities/Dog.js";
import { formatDate } from '../../../../../utils/helpers.js';

export default {
    name: 'DogItem',
    props: {
        dog: Dog,
    },
    methods: {
        formatDate,
    },
    data() {
        return {
            thumbnailFailed: false,
        };
    },
    template: `
        <div class="dog-card dog-card-row">
            <a :href="'/dog/' + dog.id" class="dog-card-link dog-card-main">
            <div class="dog-thumbnail-column">
                <div class="dog-thumbnail">
                    <img v-if="dog.thumbnail?.url && !thumbnailFailed"
                         :src="dog.thumbnail.url"
                         :alt="'Photo of ' + (dog.name || 'dog')"
                         @error="thumbnailFailed = true" />
                    <div v-else class="image-placeholder" aria-hidden="true"></div>
                </div>
            </div>
        <div class="dog-info">
            <div class="dog-info-header">
                <h2 class="dog-card-title">{{ dog.name }}</h2>
            </div>
            <span class="breed-tag">{{ dog.status ?? 'No status' }}</span>
            <p class="dog-card-meta">
                Born: {{ formatDate(dog.birthDate) }}<br>
                Gender {{ dog.gender || 'Unknown' }}<br></br>
                Adopted: {{ formatDate(dog.adoptDate) }}<br>
                Weight: {{ dog.weight ?? 'Unknown' }} kg
            </p>
        </div>
    </a>
</div>
`
}

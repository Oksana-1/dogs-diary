import Dog from "../../../js/modules/dogsDiary/entities/Dog.js";

export default {
    name: 'DogItem',
    props: {
        dog: Dog
    },
    template: `
        <div class="dog-card dog-card-row">
            <a href="#" class="dog-card-link dog-card-main">
            <div class="dog-avatar-column">
                <div class="dog-avatar">
                    <img src="/assets/images/dogAvatarPlaceholder.jpg" :alt="dog.name" class="image-placeholder" />
                </div>
            </div>
        <div class="dog-info">
            <div class="dog-info-header">
                <h2 class="dog-card-title">{{ dog.name }}</h2>
            </div>
            <span class="breed-tag">{{ dog.status ?? 'No status' }}</span>
            <p class="dog-card-meta">
                Born: {{ dog.birthDate ? dog.birthDate : 'Unknown' }}<br>
                Adopted: {{ dog.adoptDate ? dog.adoptDate : 'Unknown' }}<br>
                Weight: {{ dog.weight ?? 'Unknown' }} kg
            </p>
        </div>
    </a>
</div>
`
}

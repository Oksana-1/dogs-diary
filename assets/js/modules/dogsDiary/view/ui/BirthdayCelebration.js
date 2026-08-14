import { computed } from 'vue';

const COLORS = ['#ff6b6b', '#ffd166', '#06d6a0', '#4dabf7', '#9b5de5', '#f15bb5'];

const confetti = Array.from({ length: 30 }, (_, index) => ({
    id: index,
    style: {
        '--birthday-left': `${(index * 37 + 7) % 100}%`,
        '--birthday-delay': `${-((index * 0.47) % 7).toFixed(2)}s`,
        '--birthday-duration': `${5 + (index % 5) * 0.7}s`,
        '--birthday-color': COLORS[index % COLORS.length],
        '--birthday-drift': `${((index % 7) - 3) * 14}px`,
        '--birthday-rotation': `${540 + (index % 4) * 180}deg`,
    },
}));

function isBirthdayToday(birthDate, today = new Date()) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(birthDate ?? '');

    return Boolean(match)
        && Number(match[2]) === today.getMonth() + 1
        && Number(match[3]) === today.getDate();
}

export default {
    name: 'BirthdayCelebration',

    props: {
        birthDate: { type: String, default: null },
        dogsName: { type: String, default: "" },
    },

    setup(props) {
        const isBirthday = computed(() => isBirthdayToday(props.birthDate));
        return { confetti, isBirthday };
    },

    template: /*language=HTML*/ `
        <div v-if="isBirthday" class="birthday-celebration">
            <div class="birthday-confetti" aria-hidden="true">
                <span
                    v-for="piece in confetti"
                    :key="piece.id"
                    class="birthday-confetti-piece"
                    :style="piece.style"
                ></span>
            </div>
            <div class="birthday-card" role="status">
                <span class="birthday-card-icons" aria-hidden="true">🎉</span>
                <div>
                    <p class="birthday-card-kicker">Today is the big day!</p>
                    <p class="birthday-card-title">Happy birthday, {{ dogsName }}!</p>
                </div>
            </div>
        </div>
    `,
};

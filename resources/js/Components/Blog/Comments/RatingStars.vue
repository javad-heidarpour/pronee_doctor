<template>
    <div
        class="flex items-center gap-1"
        :class="center ? 'justify-center' : ''"
    >
        <svg
            v-for="star in 5"
            :key="star"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            class="w-5 h-5 transition"
            :class="star <= rating ? activeClass : inactiveClass"
            fill="currentColor"
        >
            <path
                d="M12 2l2.94 6.28 6.93.63-5.23 4.57 1.56 6.78L12 16.9l-6.2 3.36 1.56-6.78L2.13 8.91l6.93-.63L12 2z"
            />
        </svg>

        <span
            v-if="showNumber"
            class="mr-2 text-sm font-bold text-gray-600"
        >
            {{ rating.toFixed(1) }}
        </span>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    rating: {
        type: Number,
        default: 0,
    },

    center: {
        type: Boolean,
        default: false,
    },

    showNumber: {
        type: Boolean,
        default: false,
    },

    activeColor: {
        type: String,
        default: "text-yellow-400",
    },

    inactiveColor: {
        type: String,
        default: "text-gray-300",
    },
});

const activeClass = computed(() => props.activeColor);

const inactiveClass = computed(() => props.inactiveColor);
</script>
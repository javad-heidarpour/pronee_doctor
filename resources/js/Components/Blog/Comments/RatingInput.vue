<template>
    <div class="space-y-3">
        <label class="block text-sm font-black text-gray-700">
            امتیاز شما به این مقاله
        </label>

        <div class="flex items-center gap-2" dir="ltr">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="setRating(star)"
                class="transition hover:scale-110"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="w-9 h-9"
                    :class="
                        star <= modelValue
                            ? 'text-yellow-400'
                            : 'text-gray-300'
                    "
                    fill="currentColor"
                >
                    <path
                        d="M12 2l2.94 6.28 6.93.63-5.23 4.57 1.56 6.78L12 16.9l-6.2 3.36 1.56-6.78L2.13 8.91l6.93-.63L12 2z"
                    />
                </svg>
            </button>
        </div>

        <p
            v-if="modelValue"
            class="text-sm text-yellow-600 font-bold"
        >
            {{ labels[modelValue] }}
        </p>
    </div>
</template>

<script setup>

const props = defineProps({
    modelValue: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits([
    "update:modelValue",
]);

const labels = {
    1: "خیلی ضعیف",
    2: "ضعیف",
    3: "متوسط",
    4: "خوب",
    5: "عالی",
};


const setRating = (value) => {

    emit(
        "update:modelValue",
        value
    );

};

</script>
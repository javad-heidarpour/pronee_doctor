<template>
  <div>
    <label class="block text-sm font-bold text-gray-700 mb-3"> امتیاز شما </label>

    <div class="flex items-center gap-2">
      <button
        v-for="star in 5"
        :key="star"
        type="button"
        @click="select(star)"
        @mouseenter="hover = star"
        @mouseleave="hover = 0"
        class="transition duration-150"
      >
        <svg
          class="w-9 h-9"
          :class="(hover || modelValue) >= star ? 'text-amber-400' : 'text-gray-300'"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
          />
        </svg>
      </button>

      <button
        v-if="modelValue"
        type="button"
        @click="clear"
        class="mr-3 text-sm text-red-500 hover:text-red-700"
      >
        حذف امتیاز
      </button>
    </div>

    <p v-if="modelValue" class="text-xs text-gray-500 mt-2">
      {{ labels[modelValue] }}
    </p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(["update:modelValue"]);

const hover = ref(0);

const labels = {
  1: "خیلی ضعیف",

  2: "ضعیف",

  3: "متوسط",

  4: "خوب",

  5: "عالی",
};

const select = (value) => {
  emit("update:modelValue", value);
};

const clear = () => {
  emit("update:modelValue", 0);
};
</script>

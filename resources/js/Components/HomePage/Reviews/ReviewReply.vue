<template>
  <div v-if="reply">
    <!-- دکمه‌ی نمایش/مخفی کردن پاسخ -->
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="mt-3 mr-8 flex items-center gap-2 text-sm font-medium text-teal-700 hover:text-teal-800 transition-colors"
    >
      <span
        class="w-6 h-6 rounded-full bg-teal-600 text-white flex items-center justify-center text-xs"
      >
        د
      </span>
      پاسخ {{ doctor_name }}
      <svg
        class="w-4 h-4 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- محتوای پاسخ (فقط وقتی isOpen باز باشه نمایش داده می‌شه) -->
    <div
      v-if="isOpen"
      class="mt-3 mr-8 bg-teal-50 border border-teal-100 rounded-2xl p-5"
    >
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <!-- <div
            class="w-9 h-9 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold"
          >
            د
          </div> -->

          <div>
            <!-- <h4 class="font-bold text-teal-800">
              پاسخ {{ doctor_name }}
            </h4> -->

            <p v-if="replyDate" class="text-xs text-teal-600 mt-1">
              {{ replyDate }}
            </p>
          </div>
        </div>

        <span class="px-3 py-1 rounded-full bg-teal-600 text-white text-xs">
          پاسخ رسمی
        </span>
      </div>

      <div class="leading-8 text-gray-700 whitespace-pre-line">
        {{ reply }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

defineProps({
  reply: {
    type: String,
    default: "",
  },

  replyDate: {
    type: String,
    default: "",
  },

  doctor_name: {
    type: String,
    default: "",
  },
});

const isOpen = ref(false);
</script>
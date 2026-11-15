<template>
  <div
    dir="rtl"
    class="fixed bottom-0 inset-x-0 z-[100] px-3 pt-3 pb-[calc(12px+env(safe-area-inset-bottom))]"
  >
    <div class="max-w-xl mx-auto grid grid-cols-3 items-center gap-3">
      <!-- تصویر پزشک -->

      <a href="/">
        <div class="flex justify-center items-center">
          <CircleDoctorIMG :ImgPath="ImgPath" :doctor="doctor" />
        </div>
      </a>

      <!-- نوبت آنلاین -->
      <button
        type="button"
        @click="selectSection('booking')"
        class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 bg-teal-600 hover:bg-teal-700 active:scale-95 text-white rounded-2xl py-3 px-2 font-bold text-xs sm:text-sm shadow-md shadow-teal-600/20 transition-all duration-200"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <rect x="3" y="5" width="18" height="16" rx="2" />
          <path stroke-linecap="round" d="M16 3v4M8 3v4M3 11h18" />
        </svg>

        <span>نوبت آنلاین</span>
      </button>

      <!-- نوبت حضوری -->
      <button
        type="button"
        @click="selectSection('location')"
        class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-2xl py-3 px-2 font-bold text-xs sm:text-sm shadow-md shadow-blue-600/20 transition-all duration-200"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8 9h.01M8 12h.01M8 15h.01M8 18h.01"
          />
        </svg>

        <span>نوبت حضوری</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import CircleDoctorIMG from "../HomePage/CircleDoctorIMG.vue";

defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  ImgPath: {
    type: String,
    default: "",
  },
  activeSection: {
    type: String,
    default: "all",
  },
});

const emit = defineEmits(["select-section"]);

function selectSection(section) {
  emit("select-section", section);
}
</script>

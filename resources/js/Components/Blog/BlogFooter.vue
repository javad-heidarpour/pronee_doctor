<template>
  <transition
    enter-active-class="transition duration-300"
    enter-from-class="translate-y-full"
    enter-to-class="translate-y-0"
  >
    <div
      class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-lg border-t shadow-2xl"
    >
      <div
        class="max-w-7xl mx-auto px-3 md:px-4 py-1.5 md:py-3 flex flex-col md:flex-row items-center justify-between gap-2 md:gap-4"
      >
        <!-- Right -->
        <div class="flex items-center gap-2 md:gap-4">
          <div
            class="w-9 h-9 md:w-14 md:h-14 rounded-full bg-teal-100 flex items-center justify-center overflow-hidden shrink-0"
          >
            <img
              :src="doctorImage"
              :alt="`${post.doctor.caption} ${post.doctor.name} ${post.doctor.family}`"
              class="w-full h-full object-cover"
            />
          </div>

          <div class="min-w-0">
            <div
              class="font-bold md:font-black text-gray-900 text-xs md:text-lg truncate"
            >
              {{ post.doctor.name }} {{ post.doctor.family }}
            </div>

            <div class="text-[10px] md:text-sm text-gray-500 truncate">
              {{ post.doctor.specialty?.title }}
            </div>
          </div>
        </div>

        <!-- Center -->
        <div class="hidden lg:flex items-center gap-8 text-gray-600">
          <div class="flex items-center gap-2">
            ⭐
            <span class="font-bold">
              {{ post.doctor.average_rating || 5 }}
            </span>
          </div>

          <div class="flex items-center gap-2">⚡ پاسخگویی سریع</div>

          <div class="flex items-center gap-2">📅 رزرو آنلاین</div>
        </div>

        <!-- Left -->
        <div class="flex items-center gap-2 w-full md:w-auto">
          <a
            :href="route('booking')"
            class="flex-1 md:flex-none bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-bold md:font-black text-xs md:text-base px-3 md:px-8 py-2 md:py-4 rounded-xl md:rounded-2xl shadow-lg md:shadow-xl transition hover:scale-105 text-center whitespace-nowrap"
          >
            📅 نوبت آنلاین
          </a>
          <a
            :href="route('home')"
            class="flex-1 md:flex-none bg-white border-2 border-teal-600 hover:bg-teal-50 text-teal-700 font-bold md:font-black text-xs md:text-base px-3 md:px-8 py-2 md:py-4 rounded-xl md:rounded-2xl shadow-sm transition hover:scale-105 text-center whitespace-nowrap"
          >
            👨‍⚕️ پروفایل
          </a>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const STORAGE = "/storage/";

const DEFAULT_DOCTOR_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },

  STORAGE: {
    type: String,
    default: () => [],
  },
});

/**
 * تصویر پزشک
 */
const doctorImage = computed(() => {
  const photo = props.post?.doctor?.photo;

  if (!photo) {
    return DEFAULT_DOCTOR_IMAGE;
  }

  if (photo.startsWith("http") || photo.startsWith("/")) {
    return photo;
  }

  return STORAGE + "doctors/" + photo;
});
</script>

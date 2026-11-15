<template>
  <div
    class="max-w-2xl mx-auto bg-white rounded-3xl shadow-xl border border-gray-100 p-10 text-center"
  >
    <!-- Icon -->
    <div
      class="w-24 h-24 mx-auto rounded-full bg-rose-100 flex items-center justify-center text-5xl"
    >
      🏷️
    </div>

    <h2 class="mt-8 text-3xl font-black text-gray-800">مقاله‌ای پیدا نشد</h2>

    <p class="mt-4 text-lg text-gray-500 leading-9">
      متأسفانه هنوز مقاله‌ای با تگ
      <span class="font-black text-rose-600">
        {{ slug }}
      </span>
      منتشر نشده است.
    </p>

    <!-- Doctor Card -->

    <div
      class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-5 bg-gray-50 rounded-2xl border border-gray-200 p-5"
    >
      <img
        :src="doctorImage"
        :alt="`${doctor.name} ${doctor.family}`"
        class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg"
      />

      <div class="text-center sm:text-right">
        <div class="text-sm text-gray-500">منتشرکننده مقالات</div>

        <h3 class="text-2xl font-black text-teal-700 mt-1">
          {{ doctor.caption }}
          {{ doctor.name }}
          {{ doctor.family }}
        </h3>

        <p class="text-gray-500 mt-2">
          این پزشک تاکنون مقاله‌ای با این تگ منتشر نکرده است.
        </p>
      </div>
    </div>

    <!-- Buttons -->

    <div class="flex flex-col sm:flex-row justify-center gap-4 mt-10">
      <Link
        :href="`/${doctor.slug}/blog`"
        class="bg-teal-600 hover:bg-teal-700 text-white font-black px-8 py-4 rounded-2xl transition shadow-lg"
      >
        📚 مشاهده همه مقالات پزشک
      </Link>

      <Link
        :href="`/${doctor.slug}`"
        class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-black px-8 py-4 rounded-2xl transition"
      >
        👨‍⚕️ بازگشت به پروفایل پزشک
      </Link>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import {  Link } from "@inertiajs/vue3";

const STORAGE = "/storage/";
const DEFAULT_DOCTOR_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";
const DEFAULT_POST_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const props = defineProps({
  doctor: Object,
  tag: Object,
  slug: String,
  posts: Object,
  doctorImage: String,
});
</script>

<template>
  <div
    class="bg-white rounded-3xl border border-gray-100 p-6 shadow-sm hover:shadow-lg transition-all duration-300"
  >
    <!-- Header -->

    <div class="flex justify-between items-start">
      <div class="flex items-center gap-4">
        <div
          class="w-14 h-14 rounded-full flex items-center justify-center text-white font-black text-lg"
          :class="avatarColor"
        >
          {{ initials }}
        </div>

        <div>
          <h3 class="font-bold text-gray-900">
            {{ review.patient_name }}
          </h3>

          <p class="text-xs text-gray-500 mt-1">
            {{ review.persian_date }}
          </p>
        </div>
      </div>

      <RatingStars :rating="review.rating" />
    </div>

    <!-- Comment -->

    <div class="mt-5 leading-8 text-gray-700 text-justify whitespace-pre-line">
      {{ review.comment }}
    </div>

    <!-- Reply -->

    <ReviewReply :doctor_name="review.doctor_name" :reply="review.doctor_reply" :reply-date="review.reply_date" />
  </div>
</template>

<script setup>
import { computed } from "vue";

import RatingStars from "./RatingStars.vue";
import ReviewReply from "./ReviewReply.vue";

const props = defineProps({
  review: {
    type: Object,
    required: true,
  },
});

const colors = [
  "bg-teal-500",

  "bg-cyan-500",

  "bg-indigo-500",

  "bg-purple-500",

  "bg-pink-500",

  "bg-green-500",

  "bg-amber-500",

  "bg-red-500",
];

const avatarColor = computed(() => {
  const name = props.review.patient_name || "A";

  return colors[name.charCodeAt(0) % colors.length];
});

const initials = computed(() => {
  const name = props.review.patient_name;

  if (!name) {
    return "?";
  }

  if (name === "بیمار ناشناس") {
    return "?";
  }

  const parts = name.split(" ");

  if (parts.length >= 2) {
    return parts[0][0] + parts[1][0];
  }

  return parts[0][0];
});
</script>

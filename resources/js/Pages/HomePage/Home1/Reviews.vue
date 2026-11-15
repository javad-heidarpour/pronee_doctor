<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import ReviewsSection from "@/Components/HomePage/Reviews/ReviewsSection.vue";
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  Reviews: {
    type: Object,
    required: false,
  },

  reviewsStats: {
    type: Object,
    required: false,
  },
});
</script>

<template>
  <PublicPageLayout :doctor="doctor" title="نظرات بیماران" active-item="Reviews">
    <ReviewsSection
      :doctor="doctor"
      :reviews="Reviews"
      :stats="reviewsStats"
    />
  </PublicPageLayout>
</template>

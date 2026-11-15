<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import AboutDoctor from "@/Components/HomePage/AboutDoctor.vue";
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
  <PublicPageLayout :doctor="doctor" title="درباره دکتر فرهاد وفایی" active-item="Reviews">
    <AboutDoctor :doctor="doctor" />
  </PublicPageLayout>
</template>

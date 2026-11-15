<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import BlogSection from "@/Components/HomePage/BlogSection.vue";
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  blog: {
    type: Object,
    required: false,
  },
});
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    title="دانستنی‌های قبل و بعد از عمل"
    active-item="tips"
  >
    <BlogSection :doctor="doctor" :blog="blog" />
  </PublicPageLayout>
</template>

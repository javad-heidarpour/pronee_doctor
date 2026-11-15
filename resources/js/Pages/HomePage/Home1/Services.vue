<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import ServicesDetails from "./Services-Details.vue";
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  services: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    title="خدمات قابل ارائه در مطب"
    subtitle="آشنایی با خدمات تخصصی، مراقبت‌های پزشکی و امکانات درمانی"
    eyebrow="خدمات تخصصی پزشکی"
    active-item="services"
  >
    <ServicesDetails :services="services" />
  </PublicPageLayout>
</template>

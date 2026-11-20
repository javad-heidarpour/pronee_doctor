<script setup>
import { computed } from "vue";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import ServicesDetails from "./Services-Details.vue";

const props = defineProps({
  doctor: { type: Object, required: true },
  services: { type: Array, default: () => [] },
});

const doctorName = computed(() =>
  [props.doctor?.name, props.doctor?.family].filter(Boolean).join(" ") || "پزشک"
);
const pageTitle = computed(() => `خدمات قابل ارائه در مطب | ${doctorName.value}`);
const pageDescription = computed(() =>
  `آشنایی با خدمات پزشکی و درمانی مطب ${doctorName.value} و دریافت نوبت آنلاین.`
);
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    :title="pageTitle"
    :subtitle="pageDescription"
    eyebrow="خدمات تخصصی پزشکی"
    active-item="services"
  >
    <ServicesDetails :doctor="doctor" :services="services" />
  </PublicPageLayout>
</template>

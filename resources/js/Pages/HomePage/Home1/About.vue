<script setup>
import { computed } from "vue";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import AboutDoctor from "@/Components/HomePage/AboutDoctor.vue";

const props = defineProps({
  doctor: { type: Object, required: true },
});

const doctorName = computed(() =>
  [props.doctor?.name, props.doctor?.family].filter(Boolean).join(" ") || "پزشک"
);
const pageTitle = computed(() => `درباره ${doctorName.value}`);
const pageDescription = computed(() =>
  `معرفی، تخصص و سوابق حرفه‌ای ${doctorName.value}.`
);
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    :title="pageTitle"
    :subtitle="pageDescription"
    active-item="About"
  >
    <AboutDoctor :doctor="doctor" />
  </PublicPageLayout>
</template>

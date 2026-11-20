<script setup>
import { computed } from "vue";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import ClinicLocation from "@/Components/HomePage/ClinicLocation.vue";

const props = defineProps({
  doctor: { type: Object, required: true },
});

const doctorName = computed(() =>
  [props.doctor?.name, props.doctor?.family].filter(Boolean).join(" ") || "پزشک"
);
const pageTitle = computed(() => `اطلاعات تماس و نشانی مطب | ${doctorName.value}`);
const pageDescription = computed(() =>
  `نشانی، شماره تماس و موقعیت شعب فعال ${doctorName.value}.`
);
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    :title="pageTitle"
    :subtitle="pageDescription"
    active-item="contact"
  >
    <ClinicLocation :doctor="doctor" />
  </PublicPageLayout>
</template>

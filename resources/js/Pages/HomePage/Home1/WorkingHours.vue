<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import WorkingHoursPanel from "@/Components/HomePage/Booking/WorkingHoursPanel.vue";
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  working_hours: {
    type: Object,
    required: false,
  },
});
</script>

<template>
  <PublicPageLayout :doctor="doctor" title="ساعت کاری مطب" active-item="Reviews">
    <WorkingHoursPanel  :working_hours="working_hours" />
  </PublicPageLayout>
</template>

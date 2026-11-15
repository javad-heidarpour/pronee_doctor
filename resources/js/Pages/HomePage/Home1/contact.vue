<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import ClinicLocation from "@/Components/HomePage/ClinicLocation.vue"
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
});
</script>

<template>


  <PublicPageLayout
    :doctor="doctor"
    title="تماس با ما"
    active-item="contact"
  >
  <ClinicLocation :doctor="doctor" />
  </PublicPageLayout>
</template>

<template>
  <div
    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300"
  >
      <!-- :href="`/${doctor.slug}/tag/${specialty.name}`" -->

    <Link
      v-for="(specialty, index) in doctor.specialty"
      :key="specialty.id || index"
      class="group hover:bg-white hover:shadow-xl transition-all duration-300 p-5 block inline-flex items-center gap-1 px-3 py-1 rounded-lg text-sm font-medium"
    >
      <!-- <span
        > -->
      {{ specialty.icon }} {{ specialty.name }}
      <!-- </span> -->
    </Link>
  </div>

</template>
<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});
</script>

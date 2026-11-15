<!-- resources/js/Components/Doctor/QuickAction.vue -->
<template>
  <Link
    :href="href"
    class="flex flex-col items-center gap-2 p-4 bg-gray-50 hover:bg-teal-50 rounded-2xl transition-all group"
  >
    <span class="text-3xl group-hover:scale-110 transition-transform">{{ icon }}</span>
    <span class="text-sm font-medium text-gray-700 group-hover:text-teal-700">{{ label }}</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ href: String, label: String, icon: String });
</script>
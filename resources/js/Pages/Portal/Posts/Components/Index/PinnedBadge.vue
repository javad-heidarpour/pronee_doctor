<template>
  <span v-if="show" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-white/90 backdrop-blur text-amber-600 shadow-md">
    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.73 5.42a1 1 0 01-.289 1.05A3.982 3.982 0 0114 15v2a1 1 0 11-2 0v-2a3.982 3.982 0 01-1.655-3.233l-1.73-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L6 6.323V3a1 1 0 011-1z"/></svg>
    پین
  </span>
</template>

<script setup>
defineProps({ show: { type: Boolean, default: false } })
</script>
<!-- resources/js/Components/Doctor/StatCard.vue -->
<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg transition-shadow">
    <div class="flex items-center justify-between mb-3">
      <div :class="`w-12 h-12 rounded-xl bg-${color}-50 flex items-center justify-center text-2xl`">
        {{ icon }}
      </div>
    </div>
    <p class="text-3xl font-black text-gray-900 mb-1">{{ value }}</p>
    <p class="text-sm text-gray-500">{{ label }}</p>
  </div>
</template>

<script setup>
defineProps({
  icon: String,
  label: String,
  value: [Number, String],
  color: { type: String, default: 'teal' },
});
</script>
<template>
  <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shadow-md', isPublished ? 'bg-green-500 text-white' : 'bg-slate-500 text-white']">
    {{ isPublished ? 'منتشر شده' : 'پیش‌نویس' }}
  </span>
</template>

<script setup>
defineProps({ isPublished: { type: Boolean, default: false } })
</script>   
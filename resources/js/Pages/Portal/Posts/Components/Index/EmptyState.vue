<template>
  <div :class="['py-16 text-center', className]">
    <svg class="w-16 h-16 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
    </svg>
    <p class="mt-4 text-slate-500">{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'مقاله‌ای یافت نشد' },
  className: { type: String, default: 'bg-white rounded-2xl border border-slate-100' }
})
</script>
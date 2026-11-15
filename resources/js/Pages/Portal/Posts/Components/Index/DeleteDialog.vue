<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    @click.self="$emit('update:show', false)"
  >
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in duration-200">
      <div class="flex items-center gap-4 mb-4">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-slate-900">حذف مقاله</h3>
          <p class="text-sm text-slate-600 mt-1">آیا از حذف این مقاله اطمینان دارید؟</p>
        </div>
      </div>
      <p class="text-sm text-slate-700 mb-6 bg-slate-50 rounded-lg p-3">
        «<span class="font-semibold">{{ post?.title }}</span>» برای همیشه حذف خواهد شد.
      </p>
      <div class="flex gap-3">
        <button
          @click="$emit('update:show', false)"
          class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50 transition font-medium"
        >
          انصراف
        </button>
        <button
          @click="$emit('confirm')"
          class="flex-1 px-4 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:shadow-lg transition font-medium"
        >
          حذف قطعی
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  post: { type: Object, default: null }
})
defineEmits(['update:show', 'confirm'])
</script>

<style scoped>
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in { from { transform: scale(0.95); } to { transform: scale(1); } }
.animate-in { animation: fade-in 0.2s ease-out, zoom-in 0.2s ease-out; }
</style>
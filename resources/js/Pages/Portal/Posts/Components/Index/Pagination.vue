<template>
  <div v-if="totalPages > 1" class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
    <p class="text-sm text-slate-600">
      نمایش <span class="font-semibold">{{ startIndex + 1 }}</span> تا
      <span class="font-semibold">{{ Math.min(endIndex, totalFiltered) }}</span>
      از <span class="font-semibold">{{ totalFiltered }}</span> مقاله
    </p>
    <div class="flex items-center gap-1">
      <button
        @click="$emit('update:currentPage', Math.max(1, currentPage - 1))"
        :disabled="currentPage === 1"
        class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
      </button>
      <button
        v-for="page in visiblePages"
        :key="page"
        @click="$emit('update:currentPage', page)"
        :class="[
          'w-10 h-10 rounded-lg text-sm font-medium transition',
          currentPage === page
            ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md'
            : 'border border-slate-200 hover:bg-slate-50 text-slate-700'
        ]"
      >
        {{ page }}
      </button>
      <button
        @click="$emit('update:currentPage', Math.min(totalPages, currentPage + 1))"
        :disabled="currentPage === totalPages"
        class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  currentPage: { type: Number, required: true },
  totalPages: { type: Number, required: true },
  startIndex: { type: Number, required: true },
  endIndex: { type: Number, required: true },
  totalFiltered: { type: Number, required: true },
  visiblePages: { type: Array, required: true }
})
defineEmits(['update:currentPage'])
</script>
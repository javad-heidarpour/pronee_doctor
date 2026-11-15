<template>

  <div :class="['rounded-xl overflow-hidden bg-gradient-to-br from-indigo-100 to-purple-100 flex-shrink-0', size === 'large' ? 'w-full h-48' : 'w-14 h-14']">
    <img
      v-if="src"
      :src="src"
      :alt="alt"
      :class="['w-full h-full object-cover', size === 'large' ? 'group-hover:scale-105 transition-transform duration-500' : '']"
    />
    <div v-else class="w-full h-full flex items-center justify-center">
      <svg :class="['text-indigo-400', size === 'large' ? 'w-16 h-16 text-indigo-300' : 'w-6 h-6']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
  </div>
</template>

<script setup>
defineProps({
  src: { type: String, default: '' },
  alt: { type: String, default: '' },
  size: { type: String, default: 'small' } // 'small' or 'large'
})
</script>
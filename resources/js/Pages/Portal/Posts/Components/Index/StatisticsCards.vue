<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- کل مقالات -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-slate-600 text-sm">کل مقالات</p>
          <p class="text-3xl font-bold text-slate-900 mt-1">{{ stats.total }}</p>
          <p class="text-xs text-slate-500 mt-1">+{{ stats.totalGrowth }}% این ماه</p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        </div>
      </div>
    </div>
    <!-- منتشر شده -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-slate-600 text-sm">منتشر شده</p>
          <p class="text-3xl font-bold text-green-600 mt-1">{{ stats.published }}</p>
          <p class="text-xs text-slate-500 mt-1">فعال در سایت</p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
      </div>
    </div>
    <!-- مقالات ویژه -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-slate-600 text-sm">مقالات ویژه</p>
          <p class="text-3xl font-bold text-amber-600 mt-1">{{ stats.featured }}</p>
          <p class="text-xs text-slate-500 mt-1">در صفحه اصلی</p>
        </div>
        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
        </div>
      </div>
    </div>
    <!-- کل بازدیدها -->
    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-slate-600 text-sm">کل بازدیدها</p>
          <p class="text-3xl font-bold text-purple-600 mt-1">{{ formatNumber(stats.totalViews) }}</p>
          <p class="text-xs text-slate-500 mt-1">مجموع کل</p>
        </div>
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: { type: Object, required: true }
})

const formatNumber = (num) => {
  if (num >= 1000) return (num / 1000).toFixed(1) + 'k'
  return num.toString()
}
</script>
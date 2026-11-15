<template>
  <Head title="صفحه یافت نشد" />

  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 py-12">
    <div class="max-w-2xl w-full text-center">
      <!-- آیکون و عدد 404 -->
      <div class="relative inline-block mb-8">
        <div class="text-[150px] md:text-[200px] font-black text-indigo-600 leading-none select-none">
          404
        </div>
        <div class="absolute inset-0 text-[150px] md:text-[200px] font-black text-indigo-200 leading-none select-none blur-sm">
          404
        </div>
      </div>

      <!-- عنوان و توضیحات -->
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
        صفحه مورد نظر یافت نشد!
      </h1>
      <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
        متأسفانه صفحه‌ای که به دنبال آن می‌گردید وجود ندارد یا آدرس آن تغییر کرده است.
      </p>

      <!-- دکمه‌های عملیات -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
        <Link
          href="/"
          class="w-full sm:w-auto px-8 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <span class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            بازگشت به صفحه اصلی
          </span>
        </Link>

        <button
          @click="goBack"
          class="w-full sm:w-auto px-8 py-3 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50 transition-all duration-200"
        >
          <span class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            صفحه قبل
          </span>
        </button>
      </div>

      <!-- لینک‌های پیشنهادی -->
            <PorneexWidget />

    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PorneexWidget from "@/Components/PorneexWidget.vue";

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back()
  } else {
    window.location.href = '/'
  }
}
</script>
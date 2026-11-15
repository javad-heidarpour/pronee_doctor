<template>
  <Head title="خطای سرور" />

  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-pink-100 px-4 py-12"
  >
    <div class="max-w-2xl w-full text-center">
      <!-- آیکون خطا -->
      <div class="mb-8">
        <div
          class="inline-flex items-center justify-center w-32 h-32 bg-red-100 rounded-full mb-4 animate-pulse"
        >
          <svg
            class="w-16 h-16 text-red-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
        <div
          class="text-[100px] md:text-[120px] font-black text-red-600 leading-none"
        >
          500
        </div>
      </div>

      <!-- عنوان و توضیحات -->
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
        خطای داخلی سرور!
      </h1>
      <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
        متأسفانه مشکلی در سرور رخ داده است. تیم فنی ما از این موضوع آگاه شده و
        در حال رفع آن است.
      </p>

      <!-- دکمه‌های عملیات -->
      <div
        class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8"
      >
        <Link
          href="/"
          class="w-full sm:w-auto px-8 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <span class="flex items-center justify-center gap-2">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
              />
            </svg>
            بازگشت به صفحه اصلی
          </span>
        </Link>

        <button
          @click="reloadPage"
          class="w-full sm:w-auto px-8 py-3 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:border-gray-400 hover:bg-gray-50 transition-all duration-200"
        >
          <span class="flex items-center justify-center gap-2">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            تلاش مجدد
          </span>
        </button>
      </div>

      <!-- اطلاعات بیشتر -->
      <div class="mt-8 p-6 bg-white rounded-lg shadow-md max-w-md mx-auto">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">
          چه کاری می‌توانید انجام دهید؟
        </h3>
        <ul class="text-sm text-gray-600 text-right space-y-2">
          <li class="flex items-start gap-2">
            <span class="text-red-500 mt-1">•</span>
            <span>چند دقیقه دیگر دوباره تلاش کنید</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-red-500 mt-1">•</span>
            <span>اگر مشکل ادامه داشت، صفحه را رفرش کنید</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-red-500 mt-1">•</span>
            <span>در صورت تداوم مشکل، با پشتیبانی تماس بگیرید</span>
          </li>
        </ul>
      </div>

      <!-- کد خطا (اختیاری - فقط در حالت توسعه) -->
      <div
        v-if="errorCode"
        class="mt-8 p-4 bg-gray-100 rounded-lg max-w-md mx-auto"
      >
        <p class="text-xs text-gray-500 font-mono">
          Error Code: {{ errorCode }}
        </p>
      </div>
      <PorneexWidget />
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PorneexWidget from "@/Components/PorneexWidget.vue";

const props = defineProps({
  errorCode: {
    type: String,
    default: null,
  },
});

const reloadPage = () => {
  window.location.reload();
};
</script>
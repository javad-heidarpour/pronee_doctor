<template>
  <Head title="پزشک مورد نظر یافت نشد" />

  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-blue-100 px-4 py-12"
  >
    <div class="max-w-2xl w-full text-center">
      <!-- آیکون پزشک -->
      <div class="mb-8">
        <div
          class="inline-flex items-center justify-center w-32 h-32 bg-blue-100 rounded-full mb-4"
        >
          <svg
            class="w-16 h-16 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M12 2a3 3 0 00-3 3v3a3 3 0 006 0V5a3 3 0 00-3-3zM5 10v2a7 7 0 0014 0v-2M12 19v3m-4 0h8"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M19 5l2 2-2 2"
            />
          </svg>
        </div>

        <div class="text-[100px] md:text-[120px] font-black text-blue-600 leading-none">
          404
        </div>
      </div>

      <!-- عنوان و توضیحات -->
      <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
        پزشک مورد نظر یافت نشد!
      </h1>

      <p class="text-lg text-gray-600 mb-4 max-w-lg mx-auto leading-9">
        متأسفانه پزشک مورد نظر شما در سامانه پرونیکس ثبت نشده است یا صفحه اختصاصی ایشان در
        دسترس نیست.
      </p>

      <p class="text-sm text-gray-500 mb-8">
        لطفاً از صحت آدرس وارد شده اطمینان حاصل کنید.
      </p>

      <!-- دکمه‌های عملیات -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8">
        <Link
          href="/"
          class="w-full sm:w-auto px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <span class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"
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
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

      <!-- راهنمای کاربر -->
      <div class="mt-8 p-6 bg-white rounded-lg shadow-md max-w-md mx-auto">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">
          چه کاری می‌توانید انجام دهید؟
        </h3>

        <ul class="text-sm text-gray-600 text-right space-y-3">
          <li class="flex items-start gap-2">
            <span class="text-blue-500 mt-1">•</span>
            <span>آدرس صفحه پزشک را بررسی کنید</span>
          </li>

          <li class="flex items-start gap-2">
            <span class="text-blue-500 mt-1">•</span>
            <span>به صفحه اصلی سامانه بازگردید</span>
          </li>

          <li class="flex items-start gap-2">
            <span class="text-blue-500 mt-1">•</span>
            <span>در صورت نیاز با پشتیبانی پرونیکس تماس بگیرید</span>
          </li>
        </ul>
      </div>

      <PorneexWidget />
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PorneexWidget from "@/Components/PorneexWidget.vue";

const reloadPage = () => {
  window.location.reload();
};
</script>

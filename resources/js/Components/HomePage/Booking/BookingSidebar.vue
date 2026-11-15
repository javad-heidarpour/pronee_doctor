<template>
  <!-- Booking Card -->
  <div
    class="bg-gradient-to-br from-teal-500 to-cyan-600 rounded-3xl shadow-xl shadow-teal-200/50 p-6 text-white sticky"
  >
    <h3 class="text-lg font-bold mb-2">دریافت نوبت</h3>
    <p class="text-teal-50 text-sm mb-6">
      برای ویزیت حضوری یا آنلاین، همین حالا نوبت خود را رزرو کنید.
    </p>

    <!-- ✅ دکمه اول: لینک به بخش نوبت‌دهی (ظاهر مشابه دکمه دوم) -->
    <a
      :href="route('booking')"
      class="w-full bg-rose-200 text-rose-800 font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 mb-3 text-center block"
    >
      رزرو نوبت آنلاین
    </a>

    <a
      href="#location"
      class="w-full bg-white text-teal-600 font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 mb-3 text-center block"
    >
      رزرو نوبت حضوری
    </a>
    <!-- ✅ دکمه سوم: مشاوره آنلاین -->
    <button
      class="w-full bg-teal-700/30 backdrop-blur text-white font-bold py-3.5 rounded-2xl border border-white/20 hover:bg-teal-700/50 transition-all duration-200"
    >
      مشاوره آنلاین (ویدئوکال)
    </button>

    <!-- Satisfaction Ring -->
    <div
      class="mt-8 flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-2xl p-4"
    >
      <div class="relative w-16 h-16 flex-shrink-0">
        <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
          <path
            class="text-white/20"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
          />
          <path
            class="text-white"
            :stroke-dasharray="`${doctor.satisfaction_rate}, 100`"
            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
          />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold">
          {{ formattedSatisfaction }}%
        </span>
      </div>
      <div>
        <p class="font-bold text-sm">میزان رضایت</p>
        <p class="text-teal-100 text-xs mt-1">بر اساس نظرات بیماران قبلی</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const formattedSatisfaction = computed(() => {
  const rate = parseFloat(props.doctor.satisfaction_rate) || 0;
  return rate.toFixed(0);
});
</script>

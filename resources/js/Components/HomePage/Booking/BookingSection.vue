<template>
  <div
    v-if="doctor.clinics_with_slots && doctor.clinics_with_slots.length > 0"
    id="BookingSection"
    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 hover:shadow-lg transition-shadow duration-300"
  >
    <div class="flex items-center gap-3 mb-6">
      <div class="p-2 bg-green-50 rounded-xl">
        <svg
          class="w-6 h-6 text-green-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900">انتخاب نوبت ویزیت</h2>
    </div>

    <!-- 1. Clinic Tabs -->
    <!-- انتخاب مطب -->

    <div
      v-if="doctor.clinics_with_slots?.length"
      class="w-full max-w-xl mx-auto mb-6 px-3"
    >
      <p class="text-center text-gray-700 font-bold text-base mb-4">
        <span class="text-teal-600">●</span>
        لطفاً مطب موردنظر خود را انتخاب کنید
      </p>

      <div
        v-if="nearestAvailable && !isClinicSelected"
        class="mb-5 flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-gradient-to-l from-emerald-50 via-white to-teal-50 p-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-xl shadow-sm">✨</span>
          <div>
            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-extrabold text-emerald-800">پیشنهاد پرونیکس</span>
            <p class="mt-2 text-sm font-extrabold text-slate-900">نزدیک‌ترین نوبت آزاد</p>
            <p class="mt-1 text-xs leading-6 text-slate-600">
              {{ nearestAvailable.clinic.name }} ·
              {{ getPersianDayName(nearestAvailable.date) }}
              {{ getPersianDayNumber(nearestAvailable.date) }}
              {{ getPersianMonthName(nearestAvailable.date) }}
              · ساعت {{ formatTime(nearestAvailable.slot.start_time) }}
            </p>
          </div>
        </div>
        <button
          type="button"
          @click="selectRecommendedSlot"
          class="shrink-0 rounded-xl bg-emerald-700 px-4 py-3 text-xs font-extrabold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-500/20"
        >
          انتخاب این نوبت
        </button>
      </div>

      <div class="flex flex-col items-center gap-3">
        <button
          v-for="(clinicData, index) in doctor.clinics_with_slots"
          :key="index"
          v-show="selectedClinicIndex === null || selectedClinicIndex === index"
          @click="selectClinic(index)"
          :disabled="isClinicSelected"
          :class="[
            'w-full max-w-md flex items-center justify-between gap-3',
            'px-5 py-4 rounded-2xl border-2 text-right',
            'transition-all duration-300',
            selectedClinicIndex === index
              ? 'bg-teal-50 border-teal-500 text-teal-800 shadow-md'
              : 'bg-white border-gray-200 text-gray-700 hover:border-teal-400 hover:bg-teal-50 hover:shadow-sm',
            isClinicSelected ? 'cursor-default' : 'cursor-pointer active:scale-[0.98]',
          ]"
        >
          <div class="flex items-center gap-3 min-w-0">
            <span
              class="flex items-center justify-center w-11 h-11 shrink-0 rounded-xl"
              :class="
                selectedClinicIndex === index
                  ? 'bg-teal-600 text-white'
                  : 'bg-teal-50 text-teal-600'
              "
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01M15 12v.01M15 15v.01M15 18v.01"
                />
              </svg>
            </span>

            <span class="flex flex-col gap-1 min-w-0">
              <span class="text-xs text-gray-500">
                {{
                  selectedClinicIndex === index
                    ? "مطب انتخاب‌شده"
                    : "برای انتخاب کلیک کنید"
                }}
              </span>

              <span class="font-bold text-sm sm:text-base whitespace-normal">
                {{ clinicData.clinic.name }}
              </span>
            </span>
          </div>

          <span
            class="shrink-0 flex items-center justify-center w-7 h-7 rounded-full"
            :class="
              selectedClinicIndex === index
                ? 'bg-teal-600 text-white'
                : 'border-2 border-gray-300 text-transparent'
            "
          >
            <svg
              v-if="selectedClinicIndex === index"
              xmlns="http://www.w3.org/2000/svg"
              class="w-4 h-4"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M16.704 5.29a1 1 0 010 1.415l-7.1 7.1a1 1 0 01-1.415 0l-3.6-3.6a1 1 0 011.415-1.415l2.893 2.893 6.392-6.393a1 1 0 011.415 0z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </button>
      </div>

      <button
        v-if="selectedClinicIndex !== null && !isClinicSelected"
        type="button"
        @click="selectedClinicIndex = null"
        class="block mx-auto mt-3 text-sm text-teal-700 hover:text-teal-900 font-medium underline underline-offset-4"
      >
        تغییر مطب
      </button>
    </div>
    <!-- 2. Date Strip -->
    <div v-if="isClinicSelected" class="mb-6">
      <h3 class="text-sm font-bold text-gray-500 mb-3">انتخاب روز ویزیت:</h3>

      <div class="relative group">
        <!-- دکمه اسکرول به راست -->
        <button
          v-show="canScrollDateRight"
          @click="scrollDates('right')"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-teal-600 hover:bg-teal-50 transition-all opacity-0 group-hover:opacity-100 md:opacity-100"
          aria-label="اسکرول به راست"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>

        <!-- نوار تاریخ‌ها -->
        <div
          ref="dateStripRef"
          @scroll="checkDateScrollPosition"
          class="flex gap-3 overflow-x-auto pb-2 scroll-smooth snap-x snap-mandatory hide-scrollbar"
          style="scrollbar-width: none; -ms-overflow-style: none"
        >
          <button
            v-for="(dateData, dIndex) in currentClinicData.dates"
            :key="dIndex"
            @click="
              selectedDateIndex = dIndex;
              selectedSlot = null;
              availabilityMessage = "";
            "
            :class="[
              'flex flex-col items-center min-w-[80px] p-3 m-1 rounded-2xl border-2 transition-all duration-200 snap-center flex-shrink-0',
              selectedDateIndex === dIndex
                ? 'border-teal-500 bg-teal-50 text-teal-700 shadow-sm scale-105'
                : 'border-gray-100 bg-white text-gray-600 hover:border-gray-200',
            ]"
          >
            <span class="text-xs font-medium">
              {{ getPersianDayName(dateData.date) }}
            </span>
            <span class="text-2xl font-black my-1">
              {{ getPersianDayNumber(dateData.date) }}
            </span>
            <span class="text-[10px] text-gray-400">
              {{ getPersianMonthName(dateData.date) }}
            </span>
          </button>
        </div>

        <!-- دکمه اسکرول به چپ -->
        <button
          v-show="canScrollDateLeft"
          @click="scrollDates('left')"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-teal-600 hover:bg-teal-50 transition-all opacity-0 group-hover:opacity-100 md:opacity-100"
          aria-label="اسکرول به چپ"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- 3. Time Slots Strip -->
    <div v-if="isClinicSelected">
      <h3 class="text-sm font-bold text-gray-500 mb-3">ساعات موجود:</h3>

      <div v-if="currentDateData?.slots?.length > 0" class="relative group">
        <!-- نوار ساعت‌ها -->
        <div
          ref="timeStripRef"
          @scroll="checkTimeScrollPosition"
          dir="rtl"
          class="flex gap-3 overflow-x-auto pb-2 px-12 scroll-smooth snap-x snap-mandatory hide-scrollbar"
          style="scrollbar-width: none; -ms-overflow-style: none"
        >
          <button
            v-for="slot in currentDateData.slots"
            :key="slot.id"
            @click="selectSlot(slot)"
            :disabled="slot.status !== 'available'"
            dir="ltr"
            :class="[
              'min-w-[90px] py-3 px-4 m-1 rounded-2xl text-sm font-bold transition-all duration-200 border-2 snap-center flex-shrink-0',
              selectedSlot?.id === slot.id
                ? 'bg-teal-600 text-white border-teal-600 shadow-lg shadow-teal-200 scale-105 ring-2 ring-teal-300'
                : slot.status === 'available'
                ? 'bg-white text-teal-700 border-teal-200 hover:bg-teal-50 hover:border-teal-400 hover:scale-105 cursor-pointer'
                : 'bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed',
            ]"
          >
            <div class="flex flex-col items-center">
              <span class="text-base font-black">
                {{ slot.start_time.substr(0, 5) }}
              </span>

              <span
                v-if="slot.status === 'booked'"
                class="text-[10px] mt-1 text-gray-400"
              >
                رزرو شده
              </span>
            </div>
          </button>
        </div>
      </div>

      <!-- پیام نبود نوبت -->
      <div
        v-else
        class="text-center py-10 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200"
      >
        <svg
          class="w-12 h-12 text-gray-300 mx-auto mb-3"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>

        <p class="text-gray-500 font-medium">برای این روز نوبت خالی وجود ندارد.</p>

        <p class="text-xs text-gray-400 mt-1">لطفاً روز دیگری را انتخاب کنید.</p>
      </div>
    </div>

    <!-- 4. Action Button (کامپوننت جداگانه) -->
    <BookingSectionActionButton
     :visitReasons="doctor.visitReasons"
      :selected-slot="selectedSlot"
      :current-date-data="currentDateData"
      @success="handleBookingSuccess"
      @slot-cleared="handleSlotCleared"
      @slot-booked="handleSlotBooked"
    />
  </div>
  <!-- اطلاعات مطب انتخاب‌شده -->
  <div
    v-if="isClinicSelected && selectedClinic"
    class="mt-5 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
    dir="rtl"
  >
    <div class="mb-4 flex items-start gap-3">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.8"
            d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.828 0l-4.243-4.243a8 8 0 1 1 11.314 0Z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.8"
            d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
          />
        </svg>
      </div>

      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-gray-800">
          {{ selectedClinic.name }}
        </h3>

        <p v-if="selectedClinic.city?.name" class="mt-1 text-sm text-gray-500">
          {{ selectedClinic.city.name }}
        </p>

        <p class="mt-2 text-sm leading-6 text-gray-600">
          {{ selectedClinic.full_address }}
        </p>

        <a
          v-if="selectedClinic.phone"
          :href="`tel:${selectedClinic.phone}`"
          class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-blue-600"
        >
          <span>{{ selectedClinic.phone }}</span>
        </a>
      </div>
    </div>

    <!-- نقشه -->
    <div
      v-if="selectedClinic.Latitude && selectedClinic.Longitude"
      class="overflow-hidden rounded-xl border border-gray-100"
    >
      <iframe
        :src="getMapUrl(selectedClinic.Latitude, selectedClinic.Longitude)"
        title="موقعیت مطب روی نقشه"
        class="h-56 w-full"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
      ></iframe>
    </div>

    <!-- لینک‌های مسیریابی -->
    <div
      v-if="selectedClinic.Latitude && selectedClinic.Longitude"
      class="mt-3 flex flex-wrap gap-2"
    >
      <a
        :href="getGoogleMapsLink(selectedClinic.Latitude, selectedClinic.Longitude)"
        target="_blank"
        rel="noopener noreferrer"
        class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
      >
        مسیریابی گوگل
      </a>

      <a
        :href="getOSMLink(selectedClinic.Latitude, selectedClinic.Longitude)"
        target="_blank"
        rel="noopener noreferrer"
        class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-200"
      >
        نقشه OpenStreetMap
      </a>

      <a
        :href="getNeshanUrl(selectedClinic.Latitude, selectedClinic.Longitude)"
        target="_blank"
        rel="noopener noreferrer"
        class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100"
      >
        نقشه نشان
      </a>
    </div>
  </div>
  <div
    v-if="!doctor.clinics_with_slots || doctor.clinics_with_slots.length === 0"
    id="BookingSection"
    class="m-10 bg-gradient-to-br from-amber-50 to-orange-50 rounded-3xl shadow-sm border-2 border-dashed border-amber-200 p-8 md:p-10 hover:shadow-lg hover:border-amber-300 transition-all duration-300"
  >
    <div class="flex flex-col items-center text-center">
      <!-- آیکون -->
      <div class="relative mb-6">
        <div
          class="absolute inset-0 bg-amber-200 rounded-full blur-xl opacity-50 animate-pulse"
        ></div>
        <div class="relative p-4 bg-white rounded-2xl shadow-lg border border-amber-100">
          <svg
            class="w-12 h-12 text-amber-500"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
      </div>

      <!-- عنوان -->
      <h3 class="text-lg font-bold text-gray-800 mb-2">نوبتی ثبت نشده است</h3>

      <!-- توضیحات -->
      <span class="text-sm text-gray-600 max-w-md leading-relaxed">
        پزشک محترم هنوز شیفت کاری برای این بازه زمانی تعریف نکرده است.
      </span>
      <span
        class="text-md text-gray-600 max-w-md leading-relaxed rounded border px-2 text-white bg-amber-500"
      >
        و البته امکان دارد نوبت ها همه رزرو شده باشند
      </span>
      <span class="text-sm text-gray-600 max-w-md leading-relaxed">
        لطفاً در روزهای آینده مجدداً بررسی فرمایید.
      </span>
      <!-- دکمه پیشنهادی (اختیاری) -->
      <div class="mt-6 flex items-center gap-2 text-xs text-gray-500">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>می‌توانید بعداً مجدداً مراجعه کنید</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, nextTick, watch } from "vue";
import BookingSectionActionButton from "./BookingSectionActionButton.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

// --- BOOKING STATE ---
const selectedClinicIndex = ref(null);
const selectedDateIndex = ref(0);
const selectedSlot = ref(null);
const availabilityMessage = ref("");

// آیا مطب انتخاب شده است؟
const isClinicSelected = ref(false);

// انتخاب مطب
const selectClinic = (index) => {
  if (isClinicSelected.value) return;

  selectedClinicIndex.value = index;
  selectedDateIndex.value = 0;
  selectedSlot.value = null;
  availabilityMessage.value = "";

  const clinicData = props.doctor.clinics_with_slots?.[index];
  // پیدا کردن اولین روز دارای نوبت آزاد
  const availableDateIndex = clinicData?.dates?.findIndex((date) =>
    date.slots?.some((slot) => slot.status === "available")
  );

  selectedDateIndex.value =
    availableDateIndex !== undefined && availableDateIndex >= 0 ? availableDateIndex : 0;

  isClinicSelected.value = true;

  nextTick(() => {
    checkDateScrollPosition();
    checkTimeScrollPosition();
  });
};
const nearestAvailable = computed(() => {
  const options = [];

  (props.doctor.clinics_with_slots || []).forEach((clinicData, clinicIndex) => {
    (clinicData.dates || []).forEach((dateData, dateIndex) => {
      (dateData.slots || []).forEach((slot) => {
        if (slot.status === "available") {
          options.push({
            clinicIndex,
            dateIndex,
            clinic: clinicData.clinic,
            date: dateData.date,
            slot,
          });
        }
      });
    });
  });

  options.sort((a, b) => {
    const first = `${a.date} ${a.slot.start_time}`;
    const second = `${b.date} ${b.slot.start_time}`;
    return first.localeCompare(second);
  });

  return options[0] || null;
});

const selectRecommendedSlot = () => {
  const recommendation = nearestAvailable.value;
  if (!recommendation) return;

  selectClinic(recommendation.clinicIndex);
  selectedDateIndex.value = recommendation.dateIndex;
  selectedSlot.value = recommendation.slot;
};

const selectedClinic = computed(() => {
  return currentClinicData.value?.clinic ?? null;
});

const getMapUrl = (lat, lng) => {
  const latitude = Number(lat);
  const longitude = Number(lng);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return null;
  }

  const bbox = [
    longitude - 0.002,
    latitude - 0.002,
    longitude + 0.002,
    latitude + 0.002,
  ].join(",");

  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${latitude},${longitude}`;
};

const getGoogleMapsLink = (lat, lng) =>
  `https://www.google.com/maps/place/?api=1&destination=${lat},${lng}`;

const getOSMLink = (lat, lng) =>
  `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}`;

const getNeshanUrl = (lat, lng) =>
  `https://neshan.org/maps/iframe?lat=${lat}&lng=${lng}&zoom=15`;
// --- DATE STRIP SCROLL ---
const dateStripRef = ref(null);
const canScrollDateLeft = ref(false);
const canScrollDateRight = ref(false);

const checkDateScrollPosition = () => {
  if (!dateStripRef.value) return;

  const el = dateStripRef.value;
  const scrollLeft = el.scrollLeft;
  const scrollWidth = el.scrollWidth;
  const clientWidth = el.clientWidth;

  canScrollDateLeft.value = Math.abs(scrollLeft) < scrollWidth - clientWidth - 5;
  canScrollDateRight.value = Math.abs(scrollLeft) > 5;
};

const scrollDates = (direction) => {
  if (!dateStripRef.value) return;

  const scrollAmount = 200;
  const currentScroll = dateStripRef.value.scrollLeft;

  if (direction === "right") {
    dateStripRef.value.scrollTo({
      left: currentScroll + scrollAmount,
      behavior: "smooth",
    });
  } else {
    dateStripRef.value.scrollTo({
      left: currentScroll - scrollAmount,
      behavior: "smooth",
    });
  }
};

// --- TIME STRIP SCROLL ---
const timeStripRef = ref(null);
const canScrollTimeLeft = ref(false);
const canScrollTimeRight = ref(false);

const checkTimeScrollPosition = () => {
  if (!timeStripRef.value) return;

  const el = timeStripRef.value;
  let scrollLeft = el.scrollLeft;
  const scrollWidth = el.scrollWidth;
  const clientWidth = el.clientWidth;

  // ✅ نرمال‌سازی scrollLeft برای RTL
  // در Safari، scrollLeft منفی است. در Chrome/Firefox، مثبت است.
  // ما می‌خواهیم scrollLeft همیشه مثبت باشد و از 0 شروع شود.
  if (scrollLeft < 0) {
    scrollLeft = scrollWidth - clientWidth + scrollLeft;
  }

  // حالا scrollLeft مانند LTR رفتار می‌کند:
  // 0 در سمت راست (ابتدا در RTL)
  // scrollWidth - clientWidth در سمت چپ (انتها در RTL)
  canScrollTimeRight.value = scrollLeft > 5;
  canScrollTimeLeft.value = scrollLeft < scrollWidth - clientWidth - 5;
};

const scrollTimes = (direction) => {
  if (!timeStripRef.value) return;

  const scrollAmount = 300;
  const currentScroll = timeStripRef.value.scrollLeft;

  // چون dir="rtl" است، جهت‌ها معکوس می‌شوند
  if (direction === "right") {
    timeStripRef.value.scrollTo({
      left: currentScroll - scrollAmount,
      behavior: "smooth",
    });
  } else {
    timeStripRef.value.scrollTo({
      left: currentScroll + scrollAmount,
      behavior: "smooth",
    });
  }
};

// --- WATCHERS ---
watch(
  () => props.doctor.clinics_with_slots,
  () => {
    nextTick(() => {
      setTimeout(() => {
        checkDateScrollPosition();
        checkTimeScrollPosition();
      }, 100);
    });
  },
  { deep: true }
);

watch(selectedDateIndex, () => {
  nextTick(() => {
    setTimeout(checkTimeScrollPosition, 100);
  });
});

watch(selectedClinicIndex, () => {
  nextTick(() => {
    setTimeout(() => {
      checkDateScrollPosition();
      checkTimeScrollPosition();
    }, 100);
  });
});

onMounted(() => {
  nextTick(() => {
    setTimeout(() => {
      checkDateScrollPosition();
      checkTimeScrollPosition();
    }, 100);
  });
});

// --- BOOKING COMPUTED ---
const currentClinicData = computed(() => {
  return props.doctor.clinics_with_slots?.[selectedClinicIndex.value] || { dates: [] };
});

const currentDateData = computed(() => {
  const dates = currentClinicData.value?.dates ?? [];

  if (dates.length === 0) {
    return { slots: [] };
  }

  // اگر تاریخ انتخاب‌شده معتبر نیست، اولین تاریخ را برگردان
  const index =
    selectedDateIndex.value >= 0 && selectedDateIndex.value < dates.length
      ? selectedDateIndex.value
      : 0;

  return dates[index] ?? { slots: [] };
});

// --- HELPER FUNCTIONS ---
const parseLocalDate = (dateStr) => new Date(`${dateStr}T12:00:00`);

const getPersianDayName = (dateStr) => {
  return new Intl.DateTimeFormat("fa-IR", { weekday: "long" }).format(parseLocalDate(dateStr));
};

const getPersianDayNumber = (dateStr) => {
  return new Intl.DateTimeFormat("fa-IR", { day: "numeric" }).format(parseLocalDate(dateStr));
};

const getPersianMonthName = (dateStr) => {
  return new Intl.DateTimeFormat("fa-IR", { month: "long" }).format(parseLocalDate(dateStr));
};

const formatTime = (timeStr) => {
  return timeStr.replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[d]);
};

const selectSlot = (slot) => {
  if (slot.status === "available") {
    selectedSlot.value = slot;
    availabilityMessage.value = "";
  }
};

// --- EVENT HANDLERS ---
const handleBookingSuccess = () => {
};

const handleSlotCleared = (message = "") => {
  selectedSlot.value = null;
  availabilityMessage.value = message || "این ساعت دیگر آزاد نیست؛ لطفاً زمان دیگری انتخاب کنید.";
};

const handleSlotBooked = (slotId) => {
  const currentClinic = props.doctor.clinics_with_slots?.[selectedClinicIndex.value];
  if (!currentClinic) return;

  const currentDate = currentClinic.dates?.[selectedDateIndex.value];
  if (!currentDate) return;

  const slot = currentDate.slots.find((s) => s.id === slotId);
  if (slot) {
    slot.status = "booked";
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
  scroll-behavior: smooth;
}
</style>

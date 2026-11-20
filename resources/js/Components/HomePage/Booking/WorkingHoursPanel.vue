<template>
  <section
    class="relative overflow-hidden rounded-[28px] border border-emerald-100/80 bg-white shadow-[0_8px_35px_-15px_rgba(16,185,129,0.15)]"
    dir="rtl"
  >
    <div class="absolute -top-16 -left-16 h-48 w-48 rounded-full bg-emerald-50/80 blur-3xl pointer-events-none"></div>

    <div class="relative p-5 sm:p-7">
      <div class="mb-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a2 2 0 01-2 2H6a2 2 0 01-2-2V6a1 1 0 011-1z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-black text-gray-800 sm:text-xl">ساعات کاری</h3>
            <p class="mt-1 text-xs text-gray-400 sm:text-sm">برنامه حضور پزشک در هر شعبه</p>
          </div>
        </div>

        <div class="flex min-w-[65px] flex-col items-center justify-center rounded-2xl bg-emerald-50 px-3.5 py-2">
          <span class="text-xl font-black text-emerald-700">{{ activeDays }}</span>
          <span class="text-[10px] font-medium text-emerald-600">روز فعال</span>
        </div>
      </div>

      <div class="mb-5 h-px bg-gradient-to-l from-emerald-100 via-gray-100 to-transparent"></div>

      <div v-if="clinics.length" class="space-y-6">
        <section
          v-for="clinic in clinics"
          :key="clinic.clinic_id"
          class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 sm:p-5"
        >
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01" />
              </svg>
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-extrabold text-gray-800">{{ clinic.clinic }}</p>
              <p v-if="clinic.city" class="mt-1 text-xs text-gray-500">{{ clinic.city }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <div
              v-for="day in clinic.days"
              :key="day.day_of_week"
              class="flex items-center justify-between gap-3 rounded-xl px-3 py-3"
              :class="day.hours ? 'bg-white' : 'bg-gray-100/70'"
            >
              <div class="flex items-center gap-3">
                <span
                  class="h-2 w-2 shrink-0 rounded-full"
                  :class="day.hours ? 'bg-emerald-500' : 'bg-gray-300'"
                ></span>
                <span class="text-sm font-bold text-gray-700">{{ day.name }}</span>
              </div>
              <span v-if="day.hours" class="text-left text-sm font-extrabold text-emerald-700">
                {{ day.hours }}
              </span>
              <span v-else class="text-xs font-medium text-gray-400">تعطیل</span>
            </div>
          </div>
        </section>
      </div>
      <p v-else class="rounded-2xl bg-gray-50 p-5 text-center text-sm text-gray-500">
        ساعات کاری ثبت نشده است.
      </p>

      <section v-if="holidays.length" class="mt-6 rounded-2xl border border-amber-100 bg-amber-50/70 p-4 sm:p-5">
        <h4 class="mb-3 font-bold text-amber-900">تعطیلی‌های اعلام‌شده</h4>
        <div class="space-y-2">
          <div
            v-for="holiday in holidays"
            :key="holiday.id"
            class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white/80 px-3 py-3"
          >
            <div>
              <p class="text-sm font-bold text-gray-800">{{ holiday.title }}</p>
              <p class="mt-1 text-xs text-gray-500">{{ holiday.clinic }}</p>
            </div>
            <span class="text-xs font-semibold text-amber-800">{{ formatPersianDate(holiday.date) }}</span>
          </div>
        </div>
      </section>

      <div class="mt-6 flex items-center gap-3 rounded-2xl border border-emerald-100/70 bg-emerald-50/70 px-4 py-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <p class="text-xs font-bold text-emerald-800">برای مراجعه، از قبل نوبت بگیرید</p>
          <p class="mt-1 text-[11px] text-emerald-700/70">برنامه حضور و تعطیلی‌ها ممکن است با هماهنگی مطب به‌روزرسانی شود.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  working_hours: {
    type: Object,
    required: false,
    default: () => ({ clinics: [], holidays: [] }),
  },
});

const clinics = computed(() => {
  if (Array.isArray(props.working_hours)) {
    return [{
      clinic_id: "legacy",
      clinic: "",
      city: "",
      days: props.working_hours.map((day, index) => ({
        day_of_week: index,
        name: day.name,
        hours: day.hours,
      })),
    }];
  }

  return Array.isArray(props.working_hours?.clinics)
    ? props.working_hours.clinics
    : [];
});

const holidays = computed(() => Array.isArray(props.working_hours?.holidays)
  ? props.working_hours.holidays
  : []
);

const activeDays = computed(() => new Set(
  clinics.value.flatMap((clinic) =>
    (clinic.days || []).filter((day) => day.hours).map((day) => day.name)
  )
).size);

const formatPersianDate = (dateValue) => {
  const match = String(dateValue ?? "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
  const date = match
    ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12)
    : new Date(dateValue);

  if (Number.isNaN(date.getTime())) return String(dateValue ?? "");

  return new Intl.DateTimeFormat("fa-IR", {
    calendar: "persian",
    year: "numeric",
    month: "long",
    day: "numeric",
    weekday: "long",
  }).format(date);
};
</script>


<template>
  <section
    class="relative overflow-hidden rounded-[28px] border border-emerald-100/80 bg-white shadow-[0_8px_35px_-15px_rgba(16,185,129,0.15)]"
    dir="rtl"
  >
    <!-- Decorative background -->
    <div
      class="absolute -top-16 -left-16 w-48 h-48 rounded-full bg-emerald-50/80 blur-3xl pointer-events-none"
    ></div>

    <div class="relative p-5 sm:p-7">

      <!-- Header -->
      <div class="flex items-center justify-between mb-7">

        <div class="flex items-center gap-3.5">

          <div
            class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a2 2 0 01-2 2H6a2 2 0 01-2-2V6a1 1 0 011-1z"
              />
            </svg>
          </div>

          <div>
            <h3 class="text-lg sm:text-xl font-black text-gray-800">
              ساعات کاری
            </h3>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
              برنامه حضور در مطب
            </p>
          </div>

        </div>

        <!-- Active days badge -->
        <div
          class="flex flex-col items-center justify-center bg-emerald-50 rounded-2xl px-3.5 py-2 min-w-[65px]"
        >
          <span class="text-xl font-black text-emerald-700">
            {{ activeDays }}
          </span>
          <span class="text-[10px] text-emerald-600 font-medium">
            روز فعال
          </span>
        </div>

      </div>

      <!-- Divider -->
      <div class="h-px bg-gradient-to-l from-emerald-100 via-gray-100 to-transparent mb-5"></div>

      <!-- Working hours list -->
      <div class="space-y-2.5">

        <div
          v-for="(day, index) in workingHours"
          :key="index"
          class="group flex items-center justify-between gap-3 rounded-2xl px-3 py-3.5 sm:px-4 transition-all duration-300"
          :class="
            day.hours
              ? 'bg-gray-50/80 hover:bg-emerald-50/70'
              : 'bg-gray-50/40'
          "
        >

          <!-- Day and clinic -->
          <div class="flex items-center gap-3 min-w-0">

            <!-- Day indicator -->
            <div
              class="w-2 h-2 rounded-full shrink-0"
              :class="
                day.hours
                  ? 'bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.10)]'
                  : 'bg-gray-300'
              "
            ></div>

            <div class="min-w-0">

              <div class="flex flex-wrap items-center gap-2">

                <span class="text-sm font-bold text-gray-700">
                  {{ day.name }}
                </span>

                <!-- Clinic badge -->
                <span
                  v-if="day.clinic"
                  class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] sm:text-[11px] font-semibold"
                  :class="{
                    'bg-amber-50 text-amber-700':
                      day.clinic === 'مطب بجنورد',
                    'bg-sky-50 text-sky-700':
                      day.clinic === 'مطب مشهد',
                    'bg-violet-50 text-violet-700':
                      day.clinic !== 'مطب بجنورد' &&
                      day.clinic !== 'مطب مشهد',
                  }"
                >
                  <svg
                    class="w-3 h-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01"
                    />
                  </svg>
                  {{ day.clinic }}
                </span>

              </div>

            </div>

          </div>

          <!-- Hours -->
          <div class="shrink-0 text-left">

            <span
              v-if="day.hours"
              class="inline-flex items-center gap-1.5 text-sm font-extrabold text-emerald-700"
            >
              <svg
                class="w-4 h-4 text-emerald-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <circle cx="12" cy="12" r="9" />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 7v5l3 2"
                />
              </svg>

              {{ day.hours }}
            </span>

            <span
              v-else
              class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
              تعطیل
            </span>

          </div>

        </div>

      </div>

      <!-- Footer -->
      <div
        class="mt-6 flex items-center gap-3 rounded-2xl bg-emerald-50/70 border border-emerald-100/70 px-4 py-3"
      >
        <div
          class="w-9 h-9 shrink-0 rounded-xl bg-white flex items-center justify-center text-emerald-600 shadow-sm"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.7"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>

        <div>
          <p class="text-xs font-bold text-emerald-800">
            برای مراجعه، از قبل نوبت بگیرید
          </p>
          <p class="text-[11px] text-emerald-700/70 mt-1">
            برنامه حضور ممکن است با هماهنگی مطب تغییر کند.
          </p>
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
    default: () => ({}),
  },
});

const workingHours = computed(() => {
  if (Array.isArray(props.working_hours)) {
    return props.working_hours;
  }

  return Object.values(props.working_hours || {});
});

const activeDays = computed(() => {
  return workingHours.value.filter(
    (day) => day.hours && day.hours.trim() !== ""
  ).length;
});
</script>

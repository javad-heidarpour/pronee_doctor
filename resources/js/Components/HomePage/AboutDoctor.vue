<template>
  <section class="space-y-4" dir="rtl">
    <!-- About Doctor -->
    <div
      class="relative overflow-hidden rounded-[28px] border border-emerald-100/70 bg-white shadow-[0_8px_35px_-18px_rgba(16,185,129,0.22)]"
    >
      <!-- Decorative elements -->
      <div
        class="absolute -top-20 -left-16 w-56 h-56 rounded-full bg-emerald-50/80 blur-3xl pointer-events-none"
      ></div>

      <div class="relative p-5 sm:p-7 md:p-8">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-7">
          <!-- Doctor Image -->
          <div class="relative shrink-0">
            <img
              v-if="doctorPhoto"
              :src="doctorPhoto"
              :alt="doctorFullName"
              class="w-[68px] h-[68px] sm:w-[76px] sm:h-[76px] rounded-2xl object-cover object-top border-2 border-white shadow-md"
            />
            <div
              v-else
              class="flex h-[68px] w-[68px] items-center justify-center rounded-2xl border-2 border-white bg-emerald-50 text-2xl font-black text-emerald-700 shadow-md sm:h-[76px] sm:w-[76px]"
            >
              {{ doctorInitials }}
            </div>

            <!-- Online / Verified indicator -->
            <span
              class="absolute -bottom-1 -left-1 w-5 h-5 rounded-full bg-emerald-500 border-[3px] border-white"
            ></span>
          </div>

          <!-- Title -->
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-gray-800">درباره پزشک</h2>

            <p class="text-xs sm:text-sm text-gray-400 mt-1.5">
              آشنایی بیشتر با سوابق و تجربیات پزشک
            </p>
          </div>
        </div>

        <!-- Decorative line -->
        <div class="flex items-center gap-2 mb-6">
          <div class="w-10 h-1 rounded-full bg-emerald-500"></div>
          <div class="w-2 h-1 rounded-full bg-emerald-200"></div>
          <div class="w-1 h-1 rounded-full bg-emerald-100"></div>
        </div>

        <!-- Description -->
        <div class="relative">
          <span
            class="absolute -top-5 -right-1 text-5xl font-serif text-emerald-100 select-none"
          >
            "
          </span>

          <p
            class="relative text-[14px] sm:text-[15px] text-gray-600 leading-[2.4] text-justify whitespace-pre-line"
          >
            {{
              doctor.description ||
              "توضیحاتی درباره سوابق تحصیلی، دوره‌های گذرانده شده و تجربیات بالینی پزشک در اینجا نمایش داده می‌شود."
            }}
          </p>
        </div>

        <!-- Bottom accent -->
        <div
          class="mt-7 pt-5 border-t border-gray-100 flex items-center justify-between gap-3"
        >
          <div class="flex items-center gap-2 text-xs text-gray-400">
            <svg
              class="w-4 h-4 text-emerald-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12l2 2 4-4m5 2a8 8 0 11-16 0 8 8 0 0116 0z"
              />
            </svg>

            <span>تجربه، تخصص و تعهد به سلامت شما</span>
          </div>

          <span
            class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 12h14m-6-6l6 6-6 6"
              />
            </svg>
          </span>
        </div>
      </div>
    </div>

    <!-- Specialties -->
    <SpecialtySection class="my-2" :doctor="doctor" />
  </section>
</template>

<script setup>
import { computed } from "vue";
import SpecialtySection from "@/Components/HomePage/SpecialtySection.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const doctorPhoto = computed(() => {
  const photo = props.doctor.photo;
  if (!photo) return null;
  if (/^https?:\\/\\//i.test(photo)) return photo;
  if (photo.startsWith("/")) return photo;
  if (photo.startsWith("storage/")) return `/${photo}`;
  return `/storage/${photo}`;
});

const doctorFullName = computed(() =>
  [props.doctor.caption, props.doctor.name, props.doctor.family]
    .filter(Boolean)
    .join(" ")
);

const doctorInitials = computed(() =>
  [props.doctor.name, props.doctor.family]
    .filter(Boolean)
    .map((part) => part.trim().charAt(0))
    .join("") || "پ"
);
</script>

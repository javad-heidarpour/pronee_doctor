<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="w-full min-w-0 space-y-6 sm:space-y-8">
      <!-- Welcome Banner -->
      <section
        class="relative isolate overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-l from-teal-700 via-teal-600 to-cyan-600 px-5 py-7 sm:px-8 sm:py-9 lg:px-10 lg:py-10 text-white shadow-lg shadow-teal-900/10"
      >
        <!-- Decorative Shapes -->
        <div
          class="pointer-events-none absolute -left-12 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"
        ></div>

        <div
          class="pointer-events-none absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-cyan-300/20 blur-3xl"
        ></div>

        <div
          class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0">
            <!-- Role Badge -->
            <div
              class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs sm:text-sm text-teal-50 backdrop-blur-sm"
            >
              <span class="h-2 w-2 rounded-full bg-green-300"></span>
              {{ roleTitle }} | پنل مدیریت
            </div>

            <!-- User Welcome -->
            <h1 class="text-xl font-extrabold leading-relaxed sm:text-2xl lg:text-3xl">
              سلام {{ userFullName }} عزیز 👋
            </h1>

            <!-- Active Doctor -->
            <p class="mt-3 text-sm leading-7 text-teal-50 sm:text-base">
              <template v-if="doctorFullName">
                شما در پنل اختصاصی
                <strong class="font-extrabold text-white">
                  {{ doctorFullName }}
                </strong>
                فعالیت می‌کنید.
              </template>

              <template v-else> به پنل مدیریت سامانه پرونیکس خوش آمدید. </template>
            </p>

            <p class="mt-1 text-xs leading-6 text-teal-100 sm:text-sm">
              از اینجا می‌توانید فعالیت‌ها و خدمات مطب را مدیریت کنید.
            </p>
          </div>

          <!-- Medical Icon -->
          <div class="hidden shrink-0 sm:flex">
            <div
              class="flex h-20 w-20 items-center justify-center rounded-3xl border border-white/20 bg-white/10 text-4xl shadow-inner backdrop-blur"
            >
              🩺
            </div>
          </div>
        </div>
      </section>

      <!-- Statistics Section -->
      <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-extrabold text-gray-800 sm:text-xl">
              نمای کلی فعالیت‌ها
            </h2>

            <p class="mt-1 text-xs text-gray-500 sm:text-sm">خلاصه وضعیت مدیریت مطب</p>
          </div>

          <span
            class="hidden rounded-xl bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 sm:inline-flex"
          >
            آمار کلی
          </span>
        </div>

        <div
          class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 2xl:grid-cols-6"
        >
          <StatCard
            v-for="(stat, index) in statsCards"
            :key="stat.label"
            :icon="stat.icon"
            :label="stat.label"
            :value="stat.value"
            :color="stat.color"
            class="min-w-0 transition duration-300 hover:-translate-y-1 hover:shadow-lg"
            :style="{ animationDelay: `${index * 70}ms` }"
          />
        </div>
      </section>

      <!-- Quick Actions -->
      <section
        class="overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 lg:p-7"
      >
        <div class="mb-5 flex items-center justify-between gap-3">
          <div class="flex min-w-0 items-center gap-3">
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-teal-50 text-xl"
            >
              ⚡
            </div>

            <div class="min-w-0">
              <h2 class="text-base font-extrabold text-gray-800 sm:text-lg">
                دسترسی سریع
              </h2>

              <p class="mt-1 text-xs text-gray-500 sm:text-sm">
                مدیریت بخش‌های مختلف پنل
              </p>
            </div>
          </div>

          <span class="hidden text-xs text-gray-400 sm:block"> ابزارهای پرکاربرد </span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
          <QuickAction
            :href="route('portal.profile.edit')"
            label="ویرایش پروفایل"
            icon="👤"
          />

          <QuickAction
            :href="route('portal.clinics.index')"
            label="مدیریت مطب‌ها"
            icon="🏥"
          />

          <QuickAction
            :href="route('portal.working-hours.index')"
            label="ساعات کاری"
            icon="🕐"
          />

          <QuickAction
            :href="route('portal.gallery.index')"
            label="گالری تصاویر"
            icon="📸"
          />
        </div>
      </section>

      <!-- Current Account Information -->
      <section
        class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6"
      >
        <div class="flex items-center gap-3 mb-4">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl"
          >
            👤
          </div>

          <div>
            <h2 class="text-base font-extrabold text-gray-800">اطلاعات حساب کاربری</h2>

            <p class="mt-1 text-xs text-gray-500">
              مشخصات حسابی که با آن وارد سامانه شده‌اید
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-xl bg-slate-50 p-3">
            <p class="text-xs text-slate-400 mb-1">نام کاربر</p>

            <p class="text-sm font-bold text-slate-700">
              {{ userFullName }}
            </p>
          </div>

          <div class="rounded-xl bg-slate-50 p-3">
            <p class="text-xs text-slate-400 mb-1">نوع حساب کاربری</p>

            <p class="text-sm font-bold text-teal-700">
              {{ roleTitle }}
            </p>
          </div>

          <div v-if="doctorFullName" class="rounded-xl bg-slate-50 p-3 sm:col-span-2">
            <p class="text-xs text-slate-400 mb-1">پرونده پزشکی فعال</p>

            <p class="text-sm font-bold text-slate-700">
              {{ doctorFullName }}
            </p>
          </div>
        </div>
      </section>

      <!-- Footer Note -->
      <div
        class="flex items-start gap-3 rounded-2xl border border-teal-100 bg-teal-50/70 px-4 py-4 sm:items-center"
      >
        <span class="text-xl">💡</span>

        <p class="text-xs leading-6 text-teal-800 sm:text-sm">
          برای مدیریت بهتر مطب، اطلاعات پروفایل، ساعات کاری و تصاویر را به‌روز نگه دارید.
        </p>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import PortalLayout from "@/Layouts/PortalLayout.vue";
import StatCard from "@/Components/Portal/StatCard.vue";
import QuickAction from "@/Components/Portal/QuickAction.vue";

// --------------------------------------------------
// Props
// --------------------------------------------------

const props = defineProps({
  doctor: {
    type: Object,
    default: null,
  },

  stats: {
    type: Object,
    default: () => ({
      total_appointments: 0,
      pending_appointments: 0,
      total_reviews: 0,
      average_rating: 0,
      total_images: 0,
      total_clinics: 0,
    }),
  },
});

// --------------------------------------------------
// Inertia
// --------------------------------------------------

const page = usePage();

// --------------------------------------------------
// Authenticated User
// --------------------------------------------------

const currentUser = computed(() => {
  return page.props.auth?.user || null;
});

const userRole = computed(() => {
  return currentUser.value?.role || null;
});

const userFullName = computed(() => {
  const user = currentUser.value;

  return [user?.name, user?.family].filter(Boolean).join(" ") || "کاربر گرامی";
});

const roleTitle = computed(() => {
  const roles = {
    doctor: "پزشک",
    secretary: "منشی",
    admin: "مدیر سامانه",
    user: "کاربر",
  };

  return roles[userRole.value] || "کاربر";
});

// --------------------------------------------------
// Active Doctor
// اولویت با پزشک ارسالی صفحه و سپس اطلاعات اشتراکی
// --------------------------------------------------

const currentDoctor = computed(() => {
  const sharedDoctor = page.props.auth?.doctor || {};
  const pageDoctor = props.doctor || {};

  return {
    ...sharedDoctor,
    ...pageDoctor,
  };
});

const doctorFullName = computed(() => {
  const doctor = currentDoctor.value;

  return (
    [doctor?.caption, doctor?.name, doctor?.family].filter(Boolean).join(" ") || null
  );
});

// --------------------------------------------------
// Statistics
// --------------------------------------------------

const statsCards = computed(() => {
  const stats = props.stats || {};

  return [
    {
      label: "کل نوبت‌های رزرو شده",
      value: stats.total_appointments ?? 0,
      icon: "📅",
      color: "teal",
    },
    {
      label: "نوبت‌های در انتظار",
      value: stats.pending_appointments ?? 0,
      icon: "⏳",
      color: "amber",
    },
    {
      label: "تعداد نظرات",
      value: stats.total_reviews ?? 0,
      icon: "💬",
      color: "blue",
    },
    {
      label: "میانگین امتیاز",
      value: stats.average_rating ?? 0,
      icon: "⭐",
      color: "yellow",
    },
    {
      label: "تعداد تصاویر",
      value: stats.total_images ?? 0,
      icon: "🖼️",
      color: "purple",
    },
    {
      label: "تعداد مطب‌ها",
      value: stats.total_clinics ?? 0,
      icon: "🏢",
      color: "green",
    },
  ];
});
</script>

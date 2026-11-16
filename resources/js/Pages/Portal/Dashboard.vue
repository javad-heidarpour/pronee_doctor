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

      <!-- Today's clinic operations -->
      <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm sm:rounded-3xl">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
          <div>
            <div class="flex items-center gap-2">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-lg">🗓️</span>
              <h2 class="text-base font-extrabold text-slate-900 sm:text-lg">امروز مطب</h2>
              <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700">{{ todayAppointments.length }} نوبت</span>
            </div>
            <p class="mt-2 text-xs leading-6 text-slate-500">برنامه روزانه نوبت‌ها و وضعیت مراجعه‌کنندگان مطب‌های مجاز</p>
          </div>

          <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-xl bg-amber-50 px-3 py-2 font-bold text-amber-700">در انتظار: {{ todayStats.pending }}</span>
            <span class="rounded-xl bg-emerald-50 px-3 py-2 font-bold text-emerald-700">تأییدشده: {{ todayStats.confirmed }}</span>
            <span class="rounded-xl bg-rose-50 px-3 py-2 font-bold text-rose-700">پرداخت نیازمند پیگیری: {{ todayStats.unpaid }}</span>
          </div>
        </div>

        <div v-if="nextAppointment" class="mx-4 mt-4 flex flex-col gap-3 rounded-2xl border border-teal-100 bg-gradient-to-l from-teal-50 to-white p-4 sm:mx-6 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-xl shadow-sm">⏱️</span>
            <div>
              <p class="text-[11px] font-bold text-teal-700">مراجعه بعدی امروز</p>
              <p class="mt-1 text-sm font-extrabold text-slate-900">{{ nextAppointment.patient_name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ nextAppointment.clinic_name || 'مطب ثبت نشده' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 self-start rounded-xl bg-white px-4 py-2 text-lg font-black tabular-nums text-teal-800 shadow-sm sm:self-auto">
            {{ nextAppointment.appointment_time }}
          </div>
        </div>

        <div v-if="todayAppointments.length" class="divide-y divide-slate-100">
          <div
            v-for="appointment in todayAppointments"
            :key="appointment.id"
            class="flex flex-col gap-3 px-4 py-4 transition hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between sm:px-6"
          >
            <div class="flex min-w-0 items-center gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-black text-slate-600">
                {{ patientInitials(appointment.patient_name) }}
              </div>
              <div class="min-w-0">
                <Link
                  :href="route('portal.patients.show', appointment.id)"
                  class="block truncate text-sm font-extrabold text-slate-800 transition hover:text-teal-700 hover:underline"
                >
                  {{ appointment.patient_name || 'نامشخص' }}
                </Link>
                <p class="mt-1 truncate text-xs text-slate-500">{{ appointment.clinic_name || 'مطب ثبت نشده' }}</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
              <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-black tabular-nums text-slate-700">{{ appointment.appointment_time || '--:--' }}</span>
              <span :class="appointmentStatusClass(appointment.status)" class="rounded-lg px-3 py-2 text-[11px] font-bold">{{ appointment.status_label }}</span>
              <span :class="paymentStatusClass(appointment.payment_status)" class="rounded-lg px-3 py-2 text-[11px] font-bold">{{ appointment.payment_status_label }}</span>
            </div>
          </div>
        </div>

        <div v-else class="px-5 py-10 text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-2xl">☕</div>
          <p class="mt-3 text-sm font-extrabold text-slate-700">برای امروز نوبتی ثبت نشده است</p>
          <p class="mt-1 text-xs text-slate-400">با ثبت نوبت جدید، برنامه روزانه مطب در این بخش نمایش داده می‌شود.</p>
        </div>

        <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-6">
          <Link :href="route('portal.patients.index')" class="inline-flex items-center gap-2 text-xs font-extrabold text-teal-700 transition hover:text-teal-900">
            مشاهده همه نوبت‌ها
            <span aria-hidden="true">←</span>
          </Link>
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
import { usePage, Link } from "@inertiajs/vue3";

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
  todayAppointments: {
    type: Array,
    default: () => [],
  },
  nextAppointment: {
    type: Object,
    default: null,
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

const todayStats = computed(() => ({
  pending: props.stats?.today_pending ?? 0,
  confirmed: props.stats?.today_confirmed ?? 0,
  unpaid: props.stats?.today_unpaid ?? 0,
}));

const patientInitials = (name) => {
  return (name || '؟')
    .trim()
    .split(/\\s+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2);
};

const appointmentStatusClass = (status) => ({
  pending: 'bg-amber-50 text-amber-700',
  confirmed: 'bg-emerald-50 text-emerald-700',
  visited: 'bg-violet-50 text-violet-700',
  cancelled: 'bg-rose-50 text-rose-700',
}[status] || 'bg-slate-100 text-slate-600');

const paymentStatusClass = (status) => ({
  paid: 'bg-emerald-50 text-emerald-700',
  pending: 'bg-amber-50 text-amber-700',
  initiated: 'bg-amber-50 text-amber-700',
  failed: 'bg-rose-50 text-rose-700',
  cancelled: 'bg-rose-50 text-rose-700',
  refunded: 'bg-sky-50 text-sky-700',
}[status] || 'bg-slate-100 text-slate-600');

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

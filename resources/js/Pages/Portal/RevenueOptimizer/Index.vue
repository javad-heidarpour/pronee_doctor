<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: { type: Object, required: true },
  clinics: { type: Array, default: () => [] },
  filters: { type: Object, required: true },
  report: { type: Object, required: true },
})

const form = {
  from: props.filters.from,
  to: props.filters.to,
  clinic_id: props.filters.clinic_id || '',
}
const number = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0))
const money = value => number(value) + ' ریال'
const percent = value => value === null || value === undefined ? 'داده کافی نیست' : new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 1 }).format(value) + '٪'
const capacity = computed(() => props.report.capacity)
const appointments = computed(() => props.report.appointments)
const revenue = computed(() => props.report.revenue)

const applyFilters = () => router.get(route('portal.revenue-optimizer.index'), form, {
  preserveState: true,
  preserveScroll: true,
})
const levelClass = level => ({
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
  info: 'border-slate-200 bg-slate-50 text-slate-800',
}[level] || 'border-slate-200 bg-slate-50 text-slate-800')
</script>

<template>
  <PortalLayout :doctor="doctor" title="بهینه‌ساز درآمد و ظرفیت">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-teal-950 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold tracking-wide text-teal-200">PRONEEX · REVENUE OPTIMIZER</p>
        <h1 class="mt-2 text-2xl font-black">بهینه‌ساز هوشمند درآمد و ظرفیت کلینیک</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-teal-50/85">تحلیل ظرفیت نوبت‌دهی، لغوها و دریافتی‌های واقعی برای تصمیم‌گیری بهتر در مدیریت شعبه؛ بدون تخمین‌سازی درآمد یا سود.</p>
      </header>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <form @submit.prevent="applyFilters" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <label class="text-xs font-semibold text-slate-600">از تاریخ
            <input v-model="form.from" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm" />
          </label>
          <label class="text-xs font-semibold text-slate-600">تا تاریخ
            <input v-model="form.to" type="date" required :min="form.from" class="mt-1 w-full rounded-xl border-slate-300 text-sm" />
          </label>
          <label class="text-xs font-semibold text-slate-600">شعبه
            <select v-model="form.clinic_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
              <option value="">همه شعبه‌های مجاز</option>
              <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
            </select>
          </label>
          <button class="self-end rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white hover:bg-teal-800">به‌روزرسانی گزارش</button>
        </form>
      </section>

      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">استفاده از ظرفیت</p>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ percent(capacity.utilization_rate) }}</p>
          <p class="mt-2 text-xs text-slate-500">{{ number(capacity.occupied_slots) }} <span class="text-slate-400">/ {{ number(capacity.total_slots) }} نوبت</span></p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">ظرفیت خالی</p>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ number(capacity.available_slots) }}</p>
          <p class="mt-2 text-xs text-slate-500">زمان‌های آزاد در تقویم ثبت‌شده</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">نرخ لغو نوبت</p>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ percent(appointments.cancellation_rate) }}</p>
          <p class="mt-2 text-xs text-slate-500">{{ number(appointments.cancelled) }} لغو از {{ number(appointments.total) }} نوبت</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">دریافتی پرداخت‌های موفق</p>
          <p class="mt-2 text-xl font-black text-slate-900">{{ money(revenue.paid_amount) }}</p>
          <p class="mt-2 text-xs text-slate-500">{{ number(revenue.paid_appointments) }} نوبت دارای پرداخت موفق</p>
        </article>
      </section>

      <section class="grid gap-3 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">ویزیت انجام‌شده</p>
          <p class="mt-2 text-xl font-black">{{ number(appointments.visited) }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">نوبت فعال (در انتظار / تأییدشده)</p>
          <p class="mt-2 text-xl font-black">{{ number(appointments.active) }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">میانگین دریافتی هر نوبت پرداخت‌شده</p>
          <p class="mt-2 text-xl font-black">{{ revenue.average_paid_amount === null ? 'داده کافی نیست' : money(revenue.average_paid_amount) }}</p>
        </article>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <div class="mb-4">
          <h2 class="text-lg font-black">پیشنهادهای عملیاتی</h2>
          <p class="mt-1 text-xs text-slate-500">پیشنهادها از شاخص‌های مشاهده‌شده تولید شده‌اند و جایگزین تصمیم مدیر یا مسئول درمان نیستند.</p>
        </div>
        <div v-if="!report.recommendations.length" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">در این بازه پیشنهاد خاصی تولید نشده است.</div>
        <div v-else class="space-y-3">
          <article v-for="(item, index) in report.recommendations" :key="index" class="rounded-xl border p-4" :class="levelClass(item.level)">
            <h3 class="font-bold">{{ item.title }}</h3>
            <p class="mt-1 text-sm leading-7">{{ item.description }}</p>
          </article>
        </div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 sm:p-6">
        <h2 class="font-black text-amber-950">محدودیت‌های محاسبه و شفافیت داده</h2>
        <ul class="mt-3 list-disc space-y-2 pr-5 text-sm leading-7 text-amber-950/90">
          <li>{{ report.limitations.no_show }}</li>
          <li>{{ report.limitations.profit }}</li>
          <li>{{ report.limitations.lost_revenue }}</li>
          <li>شاخص دریافتی بر اساس زمان ثبت پرداخت موفق (paid_at) محاسبه می‌شود؛ شاخص نوبت و ظرفیت بر اساس تاریخ نوبت است.</li>
          <li>این گزارش هزینه‌های ثبت‌شده را از پرداخت‌ها کسر نمی‌کند و سود خالص یا بازده سرمایه‌گذاری اعلام نمی‌کند.</li>
        </ul>
      </section>
    </main>
  </PortalLayout>
</template>

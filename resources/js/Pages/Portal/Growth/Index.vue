<script setup>
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ doctor: Object, clinics: Array, filters: Object, report: Object })
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const number = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0))
const applyFilters = event => {
  const data = new FormData(event.target)
  router.get(route('portal.growth.index'), Object.fromEntries(data.entries()), { preserveState: true, preserveScroll: true })
}
const levelClass = level => level === 'warning' ? 'border-amber-200 bg-amber-50' : level === 'success' ? 'border-emerald-200 bg-emerald-50' : 'border-sky-200 bg-sky-50'
</script>

<template>
  <PortalLayout :doctor="doctor" title="موتور رشد کلینیک">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-indigo-950 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold tracking-wider text-teal-200">PRONEEX · CLINIC GROWTH ENGINE</p>
        <h1 class="mt-2 text-2xl font-black">موتور هوشمند رشد کلینیک</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-white/80">نمایی یکپارچه از جذب، بازگشت بیماران، ظرفیت نوبت‌دهی، عملکرد کمپین‌ها و دریافت‌های موفق؛ با پیشنهادهای عملیاتی و بدون افشای اطلاعات هویتی بیماران.</p>
      </header>

      <form @submit.prevent="applyFilters" class="grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-4">
        <label class="text-xs font-semibold text-slate-600">از تاریخ<input name="from" type="date" :value="filters.from" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
        <label class="text-xs font-semibold text-slate-600">تا تاریخ<input name="to" type="date" :value="filters.to" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
        <label class="text-xs font-semibold text-slate-600">شعبه<select name="clinic_id" :value="filters.clinic_id || ''" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">همه شعب مجاز</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select></label>
        <button class="self-end rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white">اعمال فیلتر</button>
      </form>

      <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">بیماران دارای مراجعه</span><b class="mt-2 block text-2xl">{{ number(report.patients.active) }}</b><p class="mt-1 text-[11px] text-slate-500">جدید {{ number(report.patients.new) }} · بازگشتی {{ number(report.patients.returning) }}</p></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">بیماران نیازمند بازبینی ارتباط</span><b class="mt-2 block text-2xl text-amber-700">{{ number(report.patients.inactive_over_90_days) }}</b><p class="mt-1 text-[11px] text-slate-500">بر اساس بیش از ۹۰ روز از آخرین مراجعه</p></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">نرخ استفاده از ظرفیت</span><b class="mt-2 block text-2xl">{{ report.capacity.utilization_rate === null ? '—' : number(report.capacity.utilization_rate) + '٪' }}</b><p class="mt-1 text-[11px] text-slate-500">خالی {{ number(report.capacity.available) }} از {{ number(report.capacity.total) }} نوبت</p></article>
        <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">دریافت موفق ثبت‌شده</span><b class="mt-2 block text-xl text-emerald-700">{{ money(report.finance.paid_revenue_rial) }}</b><p class="mt-1 text-[11px] text-slate-500">{{ number(report.finance.paid_appointments) }} نوبت پرداخت‌شده</p></article>
      </section>

      <section class="grid gap-3 lg:grid-cols-2">
        <article class="rounded-2xl border bg-white p-4">
          <h2 class="font-black">جذب و تداوم مراجعه</h2>
          <div class="mt-3 grid grid-cols-2 gap-2 text-center">
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.patients.new) }}</b><p class="text-xs text-slate-500">بیمار جدید</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.patients.returning) }}</b><p class="text-xs text-slate-500">بیمار بازگشتی</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.patients.upcoming) }}</b><p class="text-xs text-slate-500">دارای نوبت آینده</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.appointments.visited) }}</b><p class="text-xs text-slate-500">مراجعه انجام‌شده</p></div>
          </div>
          <p class="mt-3 text-xs text-slate-500">لغو نوبت: {{ number(report.appointments.cancelled) }} · نرخ لغو: {{ report.appointments.cancellation_rate === null ? 'داده کافی نیست' : number(report.appointments.cancellation_rate) + '٪' }}</p>
        </article>
        <article class="rounded-2xl border bg-white p-4">
          <h2 class="font-black">بازاریابی و کمپین‌ها</h2>
          <div class="mt-3 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.marketing.clicks) }}</b><p class="text-xs text-slate-500">کلیک</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ number(report.marketing.bookings) }}</b><p class="text-xs text-slate-500">نوبت منتسب</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><b class="text-xl">{{ report.marketing.conversion_rate === null ? '—' : number(report.marketing.conversion_rate) + '٪' }}</b><p class="text-xs text-slate-500">نرخ تبدیل</p></div>
          </div>
          <div class="mt-3 space-y-2">
            <div v-for="campaign in report.marketing.campaigns" :key="campaign.id" class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 p-2 text-xs">
              <div><b>{{ campaign.name }}</b><p class="mt-1 text-slate-500">{{ campaign.channel }} · {{ number(campaign.clicks) }} کلیک · {{ number(campaign.bookings) }} نوبت</p></div>
              <span class="font-bold">{{ campaign.conversion_rate === null ? '—' : number(campaign.conversion_rate) + '٪' }}</span>
            </div>
            <p v-if="!report.marketing.campaigns.length" class="text-xs text-slate-500">هنوز کمپینی ثبت نشده است.</p>
          </div>
          <a :href="route('portal.marketing.index')" class="mt-3 inline-flex rounded-lg bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">مدیریت کمپین‌ها</a>
        </article>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">فرصت‌ها و پیشنهادهای عملیاتی</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
          <article v-for="item in report.opportunities" :key="item.key" class="rounded-xl border p-3" :class="levelClass(item.level)">
            <div class="flex items-center justify-between gap-2"><b class="text-sm">{{ item.title }}</b><span class="text-sm font-black">{{ number(item.value) }}<span v-if="item.key === 'cancellation'">٪</span></span></div>
            <p class="mt-2 text-xs leading-6">{{ item.description }}</p>
          </article>
          <p v-if="!report.opportunities.length" class="text-xs text-slate-500">در داده‌های این بازه فرصت عملیاتی مشخصی شناسایی نشد؛ نبود پیشنهاد به معنی ارزیابی کیفیت بالینی نیست.</p>
        </div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <h2 class="text-sm font-black text-amber-900">محدودیت‌ها و استفاده مسئولانه</h2>
        <ul class="mt-2 list-disc space-y-1 pr-5 text-xs leading-6 text-amber-900">
          <li>این داشبورد ابزار تحلیل عملیاتی است، نه پیش‌بینی قطعی ریزش یا توصیه بالینی.</li>
          <li>آمار درآمد، دریافت موفق ناخالص است و سود خالص یا درآمد منتسب به کمپین محسوب نمی‌شود.</li>
          <li>بازاریابی و تماس با بیمار باید با رضایت معتبر، حفظ محرمانگی و مقررات حرفه‌ای انجام شود.</li>
          <li>برای جلوگیری از مراجعه یا خدمت غیرضروری، از شاخص‌ها فقط برای بهبود دسترسی و تجربه بیمار استفاده کنید.</li>
        </ul>
      </section>
    </main>
  </PortalLayout>
</template>

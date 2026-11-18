<template>
  <PortalLayout :doctor="doctor" title="موتور پیش‌بینی روند بیماران">
    <div dir="rtl" class="space-y-5">
      <section class="rounded-2xl bg-gradient-to-l from-indigo-950 via-slate-900 to-cyan-900 p-5 text-white sm:p-7">
        <p class="text-xs font-bold tracking-widest text-cyan-200">PRONEEX PREDICTIVE PATIENT ENGINE</p>
        <h2 class="mt-2 text-2xl font-black">نگاه طولی به مسیر مراقبت بیمار</h2>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-200">روند اندازه‌گیری‌های ثبت‌شده و پیگیری‌های برنامه‌ریزی‌شده را کنار هم قرار می‌دهد تا پرونده‌های نیازمند بازبینی زودتر دیده شوند؛ بدون تولید احتمال یا تشخیص ساختگی.</p>
        <p class="mt-3 text-xs text-cyan-100">زمان محاسبه: {{ formatDate(generatedAt) }}</p>
      </section>
      <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">کل بیماران</p><p class="mt-2 text-2xl font-black">{{ fa(stats.total) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-amber-700">نیازمند بازبینی</p><p class="mt-2 text-2xl font-black text-amber-700">{{ fa(stats.needs_review) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-emerald-700">پوشش داده مناسب</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ fa(stats.strong) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">داده ناکافی</p><p class="mt-2 text-2xl font-black">{{ fa(stats.insufficient) }}</p></div>
      </section>
      <form @submit.prevent="apply" class="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-[1fr_220px_auto]">
        <input v-model="search" maxlength="120" type="search" placeholder="جستجوی بیمار" class="rounded-xl border-slate-200 text-sm" />
        <select v-model="attention" class="rounded-xl border-slate-200 text-sm"><option value="all">همه پرونده‌ها</option><option value="yes">نیازمند بازبینی</option><option value="no">بدون پرچم بازبینی</option></select>
        <button class="rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white">اعمال فیلتر</button>
      </form>
      <section v-if="patients.length" class="space-y-3">
        <article v-for="patient in patients" :key="patient.patient_id" class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5">
          <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-black text-slate-900">{{ patient.patient_name }}</h3><p class="mt-1 text-xs text-slate-500">شناسه پرونده {{ fa(patient.patient_id) }} · {{ fa(patient.measurement_count) }} اندازه‌گیری در {{ fa(patient.coverage_days) }} روز پوشش</p></div><span class="rounded-full px-3 py-1 text-xs font-bold" :class="patient.needs_review ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'">{{ patient.needs_review ? 'بازبینی پیشنهادی' : 'بدون پرچم فعال' }}</span></div>
          <div class="mt-3 flex flex-wrap gap-2 text-xs"><span class="rounded-lg bg-slate-100 px-3 py-2">کیفیت داده: {{ patient.confidence_label }}</span><span class="rounded-lg bg-rose-50 px-3 py-2 text-rose-700">پیگیری معوق: {{ fa(patient.overdue_followups) }}</span><span class="rounded-lg bg-cyan-50 px-3 py-2 text-cyan-800">پیگیری ۷ روز آینده: {{ fa(patient.upcoming_followups) }}</span></div>
          <p class="mt-3 text-sm leading-7 text-slate-700">{{ patient.summary }}</p>
          <div v-if="patient.signals.length" class="mt-3 grid gap-3 lg:grid-cols-2">
            <div v-for="signal in patient.signals" :key="signal.type" class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3">
              <div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-sm text-indigo-950">{{ signal.label }}</strong><span class="text-xs font-bold text-indigo-700">{{ signal.direction_label }} · {{ fa(Math.abs(signal.change_percent)) }}٪</span></div>
              <p class="mt-2 text-xs leading-6 text-slate-700">میانگین بخش قدیمی‌تر: {{ fa(signal.early_average) }} {{ signal.unit }}؛ میانگین بخش اخیر: {{ fa(signal.recent_average) }} {{ signal.unit }}</p>
              <p class="mt-1 text-[11px] text-slate-500">{{ fa(signal.samples) }} نمونه طی {{ fa(signal.span_days) }} روز · آخرین ثبت: {{ formatDate(signal.last_measured_at) }}</p><p class="mt-2 text-xs leading-6 text-slate-600">{{ signal.message }}</p>
            </div>
          </div>
          <p v-else class="mt-3 rounded-lg bg-slate-50 p-3 text-xs leading-6 text-slate-600">برای ترسیم روند، حداقل چهار اندازه‌گیری در بازه حداقل ۱۴ روز لازم است؛ داده ناکافی به معنی وضعیت طبیعی نیست.</p>
        </article>
      </section>
      <section v-else class="rounded-xl border border-dashed p-12 text-center text-sm text-slate-500">پرونده‌ای با این فیلتر پیدا نشد.</section>
      <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950"><strong>محدودیت و ایمنی:</strong><ul class="mt-1 list-disc space-y-1 pr-5"><li>این نسخه احتمال بیماری یا رویداد آینده را پیش‌بینی نمی‌کند؛ فقط تغییرات تاریخی و وضعیت پیگیری را توصیف می‌کند.</li><li>درصد تغییر، اختلاف میانگین دو بخش زمانی داده است و احتمال خطر یا شاخص اعتبارسنجی‌شده بالینی نیست.</li><li>پوشش داده مناسب فقط به تعداد و گستره زمانی نمونه‌ها اشاره دارد، نه دقت پیش‌بینی.</li><li>هیچ درمان، دارو یا اولویتی به‌صورت خودکار تغییر نمی‌کند؛ تفسیر نهایی با پزشک است.</li></ul></section>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: { type: Object, required: true },
  patients: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total: 0, needs_review: 0, strong: 0, insufficient: 0 }) },
  filters: { type: Object, default: () => ({ search: '', attention: 'all' }) },
  generatedAt: { type: String, default: null },
})
const search = ref(props.filters.search || '')
const attention = ref(props.filters.attention || 'all')
const apply = () => router.get(route('portal.predictive-patients.index'), { search: search.value || undefined, attention: attention.value }, { preserveState: true, preserveScroll: true })
const fa = value => Number(value || 0).toLocaleString('fa-IR')
const formatDate = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium' }).format(new Date(value)) : '—'
</script>

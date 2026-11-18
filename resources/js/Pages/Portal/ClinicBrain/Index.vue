<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="mx-auto w-full max-w-7xl space-y-6">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-teal-900 to-cyan-800 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
          <div>
            <div class="mb-3 inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold">PRONEEX CLINIC BRAIN</div>
            <h1 class="text-2xl font-black sm:text-3xl">مغز هوشمند مدیریت کلینیک</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-teal-50">تحلیل ظرفیت، روند مراجعات و فرصت‌های عملیاتی بر اساس داده‌های واقعی شعب مجاز.</p>
          </div>
          <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm">
            <div class="text-teal-100">بازه تحلیل تاریخی</div>
            <div class="mt-1 font-bold">{{ formatDate(brain.period.from) }} تا {{ formatDate(brain.period.to) }}</div>
          </div>
        </div>
      </header>

      <form method="get" :action="route('portal.clinic-brain.index')" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <label class="flex-1 text-sm font-bold text-slate-700">
          تحلیل شعبه
          <select name="clinic_id" :value="selectedClinicId || ''" class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
            <option value="">تمام شعب مجاز</option>
            <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
          </select>
        </label>
        <button class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-teal-800">اعمال فیلتر</button>
      </form>

      <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <MetricCard title="نوبت‌های ۸ هفته" :value="number(brain.metrics.historical_appointments)" detail="داده واقعی ثبت‌شده" icon="📅"/>
        <MetricCard title="نرخ لغو" :value="percent(brain.metrics.cancellation_rate)" detail="لغو به کل نوبت‌ها" icon="↩️"/>
        <MetricCard title="ظرفیت ۱۴ روز آینده" :value="number(brain.metrics.upcoming_capacity)" detail="اسلات‌های ساخته‌شده" icon="🗓️"/>
        <MetricCard title="زمان‌های آزاد" :value="number(brain.metrics.upcoming_available)" detail="اسلات آزاد قابل پیشنهاد" icon="✨"/>
      </section>

      <section class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <div class="mb-5 flex items-center justify-between gap-3">
            <div><h2 class="font-black text-slate-900">نقشه فشار ظرفیت ۱۴ روز آینده</h2><p class="mt-1 text-xs text-slate-500">تقاضای مورد انتظار از میانگین همان روز هفته در ۸ هفته گذشته</p></div>
            <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">برآورد</span>
          </div>
          <div class="space-y-3">
            <div v-for="day in brain.days" :key="day.date" class="grid grid-cols-[82px_1fr_90px] items-center gap-3 text-xs">
              <div class="font-bold text-slate-600">{{ formatDate(day.date) }}</div>
              <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full transition-all" :class="pressureClass(day.demand_pressure)" :style="{ width: Math.min(100, day.demand_pressure || 0) + '%' }"></div></div>
              <div class="text-left tabular-nums text-slate-500">{{ day.booked }}/{{ day.capacity }} پر</div>
            </div>
            <p v-if="!brain.days.length" class="py-8 text-center text-sm text-slate-400">برای این بازه اسلات زمان‌بندی‌شده‌ای ثبت نشده است.</p>
          </div>
          <div class="mt-5 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-[11px] text-slate-500"><span>فشار کمتر از ۶۰٪</span><span>۶۰ تا ۸۴٪</span><span>۸۵٪ و بیشتر</span></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <h2 class="font-black text-slate-900">پیشنهادهای عملیاتی</h2><p class="mt-1 text-xs text-slate-500">هر پیشنهاد همراه با دلیل و اقدام پیشنهادی</p>
          <div class="mt-4 space-y-3">
            <article v-for="(insight, index) in brain.insights" :key="index" class="rounded-xl border p-4" :class="insightClass(insight.type)">
              <div class="font-extrabold">{{ insight.title }}</div><p class="mt-2 text-xs leading-6">{{ insight.message }}</p><p class="mt-2 text-xs font-bold">اقدام: {{ insight.action }}</p>
            </article>
          </div>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <h2 class="font-black text-slate-900">زمان‌های آزاد قابل پیشنهاد</h2><p class="mt-1 text-xs text-slate-500">برای تماس با بیماران منتظر یا تکمیل ظرفیت</p>
          <div v-if="brain.available_slots.length" class="mt-4 divide-y divide-slate-100">
            <div v-for="slot in brain.available_slots" :key="slot.id" class="flex items-center justify-between gap-3 py-3">
              <div><div class="text-sm font-extrabold text-slate-800">{{ slot.clinic_name || 'شعبه' }}</div><div class="mt-1 text-xs text-slate-500">{{ formatDate(slot.date) }} · {{ slot.start_time }} تا {{ slot.end_time }}</div></div>
              <span class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">آزاد</span>
            </div>
          </div>
          <p v-else class="py-8 text-center text-sm text-slate-400">در این بازه زمان آزاد ثبت نشده است.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <h2 class="font-black text-slate-900">شاخص‌های مراجعه</h2>
          <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-slate-50 p-4"><div class="text-xs text-slate-500">ویزیت‌شده</div><div class="mt-2 text-2xl font-black text-slate-900">{{ number(brain.metrics.visited) }}</div></div>
            <div class="rounded-xl bg-slate-50 p-4"><div class="text-xs text-slate-500">لغوشده</div><div class="mt-2 text-2xl font-black text-slate-900">{{ number(brain.metrics.cancelled) }}</div></div>
            <div class="col-span-2 rounded-xl border border-amber-100 bg-amber-50 p-4">
              <div class="text-xs font-bold text-amber-800">نرخ عدم مراجعه ثبت‌شده</div><div class="mt-2 text-xl font-black text-amber-950">{{ percent(brain.metrics.no_show_rate) }}</div>
              <p class="mt-2 text-xs leading-6 text-amber-800">فقط در صورت ثبت صریح وضعیت no_show یا missed محاسبه می‌شود. لغو نوبت به‌اشتباه عدم مراجعه تلقی نمی‌شود. نمونه ثبت‌شده: {{ number(brain.metrics.no_show_sample) }}</p>
            </div>
          </div>
        </div>
      </section>

      <details class="rounded-2xl border border-slate-200 bg-white p-4 text-sm">
        <summary class="cursor-pointer font-extrabold text-slate-800">روش محاسبه و محدودیت‌های تحلیل</summary>
        <ul class="mt-3 list-disc space-y-2 pr-5 text-xs leading-6 text-slate-600">
          <li v-for="(description, index) in brain.methodology" :key="index">{{ description }}</li>
          <li>این ابزار تصمیم‌یار مدیریتی است؛ تشخیص، توصیه درمانی یا تصمیم خودکار درباره بیمار انجام نمی‌دهد.</li>
          <li>داده‌های شعب دیگر و اطلاعات بالینی بیماران در این صفحه نمایش داده نمی‌شود.</li>
        </ul>
      </details>
    </div>
  </PortalLayout>
</template>

<script setup>
import { defineComponent, h } from 'vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'

defineProps({
  doctor: { type: Object, default: null },
  clinics: { type: Array, default: () => [] },
  selectedClinicId: { type: Number, default: null },
  brain: { type: Object, default: () => ({ period: {}, metrics: {}, days: [], available_slots: [], insights: [], methodology: [] }) },
})

const MetricCard = defineComponent({
  props: { title: String, value: [String, Number], detail: String, icon: String },
  setup(props) {
    return () => h('div', { class: 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm' }, [
      h('div', { class: 'flex items-center justify-between text-xl' }, [props.icon]),
      h('div', { class: 'mt-3 text-xs font-bold text-slate-500' }, props.title),
      h('div', { class: 'mt-2 text-2xl font-black tabular-nums text-slate-900' }, props.value),
      h('div', { class: 'mt-1 text-[11px] text-slate-400' }, props.detail),
    ])
  },
})

const number = (value) => new Intl.NumberFormat('fa-IR').format(Number(value || 0))
const percent = (value) => value === null || value === undefined ? 'داده کافی نیست' : number(value) + '٪'
const formatDate = (value) => value ? new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(value + 'T12:00:00')) : '—'
const pressureClass = (value) => value === null ? 'bg-slate-300' : value >= 85 ? 'bg-rose-500' : value >= 60 ? 'bg-amber-400' : 'bg-emerald-500'
const insightClass = (type) => ({
  warning: 'border-amber-200 bg-amber-50 text-amber-950',
  opportunity: 'border-emerald-200 bg-emerald-50 text-emerald-950',
  success: 'border-teal-200 bg-teal-50 text-teal-950',
  info: 'border-sky-200 bg-sky-50 text-sky-950',
}[type] || 'border-slate-200 bg-slate-50 text-slate-900')
</script>

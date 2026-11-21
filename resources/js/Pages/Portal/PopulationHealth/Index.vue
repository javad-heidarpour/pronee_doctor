<script setup>
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object, clinics: Array, filters: Object, disclosure: Object,
  summary: Object, monthly: Array, problems: Array, vitals: Array,
  generatedAt: String, definitions: Object,
})
const n = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0))
const apply = event => router.get(route('portal.population-health.index'),
  Object.fromEntries(new FormData(event.target).entries()),
  { preserveState: true, preserveScroll: true })
const periodLabel = value => value.split('-').reverse().join('/')
</script>

<template>
  <PortalLayout :doctor="doctor" title="روند سلامت جمعیت بیماران">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 to-cyan-800 p-6 text-white">
        <p class="text-xs font-bold text-cyan-200">PRONEEX · POPULATION HEALTH</p>
        <h1 class="mt-2 text-2xl font-black">روندهای سلامت جمعیت بیماران</h1>
        <p class="mt-2 text-sm text-white/80">گزارش توصیفی و تجمیعی؛ بدون نمایش پرونده یا شناسه فردی.</p>
      </header>

      <form @submit.prevent="apply" class="grid gap-3 rounded-2xl border bg-white p-4 sm:grid-cols-4">
        <label class="text-xs">از تاریخ<input name="from" type="date" :value="filters.from" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <label class="text-xs">تا تاریخ<input name="to" type="date" :value="filters.to" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <label class="text-xs">شعبه<select name="clinic_id" :value="filters.clinic_id || ''" class="mt-1 w-full rounded-xl border-slate-300"><option value="">همه شعب مجاز</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select></label>
        <button class="self-end rounded-xl bg-cyan-800 px-4 py-2.5 text-sm font-bold text-white">اعمال فیلتر</button>
      </form>

      <section v-if="disclosure.suppressed" class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
        <h2 class="font-black">حفاظت از حریم خصوصی</h2>
        <p class="mt-2 text-sm leading-7">تعداد بیماران این گروه کمتر از حداقل {{ n(disclosure.minimum_cohort_size) }} نفر است؛ بنابراین آمار جزئی نمایش داده نمی‌شود. بازه زمانی یا دامنه شعب را به‌صورت مجاز گسترده‌تر کنید.</p>
      </section>

      <template v-else>
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">بیماران یکتا</span><b class="mt-2 block text-2xl">{{ n(summary.patients) }}</b></article>
          <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">کل نوبت‌ها</span><b class="mt-2 block text-2xl">{{ n(summary.appointments) }}</b></article>
          <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">مراجعات انجام‌شده</span><b class="mt-2 block text-2xl text-emerald-700">{{ n(summary.completed) }}</b></article>
          <article class="rounded-2xl border bg-white p-4"><span class="text-xs text-slate-500">نوبت‌های لغوشده</span><b class="mt-2 block text-2xl text-amber-700">{{ n(summary.cancelled) }}</b></article>
        </section>

        <section class="rounded-2xl border bg-white p-4">
          <h2 class="font-black">روند ماهانه مراجعه</h2>
          <div v-if="monthly.length" class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[520px] text-right text-sm">
              <thead class="bg-slate-50"><tr><th class="p-3">ماه</th><th class="p-3">بیمار یکتا</th><th class="p-3">نوبت</th><th class="p-3">انجام‌شده</th><th class="p-3">لغو</th></tr></thead>
              <tbody><tr v-for="row in monthly" :key="row.period" class="border-t">
                <td class="p-3 font-bold">{{ periodLabel(row.period) }}</td>
                <template v-if="row.suppressed"><td colspan="4" class="p-3 text-xs text-slate-500">به‌دلیل کوچک بودن گروه، جزئیات پنهان است.</td></template>
                <template v-else><td class="p-3">{{ n(row.patient_count) }}</td><td class="p-3">{{ n(row.appointment_count) }}</td><td class="p-3">{{ n(row.completed_count) }}</td><td class="p-3">{{ n(row.cancelled_count) }}</td></template>
              </tr></tbody>
            </table>
          </div>
          <p v-else class="py-8 text-center text-sm text-slate-500">در بازه انتخابی داده‌ای وجود ندارد.</p>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-2xl border bg-white p-4">
            <h2 class="font-black">گروه‌بندی مشکلات فعال</h2>
            <p class="mt-1 text-xs text-slate-500">تعداد بیماران دارای مشکل ثبت‌شده؛ نه شیوع قطعی بیماری.</p>
            <div v-if="problems.length" class="mt-3 divide-y"><div v-for="item in problems" :key="item.category" class="flex justify-between py-3 text-sm"><span>{{ item.category }}</span><b>{{ n(item.patient_count) }} نفر</b></div></div>
            <p v-else class="py-6 text-center text-xs text-slate-500">گروه قابل انتشار وجود ندارد.</p>
          </article>
          <article class="rounded-2xl border bg-white p-4">
            <h2 class="font-black">ثبت علائم و اندازه‌گیری‌ها</h2>
            <p class="mt-1 text-xs text-slate-500">بر اساس نوع اندازه‌گیری و واحد ثبت‌شده؛ بدون قضاوت طبیعی یا غیرطبیعی.</p>
            <div v-if="vitals.length" class="mt-3 divide-y"><div v-for="item in vitals" :key="item.measurement_type + item.unit" class="flex justify-between gap-3 py-3 text-sm"><span>{{ item.measurement_type }} <small class="text-slate-500">({{ item.unit }})</small></span><span class="text-left"><b>{{ n(item.patient_count) }} نفر</b><small class="block text-slate-500">{{ n(item.measurement_count) }} ثبت</small></span></div></div>
            <p v-else class="py-6 text-center text-xs text-slate-500">گروه قابل انتشار وجود ندارد.</p>
          </article>
        </section>
      </template>

      <section class="rounded-2xl border bg-slate-50 p-4">
        <h2 class="font-black">تعریف و محدودیت شاخص‌ها</h2>
        <ul class="mt-2 list-disc space-y-1 pr-5 text-xs leading-6 text-slate-700">
          <li>{{ definitions.cohort }}</li><li>{{ definitions.completed }}</li><li>{{ definitions.problems }}</li><li>{{ definitions.vitals }}</li>
          <li>هر گروه و هر ماه با کمتر از {{ n(disclosure.minimum_cohort_size) }} بیمار یکتا از جزئیات محروم می‌شود.</li>
          <li>این گزارش ابزار پایش توصیفی است؛ برای تشخیص، تصمیم درمانی یا برآورد شیوع جمعیتی اعتبارسنجی نشده است.</li>
        </ul>
      </section>
      <p class="text-left text-[11px] text-slate-400">تهیه‌شده: {{ new Date(generatedAt).toLocaleString('fa-IR') }}</p>
    </main>
  </PortalLayout>
</template>

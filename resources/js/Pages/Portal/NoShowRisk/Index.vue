<template>
  <PortalLayout :doctor="doctor" title="بررسی سابقه عدم حضور">
    <div dir="rtl" class="space-y-5">
      <section class="rounded-2xl bg-slate-900 p-5 text-white sm:p-7">
        <p class="text-xs font-bold tracking-widest text-cyan-200">PRONEEX APPOINTMENT ATTENDANCE</p>
        <h2 class="mt-2 text-2xl font-black">تحلیل سابقه حضور بیماران</h2>
        <p class="mt-2 text-sm leading-7 text-slate-200">این صفحه نتایج حضور ثبت‌شده در ۲۴ ماه اخیر را بررسی و نرخ تاریخی عدم حضور را محاسبه می‌کند. خروجی، احتمال قطعی یا مدل یادگیری ماشین نیست.</p>
      </section>
      <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">بیماران دارای سابقه</p><p class="mt-2 text-2xl font-black">{{ fa(stats.patients) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-amber-700">سابقه ناکافی</p><p class="mt-2 text-2xl font-black text-amber-700">{{ fa(stats.insufficient_history) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-rose-700">نرخ تاریخی بالا</p><p class="mt-2 text-2xl font-black text-rose-700">{{ fa(stats.high_historical_rate) }}</p></div>
      </section>
      <section v-if="patients.length" class="space-y-3">
        <article v-for="patient in patients" :key="patient.patient_id" class="rounded-2xl border bg-white p-4 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h3 class="font-black text-slate-900">{{ patient.patient_name }}</h3><p class="mt-1 text-xs text-slate-500">{{ fa(patient.observed_appointments) }} نتیجه ثبت‌شده · {{ fa(patient.attended_count) }} حضور · {{ fa(patient.no_show_count) }} عدم حضور</p></div>
            <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(patient.risk_band)">{{ patient.risk_label }}</span>
          </div>
          <p class="mt-3 text-sm text-slate-700">نرخ تاریخی عدم حضور: <strong>{{ patient.historical_no_show_rate === null ? 'قابل محاسبه نیست' : fa(patient.historical_no_show_rate) + '٪' }}</strong></p>
          <div class="mt-3 space-y-2">
            <div v-for="appointment in patient.appointments" :key="appointment.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-3 text-xs">
              <span>نوبت {{ fa(appointment.id) }} · {{ appointment.date }} · {{ appointment.time || '—' }}</span>
              <span v-if="appointment.attendance_outcome === 'attended'" class="font-bold text-emerald-700">حضور ثبت شده</span>
              <span v-else-if="appointment.attendance_outcome === 'no_show'" class="font-bold text-rose-700">عدم حضور ثبت شده</span>
              <div v-else class="flex flex-wrap items-center gap-2">
                <span class="text-slate-500">نتیجه نامشخص</span>
                <button v-if="appointment.status === 'visited' || appointment.status === 'completed'" type="button" @click="recordOutcome(appointment.id, 'attended')" class="rounded-lg bg-emerald-100 px-2 py-1 font-bold text-emerald-800">ثبت حضور</button>
                <button type="button" @click="recordOutcome(appointment.id, 'no_show')" class="rounded-lg bg-rose-100 px-2 py-1 font-bold text-rose-800">ثبت عدم حضور</button>
              </div>
            </div>
          </div>
        </article>
      </section>
      <section v-else class="rounded-xl border border-dashed p-10 text-center text-sm text-slate-500">سابقه نوبت گذشته برای این مطب‌ها ثبت نشده است.</section>
      <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950"><strong>محدودیت:</strong> لغو، نوبت آینده و نتیجه نامشخص در محاسبه عدم حضور وارد نمی‌شوند. تا زمانی که حداقل سه نتیجه واقعی برای هر بیمار ثبت نشده باشد، سابقه ناکافی نمایش داده می‌شود. این آمار نباید مبنای محروم‌کردن بیمار از نوبت یا دریافت وجه اضافی باشد.</section>
    </div>
  </PortalLayout>
</template>

<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { router } from '@inertiajs/vue3'

defineProps({
  doctor: { type: Object, required: true },
  patients: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ patients: 0, insufficient_history: 0, high_historical_rate: 0 }) },
})
const recordOutcome = (appointmentId, outcome) => router.patch(
  route('portal.appointments.attendance-outcome', appointmentId),
  { attendance_outcome: outcome },
  { preserveScroll: true },
)
const fa = value => Number(value || 0).toLocaleString('fa-IR')
const badgeClass = band => ({
  high: 'bg-rose-100 text-rose-800',
  moderate: 'bg-amber-100 text-amber-800',
  low: 'bg-emerald-100 text-emerald-800',
  insufficient: 'bg-slate-100 text-slate-700',
}[band] || 'bg-slate-100 text-slate-700')
</script>

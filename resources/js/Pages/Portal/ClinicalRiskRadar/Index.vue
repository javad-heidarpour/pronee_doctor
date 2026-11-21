<template>
  <PortalLayout :doctor="doctor" title="رادار هوشمند خطر بیماران">
    <div dir="rtl" class="space-y-5">
      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-gradient-to-l from-slate-900 via-slate-800 to-teal-900 p-5 text-white sm:p-7">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold tracking-widest text-teal-200">PRONEEX CLINICAL RISK RADAR</p>
              <h2 class="mt-2 text-xl font-black sm:text-2xl">رادار هوشمند پایش بیماران</h2>
              <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-200">نمایی اولویت‌بندی‌شده از داده‌های ثبت‌شده برای کمک به شناسایی بیمارانی که ممکن است به بازبینی کادر درمان نیاز داشته باشند.</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-xs text-slate-100">آخرین محاسبه: {{ formatDateTime(generatedAt) }}</div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-5">
          <div class="bg-white p-4"><p class="text-xs text-slate-500">کل بیماران</p><p class="mt-2 text-2xl font-black text-slate-800">{{ fa(stats.total) }}</p></div>
          <div class="bg-white p-4"><p class="text-xs text-rose-600">نیازمند بررسی فوری</p><p class="mt-2 text-2xl font-black text-rose-700">{{ fa(stats.urgent) }}</p></div>
          <div class="bg-white p-4"><p class="text-xs text-amber-600">نیازمند بررسی</p><p class="mt-2 text-2xl font-black text-amber-700">{{ fa(stats.review) }}</p></div>
          <div class="bg-white p-4"><p class="text-xs text-emerald-600">بدون پرچم فعال</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ fa(stats.routine) }}</p></div>
          <div class="bg-white p-4"><p class="text-xs text-slate-500">داده ناکافی</p><p class="mt-2 text-2xl font-black text-slate-600">{{ fa(stats.unknown) }}</p></div>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form @submit.prevent="applyFilters" class="grid gap-3 sm:grid-cols-[1fr_220px_auto_auto]">
          <input v-model="search" type="search" maxlength="120" placeholder="جستجوی نام بیمار" class="min-w-0 rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-100" />
          <select v-model="priority" class="rounded-xl border-slate-200 text-sm focus:border-teal-500 focus:ring-teal-100">
            <option value="all">همه اولویت‌ها</option>
            <option value="urgent">بررسی فوری</option>
            <option value="review">نیازمند بررسی</option>
            <option value="routine">بدون پرچم فعال</option>
            <option value="unknown">داده کافی نیست</option>
          </select>
          <button class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-800">اعمال فیلتر</button>
          <button type="button" @click="clearFilters" class="rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-200">پاک کردن</button>
        </form>
      </section>

      <section v-if="patients.length" class="space-y-3">
        <article v-for="patient in patients" :key="patient.patient_id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-lg font-black text-slate-600">{{ initials(patient.patient_name) }}</div>
              <div class="min-w-0"><h3 class="truncate font-black text-slate-800">{{ patient.patient_name || 'بیمار بدون نام' }}</h3><p class="mt-1 text-xs text-slate-500">شناسه پرونده: {{ fa(patient.patient_id) }} · {{ fa(patient.clinic_measurements_count) }} اندازه‌گیری مرکز · {{ fa(patient.home_measurements_count) }} ثبت خانگی</p></div>
            </div>
            <span class="rounded-full px-3 py-1.5 text-xs font-black" :class="priorityClass(patient.priority)">{{ patient.priority_label }}</span>
          </div>
          <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:px-5">
            <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] text-slate-500">پرچم‌های فعال</p><p class="mt-1 text-lg font-black text-slate-800">{{ fa(patient.flags_count) }}</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] text-slate-500">پیگیری عقب‌افتاده</p><p class="mt-1 text-lg font-black text-slate-800">{{ fa(patient.overdue_followups_count) }}</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] text-slate-500">برنامه دارویی فعال</p><p class="mt-1 text-lg font-black text-slate-800">{{ fa(patient.active_medication_plans_count) }}</p></div>
            <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] text-slate-500">آخرین اندازه‌گیری مرکز</p><p class="mt-1 text-sm font-black text-slate-800">{{ formatDateTime(patient.last_clinic_measurement_at) }}</p></div>
          </div>
          <div v-if="patient.flags.length" class="space-y-2 px-4 pb-4 sm:px-5">
            <div v-for="(flag, index) in patient.flags" :key="index" class="rounded-xl border p-3" :class="flag.level === 'urgent' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'">
              <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-black" :class="flag.level === 'urgent' ? 'text-rose-800' : 'text-amber-900'">{{ flag.title }}</p><span class="text-[10px] font-bold text-slate-500">{{ flag.source_label }}</span></div>
              <p class="mt-1 text-xs leading-6 text-slate-700">{{ flag.description }}</p>
              <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-slate-500"><span v-if="flag.value_label">مقدار: {{ flag.value_label }}</span><span v-if="flag.recorded_at">زمان ثبت: {{ formatDateTime(flag.recorded_at) }}</span></div>
            </div>
          </div>
          <div v-else-if="patient.priority === 'unknown'" class="px-5 pb-4 text-xs text-slate-600">برای این بیمار داده‌های اخیر کافی جهت غربالگری در دسترس نیست؛ نبود پرچم به معنی کم‌خطر بودن بیمار نیست.</div>
          <div v-else class="px-5 pb-4 text-xs text-emerald-700">بر اساس داده‌های در دسترس، پرچم فعالی برای بازبینی ثبت نشده است. این وضعیت به معنی تأیید سلامت بیمار نیست.</div>
        </article>
      </section>

      <section v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center">
        <div class="text-4xl">🩺</div><h3 class="mt-3 font-black text-slate-800">موردی برای نمایش وجود ندارد</h3><p class="mt-1 text-sm text-slate-500">فیلترها را تغییر دهید یا پس از ثبت داده‌های بالینی دوباره بررسی کنید.</p>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950">
        <p class="font-black">ملاحظات ایمنی و تفسیر</p>
        <ul class="mt-1 list-disc space-y-1 pr-5">
          <li>این رادار ابزار پشتیبان تصمیم و اولویت‌بندی است، نه تشخیص، پیش‌بینی قطعی یا جایگزین قضاوت پزشک.</li>
          <li>آستانه‌های فعلی پیش‌فرض غربالگری‌اند، نه پروتکل تأییدشده؛ پیش از استفاده عملیاتی باید به تأیید مسئول پزشکی مرکز برسند و با سن، بارداری، بیماری زمینه‌ای، داروها و علائم تفسیر شوند.</li>
          <li>مقادیر ثبت‌شده توسط بیمار جدا از اندازه‌گیری مرکز نشان داده می‌شوند و برای پرچم فوری کافی نیستند؛ ابتدا باید تأیید شوند.</li>
          <li>پرچم فوری نیازمند ارزیابی سریع بالینی است. در صورت علائم خطر یا وضعیت ناپایدار، مسیر اورژانس محلی را فعال کنید.</li>
          <li>ثبت‌نشدن مصرف دارو به معنی فراموشی نیست؛ فقط نوبت‌هایی که بیمار صریحاً «مصرف نشد» ثبت کرده در این گزارش شمرده می‌شوند.</li>
        </ul>
      </section>
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
  stats: { type: Object, default: () => ({ total: 0, urgent: 0, review: 0, routine: 0, unknown: 0 }) },
  filters: { type: Object, default: () => ({ search: '', priority: 'all' }) },
  generatedAt: { type: String, default: null },
})
const search = ref(props.filters.search || '')
const priority = ref(props.filters.priority || 'all')
const applyFilters = () => router.get(route('portal.clinical-risk.index'), { search: search.value || undefined, priority: priority.value }, { preserveState: true, preserveScroll: true })
const clearFilters = () => { search.value = ''; priority.value = 'all'; applyFilters() }
const fa = value => Number(value || 0).toLocaleString('fa-IR')
const initials = value => (value || '؟').trim().split(/\s+/).map(part => part[0]).slice(0, 2).join('')
const formatDateTime = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const priorityClass = value => ({ urgent: 'bg-rose-100 text-rose-800', review: 'bg-amber-100 text-amber-800', routine: 'bg-emerald-100 text-emerald-800', unknown: 'bg-slate-100 text-slate-700' }[value] || 'bg-slate-100 text-slate-700')
</script>

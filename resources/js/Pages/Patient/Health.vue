<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  hasAccess: Boolean, pendingRequest: Boolean, accesses: Array, accessType: String,
  patient: Object, encounters: Array, followUps: Array, caregivers: Array,
})
const linkForm = useForm({ national_code: '', phone: '' })
const caregiverForm = useForm({ email: '' })
const requestAccess = () => linkForm.post(route('patient.health.access.request'))
const grantCaregiver = () => caregiverForm.post(route('patient.health.caregivers.store'), { onSuccess: () => caregiverForm.reset() })
const revoke = access => {
  if (window.confirm('دسترسی این مراقب لغو شود؟')) router.delete(route('patient.health.caregivers.revoke', access.id))
}
const activeMeds = computed(() => (props.patient?.clinical_records || []).filter(x => x.kind === 'medication'))
const allergies = computed(() => (props.patient?.clinical_records || []).filter(x => x.kind === 'allergy'))
const latestVitals = computed(() => (props.patient?.vital_signs || []).slice(0, 8))
const dateLabel = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium' }).format(new Date(value)) : 'ثبت نشده'
const statusLabel = value => ({ pending: 'در انتظار', confirmed: 'تأیید شده', visited: 'انجام شده', cancelled: 'لغو شده', in_progress: 'در حال پیگیری' }[value] || value)
</script>

<template>
  <div dir="rtl" class="min-h-screen bg-slate-50 text-slate-800">
    <header class="bg-gradient-to-l from-teal-950 via-teal-800 to-cyan-800 px-4 py-8 text-white">
      <div class="mx-auto max-w-6xl">
        <p class="text-xs font-bold tracking-widest text-teal-100">PRONEEX · PERSONAL HEALTH</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">همراه سلامت من</h1>
        <p class="mt-2 text-sm text-teal-50">سوابق، برنامه پیگیری و اطلاعات سلامت شما در یک مکان امن.</p>
      </div>
    </header>
    <main class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
      <section v-if="!hasAccess" class="mx-auto max-w-xl rounded-2xl border bg-white p-6 shadow-sm">
        <div class="mb-4 text-4xl">🔐</div>
        <h2 class="text-lg font-black">اتصال امن پرونده سلامت</h2>
        <p class="mt-2 text-sm leading-7 text-slate-500">برای اتصال حساب کاربری به پرونده درمانی، کد ملی را وارد کنید. مرکز درمانی پیش از فعال‌سازی، هویت شما را بررسی خواهد کرد. تا زمان تأیید هیچ اطلاعات پزشکی نمایش داده نمی‌شود.</p>
        <form @submit.prevent="requestAccess" class="mt-5 space-y-3">
          <input v-model="linkForm.national_code" inputmode="numeric" maxlength="10" placeholder="کد ملی ۱۰ رقمی" class="w-full rounded-xl border-slate-300 text-sm">\n          <input v-model="linkForm.phone" inputmode="tel" maxlength="20" placeholder="شماره تلفن ثبت‌شده در پرونده" class="w-full rounded-xl border-slate-300 text-sm">
          <p v-if="linkForm.errors.national_code" class="text-xs text-rose-600">{{ linkForm.errors.national_code }}</p>
          <button :disabled="linkForm.processing || pendingRequest" class="w-full rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50">{{ pendingRequest ? 'درخواست شما در انتظار بررسی است' : 'درخواست اتصال پرونده' }}</button>
        </form>
        <p class="mt-4 text-xs leading-6 text-slate-400">برای امنیت، نتیجه بررسی تطبیق اطلاعات در این صفحه افشا نمی‌شود. در صورت نیاز با مرکز درمانی تماس بگیرید.</p>
      </section>

      <template v-else>
        <section class="rounded-2xl border bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs text-teal-700">پرونده سلامت متصل</p><h2 class="mt-1 text-xl font-black">{{ patient.name }}</h2><p class="mt-1 text-xs text-slate-500">{{ patient.doctor?.caption }} {{ patient.doctor?.name }} {{ patient.doctor?.family }} · {{ patient.doctor?.specialty_title }}</p></div>
            <span class="rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">{{ accessType === 'owner' ? 'دسترسی صاحب پرونده' : 'دسترسی مراقب' }}</span>
          </div>
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">مراجعات ثبت‌شده</p><p class="mt-2 text-2xl font-black">{{ patient.appointments?.length || 0 }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">سوابق پزشکی فعال</p><p class="mt-2 text-2xl font-black">{{ patient.clinical_records?.length || 0 }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">برنامه‌های پیگیری</p><p class="mt-2 text-2xl font-black">{{ followUps.length }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">اندازه‌گیری‌های ثبت‌شده</p><p class="mt-2 text-2xl font-black">{{ patient.vital_signs?.length || 0 }}</p></div>
        </section>
        <section class="grid gap-4 lg:grid-cols-2">
          <div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">داروهای ثبت‌شده</h3><div v-for="item in activeMeds" :key="item.id" class="border-b py-3 last:border-0"><p class="text-sm font-bold">{{ item.title }}</p><p class="mt-1 text-xs text-slate-500">{{ item.dosage }} · {{ item.frequency }}</p><p v-if="item.description" class="mt-1 text-xs">{{ item.description }}</p></div><p v-if="!activeMeds.length" class="text-xs text-slate-400">دارویی ثبت نشده است.</p></div>
          <div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">حساسیت‌ها</h3><div v-for="item in allergies" :key="item.id" class="mb-2 rounded-xl bg-rose-50 p-3"><p class="text-sm font-bold text-rose-800">{{ item.title }}</p><p class="mt-1 text-xs text-rose-700">{{ item.description }}</p></div><p v-if="!allergies.length" class="text-xs text-slate-400">حساسیتی در پرونده ثبت نشده است.</p></div>
        </section>
        <section v-if="accessType === 'owner'" class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">روند علائم و اندازه‌گیری‌های من</h3><div class="grid gap-2 sm:grid-cols-2"><article v-for="vital in latestVitals" :key="vital.id" class="rounded-xl bg-slate-50 p-3"><div class="flex justify-between gap-2"><b class="text-sm">{{ vital.measurement_type }}</b><span class="text-xs text-slate-500">{{ dateLabel(vital.measured_at) }}</span></div><p class="mt-2 text-lg font-black text-teal-800">{{ vital.value }} <span class="text-xs font-medium">{{ vital.unit }}</span></p><p v-if="vital.note" class="mt-1 text-xs text-slate-500">{{ vital.note }}</p></article></div><p v-if="!latestVitals.length" class="text-xs text-slate-400">اندازه‌گیری‌ای ثبت نشده است.</p></section>
        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">برنامه پیگیری من</h3><div v-for="task in followUps" :key="task.id" class="flex flex-wrap justify-between gap-2 border-b py-3 last:border-0"><div><p class="text-sm font-bold">{{ task.title }}</p><p class="mt-1 text-xs text-slate-500">{{ task.description }}</p></div><span class="text-xs text-teal-700">{{ dateLabel(task.due_at) }}</span></div><p v-if="!followUps.length" class="text-xs text-slate-400">پیگیری فعالی ثبت نشده است.</p></section>
        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">خط زمانی مراجعات</h3><div v-for="visit in patient.appointments" :key="visit.id" class="flex flex-wrap justify-between gap-2 border-b py-3 last:border-0"><div><p class="text-sm font-bold">{{ visit.clinic?.name || 'مرکز درمانی' }}</p><p class="mt-1 text-xs text-slate-500">{{ visit.appointment_time }} · {{ visit.visit_number || 'ویزیت' }}</p></div><div class="text-left"><p class="text-xs">{{ dateLabel(visit.appointment_date) }}</p><span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-1 text-[11px]">{{ statusLabel(visit.status) }}</span></div></div><p v-if="!patient.appointments?.length" class="text-xs text-slate-400">مراجعه‌ای ثبت نشده است.</p></section>
        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">سوابق ویزیت نهایی‌شده</h3><article v-for="encounter in encounters" :key="encounter.id" class="mb-3 rounded-xl bg-slate-50 p-4"><div class="flex flex-wrap justify-between gap-2"><b class="text-sm">{{ encounter.diagnosis || encounter.subjective || 'یادداشت ویزیت' }}</b><span class="text-xs text-slate-500">{{ dateLabel(encounter.signed_at) }}</span></div><p class="mt-2 text-xs leading-6">{{ encounter.plan }}</p><p class="mt-2 text-xs text-slate-500">{{ encounter.doctor?.name }} {{ encounter.doctor?.family }}</p></article><p v-if="!encounters.length" class="text-xs text-slate-400">یادداشت نهایی‌شده‌ای در دسترس نیست.</p></section>
        <section v-if="accessType === 'owner'" class="rounded-2xl border bg-white p-5">
          <h3 class="font-black">دسترسی خانواده و مراقب</h3><p class="mt-1 text-xs leading-6 text-slate-500">با ایمیل حساب کاربری پرونیکس، دسترسی محدود و فقط‌خواندنی به خلاصه سلامت، مراجعات و پیگیری‌ها بدهید. دسترسی پس از ۹۰ روز منقضی می‌شود.</p>
          <form @submit.prevent="grantCaregiver" class="mt-3 flex flex-col gap-2 sm:flex-row"><input v-model="caregiverForm.email" type="email" placeholder="ایمیل حساب مراقب" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm"><button :disabled="caregiverForm.processing" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white">اعطای دسترسی</button></form>
          <div v-for="access in caregivers" :key="access.id" class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-slate-50 p-3"><div><p class="text-sm font-bold">{{ access.user?.name }} {{ access.user?.family }}</p><p class="text-xs text-slate-500">{{ access.user?.email }}</p></div><button @click="revoke(access)" class="text-xs font-bold text-rose-600">لغو دسترسی</button></div>
        </section>
        <section class="rounded-xl bg-amber-50 p-4 text-xs leading-6 text-amber-900">اطلاعات این صفحه برای مشاهده سوابق است و جایگزین تشخیص یا توصیه پزشکی نیست. در صورت وضعیت اورژانسی با خدمات اورژانس تماس بگیرید. مقادیر آزمایش و علائم حیاتی باید توسط پزشک تفسیر شوند.</section>
      </template>
    </main>
  </div>
</template>

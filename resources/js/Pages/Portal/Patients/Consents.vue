<script setup>
import { useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: { type: Object, required: true },
  patient: { type: Object, required: true },
  consents: { type: Object, required: true },
  clinics: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  types: { type: Array, default: () => [] },
})

const form = useForm({
  clinic_id: props.clinics[0]?.id ?? '',
  appointment_id: '',
  consent_type: 'treatment',
  title: '',
  consent_text: '',
})

const statusLabel = status => status === 'signed' ? 'امضا شده' : 'در انتظار امضا'
const typeLabel = value => props.types.find(item => item.value === value)?.label ?? value
const dateLabel = value => value ? new Date(value).toLocaleString('fa-IR') : '—'
const submit = () => form.post(route('portal.patients.consents.store', props.patient.id), {
  preserveScroll: true,
  onSuccess: () => form.reset('appointment_id', 'consent_type', 'title', 'consent_text'),
})
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto w-full max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div>
          <p class="text-xs font-bold text-teal-700">پرونده بالینی</p>
          <h1 class="mt-1 text-xl font-black text-slate-900">رضایت‌نامه‌های {{ patient.name }}</h1>
          <p class="mt-1 text-sm text-slate-500">متن رضایت‌نامه پس از ثبت برای بیمار قفل می‌شود و نسخه امضاشده قابل ویرایش یا حذف نیست.</p>
        </div>
        <Link :href="route('portal.patients.directory')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700">بازگشت به بیماران</Link>
      </header>

      <section v-if="canManage" class="rounded-2xl border border-teal-100 bg-white p-5 shadow-sm">
        <h2 class="font-black text-slate-900">ایجاد رضایت‌نامه برای بیمار</h2>
        <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="space-y-1 text-sm font-bold text-slate-700">
            مطب
            <select v-model="form.clinic_id" required class="w-full rounded-xl border-slate-200 text-sm">
              <option value="" disabled>انتخاب مطب</option>
              <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
            </select>
            <span v-if="form.errors.clinic_id" class="block text-xs text-rose-600">{{ form.errors.clinic_id }}</span>
          </label>
          <label class="space-y-1 text-sm font-bold text-slate-700">
            نوع رضایت‌نامه
            <select v-model="form.consent_type" required class="w-full rounded-xl border-slate-200 text-sm">
              <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
            <span v-if="form.errors.consent_type" class="block text-xs text-rose-600">{{ form.errors.consent_type }}</span>
          </label>
          <label class="space-y-1 text-sm font-bold text-slate-700 sm:col-span-2">
            عنوان
            <input v-model="form.title" required maxlength="180" class="w-full rounded-xl border-slate-200 text-sm" placeholder="عنوان اقدام یا درمان">
            <span v-if="form.errors.title" class="block text-xs text-rose-600">{{ form.errors.title }}</span>
          </label>
          <label class="space-y-1 text-sm font-bold text-slate-700 sm:col-span-2">
            متن کامل رضایت‌نامه
            <textarea v-model="form.consent_text" required minlength="40" maxlength="20000" rows="8" class="w-full rounded-xl border-slate-200 text-sm leading-7" placeholder="شرح اقدام، منافع مورد انتظار، خطرات و عوارض احتمالی، گزینه‌های جایگزین و فرصت پرسش بیمار را کامل درج کنید."></textarea>
            <span v-if="form.errors.consent_text" class="block text-xs text-rose-600">{{ form.errors.consent_text }}</span>
            <span class="block text-xs font-normal text-slate-500">پس از ثبت، متن دقیق و هش آن نگهداری می‌شود؛ نسخه امضاشده تغییر نمی‌کند.</span>
          </label>
          <div class="sm:col-span-2 flex justify-end">
            <button :disabled="form.processing || !clinics.length" class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">{{ form.processing ? 'در حال ثبت…' : 'ثبت و ارسال برای امضای بیمار' }}</button>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h2 class="font-black text-slate-900">سوابق رضایت‌نامه</h2></div>
        <div v-if="consents.data?.length" class="divide-y divide-slate-100">
          <article v-for="consent in consents.data" :key="consent.id" class="flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="min-w-0">
              <h3 class="font-bold text-slate-900">{{ consent.title }}</h3>
              <p class="mt-1 text-xs text-slate-500">{{ typeLabel(consent.consent_type) }} · {{ consent.clinic?.name ?? 'مطب' }} · {{ dateLabel(consent.created_at) }}</p>
              <p v-if="consent.status === 'signed'" class="mt-1 text-xs text-emerald-700">امضاکننده: {{ consent.signer?.name }} {{ consent.signer?.family }} · {{ dateLabel(consent.signed_at) }}</p>
            </div>
            <span class="rounded-lg px-3 py-1.5 text-xs font-bold" :class="consent.status === 'signed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'">{{ statusLabel(consent.status) }}</span>
          </article>
        </div>
        <p v-else class="p-8 text-center text-sm text-slate-500">هنوز رضایت‌نامه‌ای برای این بیمار ثبت نشده است.</p>
      </section>
    </main>
  </PortalLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  consent: { type: Object, required: true },
  patientName: { type: String, required: true },
})

const form = useForm({
  signer_name: props.patientName,
  accept: false,
})

const sign = () => form.post(route('patient.health.consents.sign', props.consent.id), {
  preserveScroll: true,
})
const dateLabel = value => value ? new Date(value).toLocaleString('fa-IR') : '—'
</script>

<template>
  <div dir="rtl" class="min-h-screen bg-slate-50 px-4 py-8 text-slate-800">
    <main class="mx-auto max-w-3xl space-y-5">
      <header class="rounded-2xl bg-gradient-to-l from-teal-950 to-teal-700 p-6 text-white">
        <p class="text-xs font-bold text-teal-100">PRONEEX · رضایت آگاهانه</p>
        <h1 class="mt-2 text-2xl font-black">{{ consent.title }}</h1>
        <p class="mt-2 text-sm text-teal-50">{{ consent.doctor?.name }} {{ consent.doctor?.family }} · {{ consent.clinic?.name }}</p>
      </header>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-black">متن رضایت‌نامه</h2>
          <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs text-slate-600">{{ dateLabel(consent.created_at) }}</span>
        </div>
        <div class="mt-4 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-4 text-sm leading-8 text-slate-700">{{ consent.consent_text }}</div>
      </section>

      <section v-if="consent.status === 'signed'" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <h2 class="font-black text-emerald-900">این رضایت‌نامه امضا شده است</h2>
        <p class="mt-2 text-sm text-emerald-800">امضاکننده: {{ consent.signer_name }} · تاریخ ثبت: {{ dateLabel(consent.signed_at) }}</p>
        <p class="mt-2 text-xs text-emerald-700">نسخه امضاشده قفل است و امکان تغییر متن یا امضا وجود ندارد.</p>
      </section>

      <section v-else class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
        <h2 class="font-black text-slate-900">ثبت امضای الکترونیکی</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">با واردکردن نام مطابق پرونده و تأیید صریح، اعلام می‌کنید متن را مطالعه کرده‌اید و فرصت طرح پرسش داشته‌اید. امضا با حساب مالک پرونده ثبت و به نسخه فعلی متن متصل می‌شود.</p>
        <form class="mt-4 space-y-4" @submit.prevent="sign">
          <label class="block space-y-1 text-sm font-bold text-slate-700">
            نام و نام خانوادگی مطابق پرونده
            <input v-model="form.signer_name" required maxlength="180" class="w-full rounded-xl border-slate-200 text-sm">
            <span v-if="form.errors.signer_name" class="block text-xs text-rose-600">{{ form.errors.signer_name }}</span>
          </label>
          <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm leading-7 text-slate-700">
            <input v-model="form.accept" type="checkbox" required class="mt-1 rounded border-slate-300 text-teal-700">
            <span>متن رضایت‌نامه را خوانده‌ام، مفهوم آن را درک کرده‌ام، امکان پرسش داشته‌ام و با ثبت امضای الکترونیکی خود موافقم.</span>
          </label>
          <span v-if="form.errors.accept" class="block text-xs text-rose-600">{{ form.errors.accept }}</span>
          <button :disabled="form.processing" class="w-full rounded-xl bg-teal-700 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">{{ form.processing ? 'در حال ثبت امضا…' : 'تأیید و ثبت امضای الکترونیکی' }}</button>
        </form>
      </section>
      <footer class="text-center text-xs leading-6 text-slate-400">امضای ثبت‌شده شامل هویت حساب، زمان، نسخه متن و اثرانگشت رمزنگاری‌شده است. این سازوکار، احراز هویت قانونی مستقل یا گواهی امضای دیجیتال صادرشده توسط مرجع رسمی را جایگزین نمی‌کند.</footer>
    </main>
  </div>
</template>

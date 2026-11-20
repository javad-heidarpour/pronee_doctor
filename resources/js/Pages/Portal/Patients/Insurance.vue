<script setup>
import { reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ doctor:Object, patient:Object, verifications:Object, payers:Array })
const form = reactive({ payer_code:'tamin', payer_name:'تأمین اجتماعی', appointment_id:'', policy_number:'', member_number:'', status:'verified', valid_from:'', valid_until:'', tracking_id:'', failure_code:'', failure_message:'' })
const busy = reactive({ value:false })
const payerChanged = () => { const payer = props.payers.find(x => x.code === form.payer_code); form.payer_name = payer?.name || 'سایر' }
const submit = () => {
  if (busy.value) return
  busy.value = true
  router.post(route('portal.patients.insurance.store', props.patient.id), form, {
    preserveScroll:true,
    onFinish:()=>{busy.value=false},
    onSuccess:()=>{form.policy_number='';form.member_number='';form.tracking_id='';form.failure_code='';form.failure_message=''},
  })
}
const statusLabel = v => ({verified:'تأیید دستی',rejected:'ردشده',pending:'در انتظار',not_checked:'بررسی‌نشده',awaiting_integration:'در انتظار اتصال',not_configured:'اتصال پیکربندی نشده'}[v] || v || '—')
const dateTime = v => v ? new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)) : '—'
</script>

<template>
  <Head :title="'بیمه بیمار '+patient.name" />
  <PortalLayout :doctor="doctor" title="اطلاعات بیمه بیمار">
    <main dir="rtl" class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black">مدیریت بیمه بیمار</h1><p class="mt-1 text-sm text-slate-500">{{patient.name}} · ثبت و پیگیری پوشش بیمه</p></div><Link :href="route('portal.patients.360',patient.id)" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت به پرونده ۳۶۰</Link></header>
      <section class="rounded-2xl border bg-white p-4 sm:p-6">
        <h2 class="font-black">ثبت نتیجه بررسی بیمه</h2>
        <p class="mt-1 text-xs leading-6 text-amber-700">این فرم برای ثبت دستی نتیجه است؛ تا زمانی که اتصال رسمی بیمه‌گر فعال و قرارداد آن پیاده‌سازی نشده، سامانه ادعای استعلام آنلاین نمی‌کند.</p>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-bold">بیمه‌گر<select v-model="form.payer_code" @change="payerChanged" class="mt-1 w-full rounded-xl border-slate-300"><option v-for="p in payers" :key="p.code" :value="p.code">{{p.name}}</option></select></label>
          <label class="text-xs font-bold">نام بیمه‌گر<input v-model="form.payer_name" required maxlength="120" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">وضعیت بررسی<select v-model="form.status" class="mt-1 w-full rounded-xl border-slate-300"><option value="verified">تأییدشده دستی</option><option value="rejected">ردشده</option><option value="pending">در انتظار</option><option value="not_checked">بررسی‌نشده</option></select></label>
          <label class="text-xs font-bold">شماره بیمه‌نامه<input v-model="form.policy_number" maxlength="120" autocomplete="off" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شماره عضویت<input v-model="form.member_number" maxlength="120" autocomplete="off" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شناسه پیگیری<input v-model="form.tracking_id" maxlength="120" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شروع اعتبار<input v-model="form.valid_from" type="date" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">پایان اعتبار<input v-model="form.valid_until" type="date" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شناسه نوبت مرتبط (اختیاری)<input v-model="form.appointment_id" inputmode="numeric" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold sm:col-span-2 lg:col-span-3">توضیح نتیجه یا علت رد<textarea v-model="form.failure_message" maxlength="1000" rows="2" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
          <div class="sm:col-span-2 lg:col-span-3"><button :disabled="busy.value" class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">{{busy.value?'در حال ثبت...':'ثبت نتیجه بررسی'}}</button></div>
        </form>
      </section>
      <section class="rounded-2xl border bg-white p-4 sm:p-6">
        <h2 class="font-black">سوابق بیمه</h2>
        <div v-if="!verifications.data?.length" class="py-8 text-center text-sm text-slate-500">هنوز سابقه‌ای ثبت نشده است.</div>
        <div v-else class="mt-4 space-y-3">
          <article v-for="item in verifications.data" :key="item.id" class="rounded-xl border p-4">
            <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{item.payer_name || item.payer_code}}</strong><span class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-bold">{{statusLabel(item.status)}}</span></div>
            <div class="mt-3 grid gap-2 text-xs sm:grid-cols-3"><p>بیمه‌نامه: {{item.policy_number_masked || '—'}}</p><p>عضویت: {{item.member_number_masked || '—'}}</p><p>شناسه پیگیری: {{item.tracking_id || '—'}}</p><p>شروع اعتبار: {{item.valid_from || '—'}}</p><p>پایان اعتبار: {{item.valid_until || '—'}}</p><p>زمان بررسی: {{dateTime(item.checked_at)}}</p></div>
            <p v-if="item.failure_message" class="mt-3 text-xs leading-6 text-slate-500">{{item.failure_message}}</p>
          </article>
        </div>
      </section>
    </main>
  </PortalLayout>
</template>

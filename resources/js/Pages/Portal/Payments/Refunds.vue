<script setup>
import { reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ payments: { type: Object, default: () => ({ data: [], links: [], total: 0 }) } })
const forms = reactive({})
const busy = reactive({})
const statusLabel = value => ({ paid: 'پرداخت‌شده', refunded: 'برگشت‌خورده', processing: 'در حال پردازش', reversed: 'برگشت تأییدشده', rejected: 'ردشده', unknown: 'نامشخص' }[value] || value || '—')
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const dateTime = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const submit = payment => {
  const form = forms[payment.id]
  if (!form || String(form.reason || '').trim().length < 8 || busy[payment.id]) return
  if (!window.confirm('درخواست برگشت کامل این تراکنش به سامان‌کیش ارسال شود؟ در صورت نامشخص بودن پاسخ، ارسال مجدد خودکار انجام نخواهد شد.')) return
  busy[payment.id] = true
  router.post(route('portal.payments.refund', payment.id), { reason: form.reason }, {
    preserveScroll: true,
    onFinish: () => { busy[payment.id] = false },
  })
}
</script>

<template>
  <Head title="مدیریت برگشت وجه" />
  <PortalLayout title="مدیریت برگشت وجه">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-black">برگشت وجه پرداخت‌های نوبت</h1><p class="mt-2 text-sm leading-7 text-slate-500">فقط پرداخت‌های موفق سامان‌کیش نمایش داده می‌شوند. برگشت هر پرداخت فقط یک بار ارسال می‌شود و نتیجه نامشخص نیازمند تطبیق دستی است.</p></div>
        <Link :href="route('portal.payments.review-queue')" class="rounded-xl border px-4 py-2 text-sm font-bold">صف بررسی پرداخت‌ها</Link>
      </header>
      <section v-if="!payments.data?.length" class="rounded-2xl border bg-white p-10 text-center"><p class="font-bold">پرداخت واجد شرایطی وجود ندارد.</p></section>
      <section v-for="payment in payments.data" :key="payment.id" class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div><h2 class="font-black">پرداخت #{{ payment.id }} · نوبت #{{ payment.appointment_id }}</h2><p class="mt-1 text-xs text-slate-500">{{ payment.appointment?.appointment_date || '—' }} · {{ payment.appointment?.appointment_time || '—' }}</p></div>
          <span class="rounded-lg px-3 py-1.5 text-xs font-bold" :class="payment.refund?.status === 'reversed' || payment.status === 'refunded' ? 'bg-emerald-50 text-emerald-700' : payment.refund?.status === 'unknown' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700'">{{ statusLabel(payment.refund?.status || payment.status) }}</span>
        </div>
        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
          <div><p class="text-xs text-slate-500">مبلغ قابل برگشت</p><p class="mt-1 font-black">{{ money(payment.amount_rial) }}</p></div>
          <div><p class="text-xs text-slate-500">شناسه مرجع</p><p class="mt-1 break-all font-mono text-xs">{{ payment.ref_num || '—' }}</p></div>
          <div><p class="text-xs text-slate-500">زمان پرداخت</p><p class="mt-1">{{ dateTime(payment.paid_at) }}</p></div>
        </div>
        <div v-if="payment.refund" class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
          <p class="font-bold">درخواست ثبت‌شده</p><p class="mt-1 text-xs leading-6">{{ payment.refund.reason }}</p>
          <p v-if="payment.refund.gateway_result_message" class="mt-1 text-xs text-slate-500">{{ payment.refund.gateway_result_message }}</p>
          <p class="mt-1 text-[11px] text-slate-400">ثبت: {{ dateTime(payment.refund.requested_at) }} · تعیین تکلیف: {{ dateTime(payment.refund.processed_at) }}</p>
        </div>
        <form v-else @submit.prevent="submit(payment)" class="mt-4 space-y-2 border-t pt-4">
          <label class="block text-xs font-bold text-slate-600">دلیل برگشت وجه
            <textarea v-model="(forms[payment.id] ||= { reason: '' }).reason" minlength="8" maxlength="1000" required rows="2" class="mt-2 w-full rounded-xl border-slate-300 text-sm" placeholder="علت مستند برگشت وجه را وارد کنید"></textarea>
          </label>
          <button type="submit" :disabled="busy[payment.id] || String(forms[payment.id]?.reason || '').trim().length < 8" class="rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">{{ busy[payment.id] ? 'در حال ارسال...' : 'ارسال درخواست برگشت کامل وجه' }}</button>
        </form>
      </section>
      <nav v-if="payments.links?.length" class="flex flex-wrap gap-2"><template v-for="link in payments.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-xs" :class="link.active ? 'bg-slate-900 text-white' : 'bg-white'" v-html="link.label" /><span v-else class="rounded-lg border px-3 py-2 text-xs text-slate-300" v-html="link.label" /></template></nav>
    </main>
  </PortalLayout>
</template>

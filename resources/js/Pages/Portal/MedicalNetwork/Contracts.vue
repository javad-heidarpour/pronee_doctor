<script setup>
import { useForm, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

defineProps({ contracts: Array, incoming: Array, settlements: Array, providers: Array })
const form = useForm({ provider_id: '', service_type: 'laboratory', unit_fee_rial: '', starts_at: new Date().toISOString().slice(0, 10), ends_at: '', terms: '' })
const typeLabel = value => ({ laboratory: 'آزمایشگاه', imaging: 'تصویربرداری', rehabilitation: 'توان‌بخشی', consultation: 'مشاوره تخصصی' }[value] || value)
const statusLabel = value => ({ proposed: 'در انتظار پاسخ', active: 'فعال', rejected: 'ردشده', expired: 'منقضی', pending: 'در انتظار پرداخت', paid: 'پرداخت ثبت‌شده' }[value] || value)
const submit = () => form.post(route('portal.medical-network.contracts.store'), { onSuccess: () => form.reset('provider_id', 'unit_fee_rial', 'ends_at', 'terms') })
const respond = (contract, decision) => router.patch(route('portal.medical-network.contracts.respond', contract.id), { decision }, { preserveScroll: true })
const generate = () => router.post(route('portal.medical-network.contracts.generate'), {}, { preserveScroll: true })
const markPaid = item => router.patch(route('portal.medical-network.settlements.paid', item.id), {}, { preserveScroll: true })
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
</script>

<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4">
      <header class="rounded-2xl bg-gradient-to-l from-slate-900 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold text-teal-100">PRONEEX PARTNERSHIP ENGINE</p>
        <h1 class="mt-2 text-2xl font-black">قراردادها و تسویه همکاری پزشکی</h1>
        <p class="mt-2 text-sm leading-7 text-teal-50">تعریف تعرفه خدمت، پذیرش دوطرفه قرارداد و ثبت ردیف‌های مالی فقط برای ارجاعات تکمیل‌شده.</p>
      </header>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">پیشنهاد قرارداد جدید</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">قرارداد تا زمان پذیرش مرکز مقصد فعال نمی‌شود. مبلغ، تعرفه خدمت توافق‌شده است و به‌عنوان کمیسیون ارجاع یا پاداش معرفی بیمار محاسبه نمی‌شود.</p>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 md:grid-cols-2">
          <label class="text-xs font-semibold">مرکز همکار<select v-model="form.provider_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب مرکز</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }} · {{ p.city || '—' }}</option></select></label>
          <label class="text-xs font-semibold">نوع خدمت<select v-model="form.service_type" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="laboratory">آزمایشگاه</option><option value="imaging">تصویربرداری</option><option value="rehabilitation">توان‌بخشی</option><option value="consultation">مشاوره تخصصی</option></select></label>
          <label class="text-xs font-semibold">تعرفه توافق‌شده هر خدمت (ریال)<input v-model="form.unit_fee_rial" type="number" min="1" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">شروع اعتبار<input v-model="form.starts_at" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">پایان اعتبار (اختیاری)<input v-model="form.ends_at" type="date" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold md:col-span-2">شرایط و توضیحات<textarea v-model="form.terms" rows="3" maxlength="3000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-2">ارسال پیشنهاد قرارداد</button>
        </form>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border bg-white p-4">
          <h2 class="mb-3 font-black">قراردادهای پیشنهادی من</h2>
          <article v-for="c in contracts" :key="c.id" class="mb-2 rounded-xl bg-slate-50 p-3">
            <div class="flex justify-between gap-2"><b class="text-sm">{{ c.provider?.name }}</b><span class="text-xs text-teal-700">{{ statusLabel(c.status) }}</span></div>
            <p class="mt-1 text-xs text-slate-600">{{ typeLabel(c.service_type) }} · {{ money(c.unit_fee_rial) }} · از {{ c.starts_at }} تا {{ c.ends_at || 'بدون تاریخ پایان' }}</p>
            <p v-if="c.terms" class="mt-2 text-xs leading-6">{{ c.terms }}</p>
            <p class="mt-1 text-[11px] text-slate-500">ردیف تسویه: {{ c.settlements_count }} · مجموع: {{ money(c.settlements_sum_amount_rial) }}</p>
          </article>
          <p v-if="!contracts.length" class="text-xs text-slate-500">هنوز قراردادی ثبت نشده است.</p>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <h2 class="mb-3 font-black">پیشنهادهای دریافتی</h2>
          <article v-for="c in incoming" :key="c.id" class="mb-2 rounded-xl bg-slate-50 p-3">
            <b class="text-sm">{{ c.doctor?.name }} {{ c.doctor?.family }}</b>
            <p class="mt-1 text-xs">{{ typeLabel(c.service_type) }} · {{ money(c.unit_fee_rial) }} · {{ statusLabel(c.status) }}</p>
            <p v-if="c.terms" class="mt-2 text-xs leading-6">{{ c.terms }}</p>
            <div v-if="c.status === 'proposed'" class="mt-2 flex gap-2"><button @click="respond(c,'accept')" class="rounded-lg bg-teal-700 px-3 py-2 text-xs text-white">پذیرش</button><button @click="respond(c,'reject')" class="rounded-lg border px-3 py-2 text-xs">رد پیشنهاد</button></div>
          </article>
          <p v-if="!incoming.length" class="text-xs text-slate-500">پیشنهاد دریافتی وجود ندارد.</p>
        </div>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-black">دفتر تسویه ارجاعات تکمیل‌شده</h2><p class="mt-1 text-xs text-slate-500">تولید ردیف‌ها بر اساس قرارداد فعال و ارجاع تکمیل‌شده؛ عملیات تکراری ردیف تکراری ایجاد نمی‌کند.</p></div><button @click="generate" class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white">محاسبه / به‌روزرسانی تسویه‌ها</button></div>
        <div class="mt-4 space-y-2">
          <article v-for="item in settlements" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
            <div><b class="text-sm">{{ item.contract?.provider?.name }}</b><p class="mt-1 text-xs text-slate-500">ارجاع {{ item.referral?.tracking_code }} · {{ item.referral?.completed_at }}</p></div>
            <div class="text-left"><b class="text-sm">{{ money(item.amount_rial) }}</b><p class="text-xs text-slate-500">{{ statusLabel(item.status) }}</p></div>
            <button v-if="item.status === 'pending'" @click="markPaid(item)" class="rounded-lg border border-teal-700 px-3 py-2 text-xs text-teal-800">ثبت پرداخت</button>
          </article>
          <p v-if="!settlements.length" class="text-xs text-slate-500">هنوز ردیف تسویه‌ای ایجاد نشده است.</p>
        </div>
        <p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs leading-6 text-amber-900">ثبت پرداخت در این نسخه صرفاً ثبت داخلی وضعیت است؛ رسید بانکی، درگاه پرداخت، فاکتور رسمی، مالیات و تأیید دریافت طرف مقابل هنوز به سامانه متصل نیستند. پیش از استفاده تجاری، متن قرارداد و تعرفه‌ها باید از نظر مقررات حرفه‌ای و مالی بررسی شوند.</p>
      </section>
    </main>
  </PortalLayout>
</template>

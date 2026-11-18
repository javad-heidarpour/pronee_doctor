<script setup>
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object, canManage: Boolean, clinics: Array, patients: Array, plans: Object,
  installments: Array, expenses: Array, stats: Object, journalSummary: Array,
})

const planForm = useForm({
  clinic_id: props.clinics?.[0]?.id ?? '',
  patient_id: '', title: '', specialty_module: 'general',
  total_amount: '', discount_amount: 0, deposit_amount: 0, deposit_method: 'card', deposit_reference: '',
  installment_count: 1, first_due_date: new Date().toISOString().slice(0, 10),
  clinical_notes: '', financial_terms: '', sms_reminders_enabled: false, sms_consent_confirmed: false, follow_up_sms_enabled: false, follow_up_sms_consent_confirmed: false,
})
const expenseForm = useForm({
  clinic_id: props.clinics?.[0]?.id ?? '', category: 'اجاره',
  description: '', amount: '', expense_date: new Date().toISOString().slice(0, 10),
  payment_method: 'transfer', reference: '', notes: '',
})
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const pay = row => {
  const remaining = Number(row.amount) - Number(row.paid_amount)
  const amount = window.prompt('مبلغ دریافتی (ریال) — مانده: ' + money(remaining), String(remaining))
  if (!amount || !/^\d+$/.test(amount) || Number(amount) < 1) return
  const method = window.prompt('روش پرداخت: cash / card / transfer / online / cheque', 'card')
  if (!method) return
  router.post(route('portal.finance.installments.payments.store', row.id), { amount: Number(amount), method })
}
const revokeConsent = (plan, scope) => { if (window.confirm('ارسال پیامک‌های این نوع برای این طرح درمان متوقف شود؟')) router.post(route('portal.finance.plans.sms-consent.revoke', plan.id), { scope }) }
const addCollateral = plan => {
  const type = window.prompt('نوع وثیقه: cheque / promissory_note / guarantor / other', 'cheque')
  if (!type) return
  const identifier = window.prompt('شناسه / شماره چک یا سفته (اختیاری)', '') || ''
  const face_value = window.prompt('مبلغ اسمی وثیقه به ریال (اختیاری)', '') || ''
  const due_date = window.prompt('تاریخ سررسید (YYYY-MM-DD؛ اختیاری)', '') || ''
  router.post(route('portal.finance.collaterals.store', plan.id), { type, identifier, face_value: face_value || null, due_date: due_date || null, reminder_enabled: Boolean(due_date) })
}
const planSubmit = () => planForm.post(route('portal.finance.plans.store'), { onSuccess: () => planForm.reset('patient_id', 'title', 'total_amount', 'discount_amount', 'deposit_amount', 'clinical_notes', 'financial_terms') })
const expenseSubmit = () => expenseForm.post(route('portal.finance.expenses.store'), { onSuccess: () => expenseForm.reset('description', 'amount', 'reference', 'notes') })
const netAmount = computed(() => Math.max(0, Number(planForm.total_amount || 0) - Number(planForm.discount_amount || 0)))
</script>

<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="space-y-6">
      <header>
        <h1 class="text-xl font-black text-slate-900">مدیریت مالی و طرح درمان</h1>
        <p class="mt-1 text-sm text-slate-500">قرارداد درمان، اقساط، وثایق، دریافت‌ها و هزینه‌های هر شعبه</p>
      </header>

      <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div v-for="item in [
          {label:'طرح‌های درمان', value:stats.plans},
          {label:'مطالبات باز', value:money(stats.receivables)},
          {label:'اقساط سررسیدگذشته', value:stats.overdue},
          {label:'دریافت ثبت‌شده', value:money(stats.income)},
          {label:'هزینه ثبت‌شده', value:money(stats.expenses)}
        ]" :key="item.label" class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">{{ item.label }}</p>
          <p class="mt-2 text-lg font-black text-slate-900">{{ item.value }}</p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-3 font-extrabold">خلاصه دفتر حسابداری دوطرفه</h2>
        <p class="mb-3 text-xs text-slate-500">سندهای مالی ثبت‌شده؛ جمع بدهکار و بستانکار هر سند در لحظه ثبت کنترل می‌شود.</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="account in journalSummary" :key="account.type" class="rounded-xl bg-slate-50 p-3">
            <p class="text-xs text-slate-500">{{ ({asset:'دارایی',liability:'بدهی',income:'درآمد',expense:'هزینه',equity:'حقوق مالکانه'})[account.type] || account.type }}</p>
            <p class="mt-1 text-sm font-bold">بدهکار: {{ money(account.debit_total) }}</p>
            <p class="text-sm font-bold">بستانکار: {{ money(account.credit_total) }}</p>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 font-extrabold">ثبت طرح درمان و اقساط</h2>
        <form @submit.prevent="planSubmit" class="grid gap-3 md:grid-cols-3">
          <select v-model="planForm.clinic_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب شعبه</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <select v-model="planForm.patient_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب بیمار</option><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }} — {{ p.phone || 'بدون تلفن' }}</option></select>
          <input v-model="planForm.title" required placeholder="عنوان طرح؛ مثلاً درمان کامل فک" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="planForm.specialty_module" class="rounded-xl border-slate-300 text-sm"><option value="general">عمومی</option><option value="dentistry">دندانپزشکی</option><option value="dermatology">پوست و زیبایی</option><option value="physiotherapy">فیزیوتراپی</option><option value="surgery">جراحی</option><option value="other">سایر</option></select>
          <input v-model.number="planForm.total_amount" required type="number" min="1" placeholder="مبلغ کل (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="planForm.discount_amount" type="number" min="0" placeholder="تخفیف (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="planForm.deposit_amount" type="number" min="0" :max="netAmount" placeholder="پیش‌پرداخت (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="planForm.deposit_method" class="rounded-xl border-slate-300 text-sm"><option value="cash">نقد</option><option value="card">کارتخوان</option><option value="transfer">انتقال بانکی</option><option value="online">آنلاین</option><option value="cheque">چک</option></select>
          <input v-model="planForm.deposit_reference" placeholder="شماره پیگیری پیش‌پرداخت (اختیاری)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="planForm.installment_count" type="number" min="1" max="48" required placeholder="تعداد اقساط" class="rounded-xl border-slate-300 text-sm" />
          <label class="text-xs text-slate-500">سررسید اولین قسط<input v-model="planForm.first_due_date" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <textarea v-model="planForm.clinical_notes" rows="2" placeholder="شرح درمان / مراحل (اختیاری)" class="rounded-xl border-slate-300 text-sm md:col-span-3" />
          <textarea v-model="planForm.financial_terms" rows="2" placeholder="شرایط مالی و توافقات" class="rounded-xl border-slate-300 text-sm md:col-span-3" />
          <label class="flex items-center gap-2 text-xs text-slate-600 md:col-span-3"><input v-model="planForm.sms_reminders_enabled" type="checkbox" class="rounded border-slate-300" /> فعال‌سازی یادآوری پیامکی اقساط</label>
          <label class="flex items-center gap-2 text-xs text-slate-600 md:col-span-3"><input v-model="planForm.sms_consent_confirmed" type="checkbox" class="rounded border-slate-300" /> رضایت بیمار برای پیامک ثبت و تأیید شده است</label>
          <label class="flex items-center gap-2 text-xs text-slate-600 md:col-span-3"><input v-model="planForm.follow_up_sms_enabled" type="checkbox" class="rounded border-slate-300" /> فعال‌سازی پیامک یادآوری مراجعه‌های بعدی درمان</label>
          <label class="flex items-center gap-2 text-xs text-slate-600 md:col-span-3"><input v-model="planForm.follow_up_sms_consent_confirmed" type="checkbox" class="rounded border-slate-300" /> رضایت جداگانه بیمار برای پیامک‌های درمانی ثبت و تأیید شده است</label>
          <p class="text-xs text-slate-500 md:col-span-3">مبلغ خالص: {{ money(netAmount) }} — مبلغ پس از پیش‌پرداخت به تعداد اقساط تقسیم می‌شود. پیش‌پرداخت جداگانه در دفتر مالی ثبت می‌شود.</p>
          <button :disabled="planForm.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50 md:col-span-3">ثبت طرح درمان و ساخت اقساط</button>
        </form>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 font-extrabold">اقساط باز و سررسیدها</h2>
        <div v-if="!installments.length" class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">قسط بازی وجود ندارد.</div>
        <div v-else class="space-y-3">
          <article v-for="row in installments" :key="row.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
            <div><p class="font-bold text-slate-800">{{ row.plan?.patient?.name }} — {{ row.plan?.title }}</p><p class="mt-1 text-xs text-slate-500">{{ row.plan?.clinic?.name }} · قسط {{ row.sequence || 'پیش‌پرداخت' }} · سررسید {{ row.due_date }}</p><p class="mt-1 text-xs text-slate-600">مبلغ: {{ money(row.amount) }} · پرداخت‌شده: {{ money(row.paid_amount) }} · مانده: {{ money(row.amount-row.paid_amount) }}</p></div>
            <button v-if="canManage" @click="pay(row)" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">ثبت دریافت</button>
          </article>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 font-extrabold">طرح‌های درمان و وثایق</h2>
        <div v-for="plan in plans.data" :key="plan.id" class="mb-3 rounded-xl border border-slate-200 p-3">
          <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="font-bold">{{ plan.patient?.name }} — {{ plan.title }}</p><p class="text-xs text-slate-500">{{ plan.clinic?.name }} · {{ plan.specialty_module }} · مبلغ خالص {{ money(plan.net_amount ?? (plan.total_amount-plan.discount_amount)) }}</p></div><button v-if="canManage" @click="addCollateral(plan)" class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-bold text-amber-800">ثبت وثیقه +</button></div>
          <div v-if="plan.collaterals?.length" class="mt-3 flex flex-wrap gap-2"><span v-for="item in plan.collaterals" :key="item.id" class="rounded-lg bg-amber-50 px-2 py-1 text-xs text-amber-900">{{ item.type }} · {{ item.identifier || 'بدون شناسه' }} · {{ item.status }}</span></div>
          <div v-if="canManage" class="mt-3 flex flex-wrap gap-2">
            <button v-if="plan.sms_reminders_enabled" @click="revokeConsent(plan,'installment')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs text-rose-700">لغو پیامک اقساط</button>
            <button v-if="plan.follow_up_sms_enabled" @click="revokeConsent(plan,'follow_up')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs text-rose-700">لغو پیامک پیگیری درمان</button>
          </div>
        </div>
        <div v-if="plans.links?.length" class="mt-4 flex flex-wrap gap-2"><button v-for="link in plans.links" :key="link.label" :disabled="!link.url" @click="router.get(link.url)" class="rounded-lg border px-3 py-2 text-xs disabled:opacity-40" v-html="link.label" /></div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 font-extrabold">ثبت هزینه شعبه</h2>
        <form @submit.prevent="expenseSubmit" class="grid gap-3 md:grid-cols-3">
          <select v-model="expenseForm.clinic_id" required class="rounded-xl border-slate-300 text-sm"><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <select v-model="expenseForm.category" class="rounded-xl border-slate-300 text-sm"><option>اجاره</option><option>حقوق و دستمزد</option><option>مواد مصرفی</option><option>تجهیزات</option><option>قبوض</option><option>تعمیرات</option><option>سایر</option></select>
          <input v-model="expenseForm.description" required placeholder="شرح هزینه" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="expenseForm.amount" required type="number" min="1" placeholder="مبلغ (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model="expenseForm.expense_date" required type="date" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="expenseForm.payment_method" class="rounded-xl border-slate-300 text-sm"><option value="cash">نقد</option><option value="card">کارت</option><option value="transfer">انتقال</option><option value="cheque">چک</option></select>
          <input v-model="expenseForm.reference" placeholder="شماره پیگیری (اختیاری)" class="rounded-xl border-slate-300 text-sm" />
          <button :disabled="expenseForm.processing" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">ثبت هزینه</button>
        </form>
        <div class="mt-4 space-y-2"><div v-for="expense in expenses" :key="expense.id" class="flex justify-between gap-2 border-t pt-2 text-sm"><span>{{ expense.description }} — {{ expense.clinic?.name }}</span><b>{{ money(expense.amount) }}</b></div></div>
      </section>
    </div>
  </PortalLayout>
</template>

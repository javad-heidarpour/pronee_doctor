<script setup>
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  partnerships: Array, incoming: Array, sessions: Array, providers: Array, clinics: Array,
  stats: Object, canManage: Boolean, dues: { type: Array, default: () => [] },
})

const form = useForm({
  clinic_id: '', provider_id: '', model: 'percentage', service_name: 'جلسه مشاوره',
  service_fee_rial: '', professional_share_percent: 70, fixed_fee_rial: '',
  periodic_fee_rial: '', periodic_cycle: 'monthly', payment_direction: 'clinic_to_professional',
  starts_at: new Date().toISOString().slice(0, 10), ends_at: '', terms: '',
})
const sessionForm = useForm({
  partnership_id: '', service_date: new Date().toISOString().slice(0, 10),
  gross_amount_rial: '', adjustment_amount_rial: '', appointment_id: '',
})
const localDate = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-')
const currentDate = new Date()
const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0)
const periodicForm = useForm({ partnership_id: '', period_start: localDate(new Date(currentDate.getFullYear(), currentDate.getMonth(), 1)), period_end: localDate(monthEnd), due_date: localDate(monthEnd) })
const models = [
  { value: 'percentage', label: 'درصدی از هر خدمت' },
  { value: 'fixed_per_session', label: 'مبلغ ثابت هر جلسه' },
  { value: 'hybrid', label: 'ترکیبی؛ درصد + مبلغ ثابت' },
  { value: 'room_rent', label: 'اجاره اتاق' },
  { value: 'shift_fee', label: 'حق شیفت' },
  { value: 'monthly_fixed', label: 'مبلغ ثابت ماهانه' },
]
const modelLabel = v => models.find(x => x.value === v)?.label || v
const statusLabel = v => ({ proposed: 'در انتظار پذیرش', active: 'فعال', rejected: 'رد شده', pending: 'در انتظار تسویه', paid: 'تأیید دوطرفه', partially_confirmed: 'در انتظار تأیید طرف مقابل', confirmed: 'تأیید دوطرفه' }[v] || v)
const money = v => new Intl.NumberFormat('fa-IR').format(Number(v || 0)) + ' ریال'
const activePartnerships = computed(() => props.partnerships.filter(x => x.status === 'active' && ['percentage','fixed_per_session','hybrid'].includes(x.model)))
const activePeriodicPartnerships = computed(() => props.partnerships.filter(x => x.status === 'active' && ['room_rent','shift_fee','monthly_fixed'].includes(x.model)))
const submit = () => form.post(route('portal.professional-partnerships.store'), { preserveScroll: true, onSuccess: () => form.reset('provider_id','service_fee_rial','fixed_fee_rial','periodic_fee_rial','ends_at','terms') })
const recordSession = () => sessionForm.post(route('portal.professional-partnerships.sessions.store'), { preserveScroll: true, onSuccess: () => sessionForm.reset('gross_amount_rial','adjustment_amount_rial','appointment_id') })
const respond = (item, decision) => router.patch(route('portal.professional-partnerships.respond', item.id), { decision }, { preserveScroll: true })
const confirmSession = item => { const reference = window.prompt('شناسه یا مرجع پرداخت (اختیاری):'); if (reference === null) return; router.patch(route('portal.professional-partnerships.sessions.confirm', item.id), { payment_reference: reference || null }, { preserveScroll: true }) }
const generateDue = () => periodicForm.post(route('portal.professional-partnerships.dues.store'), { preserveScroll: true })
const confirmDue = item => { const reference = window.prompt('شناسه یا مرجع پرداخت (اختیاری):'); if (reference === null) return; router.patch(route('portal.professional-partnerships.dues.confirm', item.id), { payment_reference: reference || null }, { preserveScroll: true }) }
const invoice = item => window.open(route('portal.professional-partnerships.dues.invoice', item.id), '_blank', 'noopener')
</script>

<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4">
      <header class="rounded-2xl bg-gradient-to-l from-indigo-950 via-slate-900 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold text-teal-200">PRONEEX · MULTIDISCIPLINARY PARTNERSHIP</p>
        <h1 class="mt-2 text-2xl font-black">همکاری تخصصی و درآمد مشترک</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-200">همکاری روان‌پزشک، روان‌شناس، مشاور و سایر متخصصان با کلینیک؛ قرارداد روشن، سهم شفاف و ثبت ساده خدمات ارائه‌شده.</p><a :href="route('portal.medical-network.index')" class="mt-3 inline-flex rounded-lg bg-white/15 px-3 py-2 text-xs font-bold text-white">ثبت / مدیریت پروفایل متخصصان</a>
      </header>

      <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">همکاری فعال</p><b class="mt-2 block text-2xl">{{ stats.active }}</b></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">پیشنهادهای من</p><b class="mt-2 block text-2xl">{{ stats.pending }}</b></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">سهم متخصص؛ در انتظار تسویه</p><b class="mt-2 block text-lg">{{ money(stats.unpaid) }}</b></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">سهم متخصص؛ تسویه ثبت‌شده</p><b class="mt-2 block text-lg">{{ money(stats.paid) }}</b></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">صورتحساب‌های دوره‌ای باز</p><b class="mt-2 block text-lg">{{ money(stats.periodic_due || 0) }}</b></div>
      </section>

      <section v-if="canManage" class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">۱. تعریف همکاری جدید</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">فقط همکاران تأییدشده شبکه قابل انتخاب‌اند. قرارداد پس از پذیرش طرف مقابل فعال می‌شود.</p>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-semibold">شعبه<select v-model="form.clinic_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب شعبه</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select></label>
          <label class="text-xs font-semibold">متخصص / مرکز همکار<select v-model="form.provider_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب همکار</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }} · {{ p.city || 'بدون شهر' }}</option></select></label>
          <label class="text-xs font-semibold">مدل همکاری<select v-model="form.model" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option v-for="m in models" :key="m.value" :value="m.value">{{ m.label }}</option></select></label>
          <label class="text-xs font-semibold">عنوان خدمت<input v-model="form.service_name" required maxlength="160" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="مثلاً روان‌درمانی فردی"></label>
          <label v-if="['percentage','hybrid'].includes(form.model)" class="text-xs font-semibold">سهم متخصص از مبلغ خالص (%)<input v-model.number="form.professional_share_percent" type="number" min="1" max="99" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label v-if="['fixed_per_session','hybrid'].includes(form.model)" class="text-xs font-semibold">مبلغ ثابت هر جلسه (ریال)<input v-model="form.fixed_fee_rial" type="number" min="1" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label v-if="['room_rent','shift_fee','monthly_fixed'].includes(form.model)" class="text-xs font-semibold">مبلغ دوره‌ای (ریال)<input v-model="form.periodic_fee_rial" type="number" min="1" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label v-if="['room_rent','shift_fee','monthly_fixed'].includes(form.model)" class="text-xs font-semibold">دوره پرداخت<select v-model="form.periodic_cycle" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="weekly">هفتگی</option><option value="monthly">ماهانه</option></select></label>
          <label v-if="['percentage','hybrid'].includes(form.model)" class="text-xs font-semibold">تعرفه مرجع هر خدمت (ریال)<input v-model="form.service_fee_rial" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">جهت پرداخت<select v-model="form.payment_direction" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="clinic_to_professional">کلینیک به متخصص</option><option value="professional_to_clinic">متخصص به کلینیک</option></select></label>
          <label class="text-xs font-semibold">شروع همکاری<input v-model="form.starts_at" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">پایان همکاری<input v-model="form.ends_at" type="date" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold md:col-span-2 lg:col-span-3">شرایط توافق<textarea v-model="form.terms" rows="3" maxlength="3000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-2 lg:col-span-3">ارسال پیشنهاد همکاری</button>
        </form>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border bg-white p-4">
          <h2 class="mb-3 font-black">همکاری‌های شعبه من</h2>
          <article v-for="p in partnerships" :key="p.id" class="mb-2 rounded-xl bg-slate-50 p-3">
            <div class="flex flex-wrap justify-between gap-2"><b class="text-sm">{{ p.provider?.name }}</b><span class="text-xs text-teal-700">{{ statusLabel(p.status) }}</span></div>
            <p class="mt-1 text-xs text-slate-600">{{ p.clinic?.name }} · {{ p.service_name }} · {{ modelLabel(p.model) }}</p>
            <p class="mt-1 text-xs text-slate-600" v-if="['percentage','hybrid'].includes(p.model)">سهم متخصص {{ (p.professional_share_bps / 100).toLocaleString('fa-IR') }}٪ · سهم کلینیک {{ (p.clinic_share_bps / 100).toLocaleString('fa-IR') }}٪ · پرداخت: {{ p.payment_direction === 'clinic_to_professional' ? 'کلینیک به متخصص' : 'متخصص به کلینیک' }}</p>
            <p class="mt-1 text-xs text-slate-600" v-if="['room_rent','shift_fee','monthly_fixed'].includes(p.model)">مبلغ دوره‌ای: {{ money(p.periodic_fee_rial) }} · {{ p.periodic_cycle === 'weekly' ? 'هفتگی' : 'ماهانه' }} · پرداخت: {{ p.payment_direction === 'clinic_to_professional' ? 'کلینیک به متخصص' : 'متخصص به کلینیک' }}</p>
            <p v-if="p.terms" class="mt-2 text-xs leading-6">{{ p.terms }}</p>
          </article>
          <p v-if="!partnerships.length" class="text-xs text-slate-500">هنوز پیشنهادی ثبت نشده است.</p>
        </div>
        <div class="rounded-2xl border bg-white p-4">
          <h2 class="mb-3 font-black">پیشنهادهای دریافتی</h2>
          <article v-for="p in incoming" :key="p.id" class="mb-2 rounded-xl bg-slate-50 p-3">
            <b class="text-sm">{{ p.doctor?.name }} {{ p.doctor?.family }} · {{ p.clinic?.name }}</b>
            <p class="mt-1 text-xs">{{ p.service_name }} · {{ modelLabel(p.model) }}</p>
            <p v-if="p.status === 'proposed'" class="mt-2 flex gap-2"><button @click="respond(p,'accept')" class="rounded-lg bg-teal-700 px-3 py-2 text-xs text-white">پذیرش</button><button @click="respond(p,'reject')" class="rounded-lg border px-3 py-2 text-xs">رد</button></p>
            <span class="text-xs text-slate-500">{{ statusLabel(p.status) }}</span>
          </article>
          <p v-if="!incoming.length" class="text-xs text-slate-500">پیشنهاد دریافتی وجود ندارد.</p>
        </div>
      </section>

      <section v-if="canManage" class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">۲. ثبت خدمت انجام‌شده و محاسبه سهم‌ها</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">ثبت فقط برای قرارداد فعال و خدمت واقعاً ارائه‌شده انجام شود. اتصال به نوبت مراجعه‌شده اختیاری است؛ هیچ نام یا یادداشت درمانی بیمار در دفتر مالی ذخیره نمی‌شود.</p>
        <form @submit.prevent="recordSession" class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-semibold">قرارداد فعال<select v-model="sessionForm.partnership_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب قرارداد</option><option v-for="p in activePartnerships" :key="p.id" :value="p.id">{{ p.provider?.name }} · {{ p.service_name }}</option></select></label>
          <label class="text-xs font-semibold">تاریخ خدمت<input v-model="sessionForm.service_date" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">مبلغ ناخالص خدمت (ریال)<input v-model="sessionForm.gross_amount_rial" type="number" min="1" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">تعدیل / تخفیف (ریال)<input v-model="sessionForm.adjustment_amount_rial" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">شناسه نوبت مراجعه‌شده (اختیاری)<input v-model="sessionForm.appointment_id" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <button :disabled="sessionForm.processing" class="rounded-xl bg-indigo-700 px-4 py-3 text-sm font-bold text-white md:col-span-2 lg:col-span-3">ثبت خدمت و محاسبه سهم طرفین</button>
        </form>
      </section>

      <section v-if="canManage && activePeriodicPartnerships.length" class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">۳. صورتحساب دوره‌ای اجاره اتاق، شیفت و حق‌الزحمه</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">برای هر بازه قرارداد یک صورتحساب بسازید. ایجاد صورتحساب برای یک قرارداد و بازه تکراری، رکورد جدید ایجاد نمی‌کند.</p>
        <form @submit.prevent="generateDue" class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
          <label class="text-xs font-semibold">قرارداد دوره‌ای<select v-model="periodicForm.partnership_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب قرارداد</option><option v-for="p in activePeriodicPartnerships" :key="p.id" :value="p.id">{{ p.provider?.name }} · {{ p.service_name }}</option></select></label>
          <label class="text-xs font-semibold">شروع دوره<input v-model="periodicForm.period_start" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">پایان دوره<input v-model="periodicForm.period_end" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <label class="text-xs font-semibold">سررسید پرداخت<input v-model="periodicForm.due_date" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
          <button :disabled="periodicForm.processing" class="rounded-xl bg-indigo-700 px-4 py-3 text-sm font-bold text-white md:col-span-2 lg:col-span-4">ایجاد صورتحساب دوره‌ای</button>
        </form>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">۴. صورتحساب‌ها و تأیید دوطرفه</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">طرف پرداخت‌کننده و دریافت‌کننده هر کدام جداگانه تأیید می‌کنند. صورتحساب فقط پس از ثبت هر دو تأیید نهایی می‌شود.</p>
        <div class="mt-3 space-y-2">
          <article v-for="d in dues" :key="d.id" class="rounded-xl bg-slate-50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2"><b class="text-sm">{{ d.partnership?.provider?.name }} · {{ d.partnership?.service_name }}</b><span class="text-xs text-teal-700">{{ statusLabel(d.status) }}</span></div>
            <p class="mt-1 text-xs text-slate-500">{{ d.period_start }} تا {{ d.period_end }} · سررسید {{ d.due_date }} · {{ d.payment_direction === 'clinic_to_professional' ? 'کلینیک به متخصص' : 'متخصص به کلینیک' }}</p>
            <b class="mt-2 block text-sm">{{ money(d.amount_rial) }}</b>
            <p class="mt-1 text-xs text-slate-500">تأیید پرداخت‌کننده: {{ d.payer_confirmed_at ? 'ثبت شده' : 'در انتظار' }} · تأیید دریافت‌کننده: {{ d.receiver_confirmed_at ? 'ثبت شده' : 'در انتظار' }}</p>
            <div class="mt-2 flex flex-wrap gap-2"><button @click="invoice(d)" class="rounded-lg border px-3 py-2 text-xs">مشاهده / چاپ صورتحساب</button><button v-if="d.status !== 'confirmed'" @click="confirmDue(d)" class="rounded-lg bg-teal-700 px-3 py-2 text-xs text-white">ثبت تأیید پرداخت / دریافت</button></div>
          </article>
          <p v-if="!dues.length" class="text-xs text-slate-500">هنوز صورتحساب دوره‌ای ایجاد نشده است.</p>
        </div>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="font-black">۵. دفتر خدمات و تسویه</h2>
        <div class="mt-3 space-y-2">
          <article v-for="s in sessions" :key="s.id" class="rounded-xl bg-slate-50 p-3">
            <div class="flex flex-wrap items-start justify-between gap-2"><div><b class="text-sm">{{ s.provider?.name }} · {{ s.service_name }}</b><p class="mt-1 text-xs text-slate-500">{{ s.clinic?.name }} · {{ s.service_date }} · {{ s.appointment_id ? 'نوبت #' + s.appointment_id : 'ثبت خدمت مستقل' }}</p></div><span class="text-xs">{{ statusLabel(s.status) }}</span></div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4"><div>ناخالص: <b>{{ money(s.gross_amount_rial) }}</b></div><div>تعدیل: <b>{{ money(s.adjustment_amount_rial) }}</b></div><div>سهم متخصص: <b>{{ money(s.professional_amount_rial) }}</b></div><div>سهم کلینیک: <b>{{ money(s.clinic_amount_rial) }}</b></div></div>
            <p class="mt-2 text-xs text-slate-500">مبلغ قابل پرداخت طبق جهت قرارداد: <b>{{ money(s.settlement_amount_rial) }}</b></p><p class="mt-1 text-xs text-slate-500">تأیید پرداخت‌کننده: {{ s.payer_confirmed_at ? 'ثبت شده' : 'در انتظار' }} · تأیید دریافت‌کننده: {{ s.receiver_confirmed_at ? 'ثبت شده' : 'در انتظار' }}</p><button v-if="s.status !== 'paid'" @click="confirmSession(s)" class="mt-3 rounded-lg border border-teal-700 px-3 py-2 text-xs text-teal-800">تأیید پرداخت / دریافت</button>
          </article>
          <p v-if="!sessions.length" class="text-xs text-slate-500">خدمتی ثبت نشده است.</p>
        </div>
        <p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs leading-6 text-amber-900">این ماژول دفتر توافق و محاسبه داخلی است؛ انتقال بانکی، صدور فاکتور رسمی مالیاتی و محاسبه مالیات همچنان به درگاه و سامانه حسابداری معتبر متصل نیستند. صورتحساب دوره‌ای و تأیید دوطرفه ثبت داخلی است و جایگزین تأیید بانکی نیست. قراردادها باید با مقررات حرفه‌ای و تعرفه‌های مجاز تطبیق داده شوند؛ پرداخت بابت معرفی بیمار یا کمیسیون ارجاع در این ماژول وجود ندارد.</p>
      </section>
    </main>
  </PortalLayout>
</template>

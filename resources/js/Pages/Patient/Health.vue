<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  hasAccess: Boolean, pendingRequest: Boolean, accesses: Array, accessType: String,
  patient: Object, encounters: Array, followUps: Array, caregivers: Array, intelligence: Object, observations: Array, medicationPlans: Array, availablePrescriptions: Array, consents: { type: Array, default: () => [] }, carePathways: { type: Array, default: () => [] },
})
const linkForm = useForm({ national_code: '', phone: '' })
const clinicToday = () => new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date())
const medicationForm = useForm({ prescription_id: '', start_date: clinicToday(), end_date: '', reminder_times: ['08:00'] })
const saveMedicationPlan = () => medicationForm.transform(data => ({ ...data, end_date: data.end_date || null, reminder_times: data.reminder_times.filter(Boolean) })).post(route('patient.health.medications.store'), { onSuccess: () => medicationForm.reset('prescription_id', 'end_date') })
const removeMedicationTime = index => medicationForm.reminder_times.splice(index, 1)
const doseStatus = (plan, time) => plan.doses?.find(dose => new Intl.DateTimeFormat('en-GB', { timeZone: plan.timezone, hour: '2-digit', minute: '2-digit', hour12: false }).format(new Date(dose.scheduled_for)) === time)?.status
const recordMedicationDose = (plan, time, status) => router.post(route('patient.health.medications.doses.store', plan.id), { scheduled_for: clinicToday() + ' ' + time + ':00', status })
const pauseMedicationPlan = plan => { if (window.confirm('یادآوری این دارو متوقف شود؟ نسخه پزشک تغییری نمی‌کند.')) router.post(route('patient.health.medications.pause', plan.id)) }

const observationOptions = [
  { value: 'weight', label: 'وزن', unit: 'kg', min: 20, max: 350 },
  { value: 'systolic_bp', label: 'فشار سیستولیک', unit: 'mmHg', min: 50, max: 280 },
  { value: 'diastolic_bp', label: 'فشار دیاستولیک', unit: 'mmHg', min: 30, max: 180 },
  { value: 'blood_glucose', label: 'قند خون', unit: 'mg/dL', min: 20, max: 700 },
  { value: 'pulse', label: 'ضربان قلب', unit: 'bpm', min: 25, max: 240 },
  { value: 'temperature', label: 'دمای بدن', unit: '°C', min: 30, max: 45 },
  { value: 'spo2', label: 'اکسیژن خون', unit: '%', min: 50, max: 100 },
]
const localDateTime = () => {
  const date = new Date()
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}
const observationForm = useForm({ measurement_type: 'weight', value: '', observed_at: localDateTime(), note: '' })
const selectedObservation = computed(() => observationOptions.find(item => item.value === observationForm.measurement_type))
const saveObservation = () => observationForm.post(route('patient.health.observations.store'), { onSuccess: () => observationForm.reset('value', 'note') })
const deleteObservation = item => {
  if (window.confirm('این ثبت خانگی حذف شود؟')) router.delete(route('patient.health.observations.destroy', item.id))
}
const observationLabel = value => observationOptions.find(item => item.value === value)?.label || value
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
        <section v-if="accessType === 'owner' && consents?.length" class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h2 class="font-black text-slate-900">رضایت‌نامه‌های درمانی</h2>
              <p class="mt-1 text-xs text-slate-500">متن و وضعیت رضایت‌نامه‌هایی که مرکز درمانی برای شما ثبت کرده است.</p>
            </div>
            <span class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ consents.length.toLocaleString('fa-IR') }} مورد</span>
          </div>
          <div class="mt-3 divide-y divide-slate-100">
            <article v-for="consent in consents" :key="consent.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
              <div class="min-w-0">
                <p class="font-bold text-slate-800">{{ consent.title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ consent.doctor?.name }} {{ consent.doctor?.family }} · {{ consent.clinic?.name }} · {{ dateLabel(consent.created_at) }}</p>
              </div>
              <Link :href="route('patient.health.consents.show', consent.id)" class="rounded-lg px-3 py-2 text-xs font-bold" :class="consent.status === 'signed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'">{{ consent.status === 'signed' ? 'مشاهده امضاشده' : 'مطالعه و امضا' }}</Link>
            </article>
          </div>
        </section>
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">مراجعات ثبت‌شده</p><p class="mt-2 text-2xl font-black">{{ patient.appointments?.length || 0 }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">سوابق پزشکی فعال</p><p class="mt-2 text-2xl font-black">{{ patient.clinical_records?.length || 0 }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">برنامه‌های پیگیری</p><p class="mt-2 text-2xl font-black">{{ followUps.length }}</p></div>
          <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">اندازه‌گیری‌های ثبت‌شده</p><p class="mt-2 text-2xl font-black">{{ patient.vital_signs?.length || 0 }}</p></div>
        </section>
        <section v-if="accessType === 'owner' && carePathways.length" class="rounded-2xl border bg-white p-5 shadow-sm">
          <div class="mb-4"><p class="text-xs font-bold text-teal-700">PERSONALIZED CARE PATHWAY</p><h3 class="mt-1 text-lg font-black">مسیر مراقبت شخصی من</h3><p class="mt-1 text-xs leading-6 text-slate-500">این برنامه توسط پزشک شما تنظیم و فعال شده است. پس از انجام هر گام، می‌توانید آن را برای بررسی گزارش کنید.</p></div>
          <div v-for="pathway in carePathways" :key="pathway.id" class="mb-4 rounded-xl border p-4 last:mb-0">
            <h4 class="font-black">{{ pathway.title }}</h4><p v-if="pathway.goal" class="mt-1 text-sm text-slate-600">هدف: {{ pathway.goal }}</p>
            <p v-if="pathway.description" class="mt-1 text-xs leading-6 text-slate-500">{{ pathway.description }}</p>
            <div class="mt-3 space-y-2">
              <div v-for="step in pathway.steps" :key="step.id" class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3">
                <div class="min-w-0 flex-1"><p class="text-sm font-bold">{{ step.title }}</p><p v-if="step.description" class="mt-1 text-xs text-slate-500">{{ step.description }}</p><p class="mt-1 text-xs text-teal-700">{{ pathwayStepLabel(step.status) }}</p></div>
                <button v-if="step.status === 'pending'" @click="reportPathwayStep(pathway.id, step.id)" class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">گزارش انجام</button>
              </div>
            </div>
          </div>
        </section>
        <section class="grid gap-4 lg:grid-cols-2">
          <div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">داروهای ثبت‌شده</h3><div v-for="item in activeMeds" :key="item.id" class="border-b py-3 last:border-0"><p class="text-sm font-bold">{{ item.title }}</p><p class="mt-1 text-xs text-slate-500">{{ item.dosage }} · {{ item.frequency }}</p><p v-if="item.description" class="mt-1 text-xs">{{ item.description }}</p></div><p v-if="!activeMeds.length" class="text-xs text-slate-400">دارویی ثبت نشده است.</p></div>
          <div class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">حساسیت‌ها</h3><div v-for="item in allergies" :key="item.id" class="mb-2 rounded-xl bg-rose-50 p-3"><p class="text-sm font-bold text-rose-800">{{ item.title }}</p><p class="mt-1 text-xs text-rose-700">{{ item.description }}</p></div><p v-if="!allergies.length" class="text-xs text-slate-400">حساسیتی در پرونده ثبت نشده است.</p></div>
        </section>
        <section v-if="accessType === 'owner'" class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">روند علائم و اندازه‌گیری‌های من</h3><div class="grid gap-2 sm:grid-cols-2"><article v-for="vital in latestVitals" :key="vital.id" class="rounded-xl bg-slate-50 p-3"><div class="flex justify-between gap-2"><b class="text-sm">{{ vital.measurement_type }}</b><span class="text-xs text-slate-500">{{ dateLabel(vital.measured_at) }}</span></div><p class="mt-2 text-lg font-black text-teal-800">{{ vital.value }} <span class="text-xs font-medium">{{ vital.unit }}</span></p><p v-if="vital.note" class="mt-1 text-xs text-slate-500">{{ vital.note }}</p></article></div><p v-if="!latestVitals.length" class="text-xs text-slate-400">اندازه‌گیری‌ای ثبت نشده است.</p></section>

        <section v-if="accessType === 'owner'" class="space-y-4 rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
          <div><p class="text-xs font-bold tracking-wider text-sky-700">PRONEEX HOME HEALTH TRACKER</p><h3 class="mt-1 text-lg font-black">دفتر پایش سلامت در خانه</h3><p class="mt-1 text-xs leading-6 text-slate-500">اندازه‌گیری‌های دستگاه یا ابزار خانگی خود را ثبت کنید تا در روند سلامت شما جدا از داده‌های ثبت‌شده توسط مرکز درمانی تحلیل شوند.</p></div>
          <form @submit.prevent="saveObservation" class="grid gap-3 sm:grid-cols-2">
            <label class="text-xs font-bold text-slate-600">نوع اندازه‌گیری
              <select v-model="observationForm.measurement_type" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
                <option v-for="item in observationOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
              </select>
            </label>
            <label class="text-xs font-bold text-slate-600">مقدار ({{ selectedObservation?.unit }})
              <input v-model="observationForm.value" type="number" step="0.01" :min="selectedObservation?.min" :max="selectedObservation?.max" required class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="مقدار اندازه‌گیری">
              <span class="mt-1 block font-normal text-slate-400">محدوده ثبت: {{ selectedObservation?.min }} تا {{ selectedObservation?.max }}</span>
            </label>
            <label class="text-xs font-bold text-slate-600">زمان اندازه‌گیری
              <input v-model="observationForm.observed_at" type="datetime-local" required :max="localDateTime()" class="mt-1 w-full rounded-xl border-slate-300 text-sm">
            </label>
            <label class="text-xs font-bold text-slate-600">توضیح (اختیاری)
              <input v-model="observationForm.note" maxlength="500" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="مثلاً قبل از صبحانه">
            </label>
            <div class="sm:col-span-2"><p v-if="observationForm.errors.value || observationForm.errors.measurement_type || observationForm.errors.observed_at" class="mb-2 text-xs text-rose-600">{{ observationForm.errors.value || observationForm.errors.measurement_type || observationForm.errors.observed_at }}</p><button :disabled="observationForm.processing" class="rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">ثبت اندازه‌گیری خانگی</button></div>
          </form>
          <div class="space-y-2">
            <h4 class="text-sm font-black">ثبت‌های اخیر من</h4>
            <article v-for="item in observations" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-sky-50 p-3">
              <div><p class="text-sm font-bold">{{ observationLabel(item.measurement_type) }}: {{ item.value }} {{ item.unit }}</p><p class="mt-1 text-[11px] text-slate-500">{{ dateLabel(item.observed_at) }} · ثبت‌شده توسط بیمار</p><p v-if="item.note" class="mt-1 text-xs text-slate-600">{{ item.note }}</p></div>
              <button type="button" @click="deleteObservation(item)" class="text-xs font-bold text-rose-600">حذف</button>
            </article>
            <p v-if="!observations?.length" class="text-xs text-slate-400">هنوز اندازه‌گیری خانگی ثبت نکرده‌اید.</p>
          </div>
          <p class="rounded-lg bg-amber-50 p-3 text-[11px] leading-6 text-amber-900">مقادیر واردشده توسط بیمار، خوداظهاری هستند و جایگزین اندازه‌گیری بالینی نیستند. در صورت مشاهده مقدار نگران‌کننده یا بروز علائم، برای ارزیابی با پزشک یا خدمات اورژانس تماس بگیرید.</p>
        </section>
        <section v-if="accessType === 'owner'" class="space-y-4 rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">
          <div><p class="text-xs font-bold tracking-wider text-violet-700">PRONEEX SMART MEDICATION COMPANION</p><h3 class="mt-1 text-lg font-black">همراه هوشمند مدیریت دارو</h3><p class="mt-1 text-xs leading-6 text-slate-500">برنامه شخصی یادآوری را بر اساس نسخه نهایی‌شده پزشک تنظیم کنید و مصرف هر نوبت را ثبت کنید.</p></div>
          <form @submit.prevent="saveMedicationPlan" class="grid gap-3 sm:grid-cols-2">
            <label class="text-xs font-bold text-slate-600 sm:col-span-2">انتخاب دارو از نسخه نهایی‌شده
              <select v-model="medicationForm.prescription_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب نسخه</option><option v-for="rx in availablePrescriptions" :key="rx.id" :value="rx.id">{{ rx.medication_name }} {{ rx.strength || '' }} · {{ rx.dosage }} · {{ rx.frequency }}</option></select>
            </label>
            <label class="text-xs font-bold text-slate-600">تاریخ شروع <input v-model="medicationForm.start_date" type="date" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
            <label class="text-xs font-bold text-slate-600">تاریخ پایان (اختیاری) <input v-model="medicationForm.end_date" type="date" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></label>
            <div class="sm:col-span-2"><p class="text-xs font-bold text-slate-600">ساعت‌های یادآوری</p><div class="mt-2 flex flex-wrap gap-2"><label v-for="(time, index) in medicationForm.reminder_times" :key="index" class="flex items-center gap-2 rounded-xl bg-violet-50 p-2 text-xs"><input v-model="medicationForm.reminder_times[index]" type="time" class="rounded-lg border-violet-200 text-sm"><button type="button" @click="removeMedicationTime(index)" class="font-bold text-rose-600">×</button></label><button v-if="medicationForm.reminder_times.length < 6" type="button" @click="medicationForm.reminder_times.push('')" class="rounded-xl border border-dashed px-3 py-2 text-xs font-bold text-violet-700">افزودن ساعت +</button></div></div>
            <div class="sm:col-span-2"><p v-if="medicationForm.errors.prescription_id || medicationForm.errors.reminder_times" class="mb-2 text-xs text-rose-600">{{ medicationForm.errors.prescription_id || medicationForm.errors.reminder_times }}</p><button :disabled="medicationForm.processing || !medicationForm.prescription_id" class="rounded-xl bg-violet-700 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">ذخیره برنامه یادآوری</button></div>
          </form>
          <div v-for="plan in medicationPlans" :key="plan.id" class="rounded-xl border border-violet-100 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="text-sm font-black">{{ plan.prescription?.medication_name }} {{ plan.prescription?.strength || '' }}</p><p class="mt-1 text-xs text-slate-500">{{ plan.prescription?.dosage }} · {{ plan.prescription?.route }} · {{ plan.prescription?.frequency }}</p><p class="mt-1 text-[11px] text-slate-400">{{ plan.prescription?.instructions }}</p></div><button type="button" @click="pauseMedicationPlan(plan)" class="text-xs font-bold text-rose-600">توقف یادآوری</button></div>
            <div class="mt-3 grid gap-2 sm:grid-cols-2"><div v-for="time in plan.reminder_times" :key="time" class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-2"><span class="text-sm font-bold">{{ time }}</span><span v-if="doseStatus(plan, time)" class="text-xs" :class="doseStatus(plan,time) === 'taken' ? 'text-emerald-700' : 'text-rose-700'">{{ doseStatus(plan,time) === 'taken' ? 'مصرف شد' : 'مصرف نشد' }}</span><div v-else class="flex gap-2"><button type="button" @click="recordMedicationDose(plan,time,'taken')" class="rounded-lg bg-emerald-600 px-2 py-1 text-[11px] font-bold text-white">مصرف شد</button><button type="button" @click="recordMedicationDose(plan,time,'skipped')" class="rounded-lg bg-slate-200 px-2 py-1 text-[11px] font-bold text-slate-700">مصرف نشد</button></div></div></div>
          </div>
          <p v-if="!medicationPlans?.length" class="text-xs text-slate-400">هنوز برنامه یادآوری فعالی ندارید. برای شروع، یکی از نسخه‌های نهایی‌شده را انتخاب کنید.</p>
          <div v-if="!availablePrescriptions?.length" class="rounded-xl bg-amber-50 p-3 text-xs leading-6 text-amber-900">نسخه نهایی‌شده‌ای برای انتخاب وجود ندارد. برنامه یادآوری باید بر پایه نسخه ثبت‌شده پزشک ایجاد شود.</div>
          <p class="rounded-lg bg-amber-50 p-3 text-[11px] leading-6 text-amber-900">این ابزار فقط یادآوری و ثبت خوداظهاری مصرف است؛ تداخل دارویی را تشخیص نمی‌دهد و نسخه را تغییر نمی‌دهد. در صورت فراموشی، عارضه یا ابهام درباره دوز، طبق راهنمای پزشک یا داروساز اقدام کنید و دوز را خودسرانه دو برابر، قطع یا جایگزین نکنید. یادآوری فعلاً داخل داشبورد نمایش داده می‌شود و ارسال اعلان/پیامک خودکار در این نسخه فعال نیست.</p>
        </section>

        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">برنامه پیگیری من</h3><div v-for="task in followUps" :key="task.id" class="flex flex-wrap justify-between gap-2 border-b py-3 last:border-0"><div><p class="text-sm font-bold">{{ task.title }}</p><p class="mt-1 text-xs text-slate-500">{{ task.description }}</p></div><span class="text-xs text-teal-700">{{ dateLabel(task.due_at) }}</span></div><p v-if="!followUps.length" class="text-xs text-slate-400">پیگیری فعالی ثبت نشده است.</p></section>
        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">خط زمانی مراجعات</h3><div v-for="visit in patient.appointments" :key="visit.id" class="flex flex-wrap justify-between gap-2 border-b py-3 last:border-0"><div><p class="text-sm font-bold">{{ visit.clinic?.name || 'مرکز درمانی' }}</p><p class="mt-1 text-xs text-slate-500">{{ visit.appointment_time }} · {{ visit.visit_number || 'ویزیت' }}</p></div><div class="text-left"><p class="text-xs">{{ dateLabel(visit.appointment_date) }}</p><span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-1 text-[11px]">{{ statusLabel(visit.status) }}</span></div></div><p v-if="!patient.appointments?.length" class="text-xs text-slate-400">مراجعه‌ای ثبت نشده است.</p></section>
        <section class="rounded-2xl border bg-white p-5"><h3 class="mb-3 font-black">سوابق ویزیت نهایی‌شده</h3><article v-for="encounter in encounters" :key="encounter.id" class="mb-3 rounded-xl bg-slate-50 p-4"><div class="flex flex-wrap justify-between gap-2"><b class="text-sm">{{ encounter.diagnosis || encounter.subjective || 'یادداشت ویزیت' }}</b><span class="text-xs text-slate-500">{{ dateLabel(encounter.signed_at) }}</span></div><p class="mt-2 text-xs leading-6">{{ encounter.plan }}</p><p class="mt-2 text-xs text-slate-500">{{ encounter.doctor?.name }} {{ encounter.doctor?.family }}</p></article><p v-if="!encounters.length" class="text-xs text-slate-400">یادداشت نهایی‌شده‌ای در دسترس نیست.</p></section>
        <section v-if="accessType === 'owner'" class="rounded-2xl border bg-white p-5">
          <h3 class="font-black">دسترسی خانواده و مراقب</h3><p class="mt-1 text-xs leading-6 text-slate-500">با ایمیل حساب کاربری پرونیکس، دسترسی محدود و فقط‌خواندنی به خلاصه سلامت، مراجعات و پیگیری‌ها بدهید. دسترسی پس از ۹۰ روز منقضی می‌شود.</p>
          <form @submit.prevent="grantCaregiver" class="mt-3 flex flex-col gap-2 sm:flex-row"><input v-model="caregiverForm.email" type="email" placeholder="ایمیل حساب مراقب" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm"><button :disabled="caregiverForm.processing" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white">اعطای دسترسی</button></form>
          <div v-for="access in caregivers" :key="access.id" class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-slate-50 p-3"><div><p class="text-sm font-bold">{{ access.user?.name }} {{ access.user?.family }}</p><p class="text-xs text-slate-500">{{ access.user?.email }}</p></div><button @click="revoke(access)" class="text-xs font-bold text-rose-600">لغو دسترسی</button></div>
        </section>
        <section v-if="accessType === 'owner'" class="space-y-4 rounded-2xl border border-teal-100 bg-gradient-to-br from-white to-teal-50 p-5 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div><p class="text-xs font-bold tracking-wider text-teal-700">PRONEEX HEALTH INTELLIGENCE</p><h3 class="mt-1 text-lg font-black">مغز هوشمند سلامت شخصی من</h3><p class="mt-1 text-xs leading-6 text-slate-500">تحلیل روند داده‌های ثبت‌شده و یادآوری پیگیری‌ها؛ بدون تشخیص خودکار یا تغییر درمان.</p></div>
            <span class="rounded-full bg-teal-100 px-3 py-1.5 text-xs font-bold text-teal-800">تحلیل مبتنی بر پرونده</span>
          </div>
          <div class="grid gap-2 sm:grid-cols-3">
            <div v-for="item in (intelligence?.summary || [])" :key="item.label" class="rounded-xl border border-teal-100 bg-white p-3"><p class="text-xs text-slate-500">{{ item.label }}</p><p class="mt-1 text-xl font-black text-teal-900">{{ item.value }}</p><p class="mt-1 text-[11px] text-slate-500">{{ item.caption }}</p></div>
          </div>
          <div v-if="intelligence?.insights?.length" class="space-y-2">
            <h4 class="text-sm font-black">اقدام‌ها و نکته‌های قابل توجه</h4>
            <article v-for="(insight, index) in intelligence.insights" :key="index" class="rounded-xl border p-3" :class="insight.level === 'warning' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'">
              <p class="text-sm font-bold" :class="insight.level === 'warning' ? 'text-amber-900' : 'text-slate-800'">{{ insight.title }}</p>
              <p class="mt-1 text-xs leading-6 text-slate-600">{{ insight.description }}</p>
              <p v-if="insight.action" class="mt-2 text-xs font-bold text-teal-800">{{ insight.action }}</p>
            </article>
          </div>
          <p v-else class="rounded-xl bg-white p-3 text-xs text-slate-500">فعلاً داده کافی برای تولید نکته شخصی وجود ندارد. با ثبت منظم اندازه‌گیری‌ها و پیگیری‌ها، تحلیل‌ها کامل‌تر می‌شوند.</p>
          <div v-if="intelligence?.trends?.length" class="space-y-2">
            <h4 class="text-sm font-black">مقایسه آخرین اندازه‌گیری‌ها</h4>
            <div v-for="trend in intelligence.trends" :key="trend.key" class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white p-3">
              <div><p class="text-sm font-bold">{{ trend.label }}</p><p class="mt-1 text-[11px] text-slate-500">{{ trend.previous_date }} ← {{ trend.latest_date }}</p></div>
              <div class="text-left"><p class="text-sm font-black text-slate-800">{{ trend.latest }} {{ trend.unit }}</p><p class="mt-1 text-xs" :class="trend.direction === 'up' ? 'text-amber-700' : trend.direction === 'down' ? 'text-sky-700' : 'text-slate-500'">{{ trend.change_label }}</p></div>
            </div>
            <p class="text-[11px] leading-5 text-slate-400">این مقایسه صرفاً تغییر عددی را نشان می‌دهد؛ بالا یا پایین بودن مقدار به نوع اندازه‌گیری، شرایط فرد و نظر پزشک وابسته است.</p>
          </div>
        </section>
        <section class="rounded-xl bg-amber-50 p-4 text-xs leading-6 text-amber-900">اطلاعات این صفحه برای مشاهده سوابق است و جایگزین تشخیص یا توصیه پزشکی نیست. در صورت وضعیت اورژانسی با خدمات اورژانس تماس بگیرید. مقادیر آزمایش و علائم حیاتی باید توسط پزشک تفسیر شوند.</section>
      </template>
    </main>
  </div>
</template>

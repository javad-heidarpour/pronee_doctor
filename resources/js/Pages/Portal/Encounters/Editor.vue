<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div>
          <p class="text-xs font-bold text-teal-700">مستندسازی بالینی پرونیکس</p>
          <h1 class="mt-1 text-xl font-black text-slate-900">{{ encounter ? 'یادداشت ویزیت' : 'ثبت ویزیت پزشکی' }}</h1>
          <p class="mt-2 text-sm text-slate-500">{{ patient.name }} · {{ appointment.clinic?.name || 'مطب' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link :href="route('portal.encounters.index', appointment.id)" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-700">تاریخچه ویزیت‌ها</Link>
          <Link :href="route('portal.patients.show', appointment.id)" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-700">بازگشت به نوبت</Link>
          <span v-if="readonly" class="rounded-xl bg-emerald-100 px-4 py-2.5 text-sm font-bold text-emerald-800">نهایی و قفل‌شده</span>
          <span v-else-if="encounter" class="rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-bold text-amber-800">پیش‌نویس</span>
        </div>
      </header>

      <section class="grid gap-3 md:grid-cols-3">
        <article class="rounded-2xl border p-4" :class="context.allergies?.length ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white'">
          <h2 class="text-sm font-black text-slate-900">حساسیت‌ها</h2>
          <p v-if="context.allergies?.length" class="mt-2 text-sm font-bold text-rose-800">{{ context.allergies.map(x => x.title).join('، ') }}</p>
          <p v-else class="mt-2 text-xs leading-6 text-slate-600">حساسیت فعالی در پرونده ثبت نشده؛ نبود داده به معنی نبود حساسیت نیست.</p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
          <h2 class="text-sm font-black text-slate-900">داروهای فعال ثبت‌شده</h2>
          <p v-if="context.medications?.length" class="mt-2 text-sm text-slate-700">{{ context.medications.map(x => x.title + (x.dosage ? ' (' + x.dosage + ')' : '')).join('، ') }}</p>
          <p v-else class="mt-2 text-xs leading-6 text-slate-600">داروی فعالی در پرونده ثبت نشده است؛ با بیمار تطبیق دهید.</p>
        </article>
        <article class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
          <h2 class="text-sm font-black text-slate-900">آخرین علائم حیاتی ثبت‌شده</h2>
          <div v-if="context.vitals?.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="vital in context.vitals.slice(0, 6)" :key="vital.id" class="rounded-lg bg-white px-2 py-1 text-xs text-slate-700">{{ vital.measurement_type }}: {{ vital.value }} {{ vital.unit }}</span>
          </div>
          <p v-else class="mt-2 text-xs text-slate-600">اندازه‌گیری‌ای ثبت نشده است.</p>
        </article>
      </section>

      <form @submit.prevent="saveDraft" class="space-y-5">
        <fieldset :disabled="readonly" class="space-y-5 disabled:opacity-90">
          <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-900">اطلاعات ویزیت</h2>
            <div class="grid gap-4 sm:grid-cols-2">
              <label class="space-y-1 text-xs font-bold text-slate-600">نوع یادداشت
                <select v-model="form.note_type" class="w-full rounded-xl border-slate-200 text-sm">
                  <option value="consultation">یادداشت مشاوره / ویزیت</option>
                  <option value="progress">یادداشت پیشرفت درمان</option>
                  <option value="emergency">یادداشت اورژانس</option>
                  <option value="procedure">یادداشت اقدام / پروسیجر</option>
                  <option value="discharge">خلاصه ترخیص</option>
                </select>
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">تشخیص (متن آزاد)
                <input v-model="form.diagnosis" maxlength="500" class="w-full rounded-xl border-slate-200 text-sm" placeholder="عنوان تشخیص" />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600 sm:col-span-2">کد تشخیص (اختیاری)
                <input v-model="form.diagnosis_code" maxlength="40" class="w-full rounded-xl border-slate-200 text-sm" placeholder="کد ICD-10 یا کد محلی تأییدشده" dir="ltr" />
              </label>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-900">یادداشت ساختاریافته بالینی (SOAP)</h2>
            <div class="grid gap-4">
              <label class="space-y-1 text-xs font-bold text-slate-600">S · شرح حال و شکایت اصلی
                <textarea v-model="form.subjective" rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="علت مراجعه، شرح علائم، مدت، شدت و سابقه مرتبط..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">O · یافته‌های معاینه و بررسی‌ها
                <textarea v-model="form.objective" rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="یافته‌های معاینه، نتایج بررسی‌ها و مشاهدات عینی..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">A · ارزیابی و استدلال بالینی <span class="text-rose-600">*</span>
                <textarea v-model="form.assessment" required rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="جمع‌بندی و ارزیابی پزشک..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">P · برنامه درمانی و توصیه‌ها <span class="text-rose-600">*</span>
                <textarea v-model="form.plan" required rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="برنامه درمان، آموزش بیمار، آزمایش‌ها و زمان مراجعه بعدی..." />
              </label>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
              <div><h2 class="text-base font-black text-slate-900">داروهای تجویزشده</h2><p class="mt-1 text-xs text-slate-500">اطلاعات دارو به‌صورت ساختاریافته ذخیره می‌شود؛ سامانه بررسی تداخل یا دوز ایمن انجام نمی‌دهد.</p></div>
              <button type="button" @click="addPrescription" class="rounded-xl bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">افزودن دارو +</button>
            </div>
            <div v-if="!form.prescriptions.length" class="rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-500">برای این ویزیت دارویی ثبت نشده است.</div>
            <article v-for="(item, index) in form.prescriptions" :key="index" class="mb-3 rounded-xl border border-slate-200 p-4">
              <div class="mb-3 flex items-center justify-between"><h3 class="text-sm font-black">داروی {{ (index + 1).toLocaleString('fa-IR') }}</h3><button type="button" @click="removePrescription(index)" class="text-xs font-bold text-rose-600">حذف</button></div>
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <label class="space-y-1 text-xs font-bold text-slate-600">نام دارو *<input v-model="item.medication_name" required maxlength="180" class="w-full rounded-lg border-slate-200 text-sm" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">قدرت / شکل دارویی<input v-model="item.strength" maxlength="120" class="w-full rounded-lg border-slate-200 text-sm" placeholder="مثلاً ۵۰۰ میلی‌گرم، قرص" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">مقدار هر نوبت *<input v-model="item.dosage" required maxlength="120" class="w-full rounded-lg border-slate-200 text-sm" placeholder="مثلاً یک قرص" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">راه مصرف *<input v-model="item.route" required maxlength="80" class="w-full rounded-lg border-slate-200 text-sm" placeholder="خوراکی، موضعی..." /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">دفعات مصرف *<input v-model="item.frequency" required maxlength="120" class="w-full rounded-lg border-slate-200 text-sm" placeholder="مثلاً هر ۸ ساعت" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">مدت (روز)<input v-model="item.duration_days" type="number" min="1" max="3650" class="w-full rounded-lg border-slate-200 text-sm" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600">تعداد / مقدار تحویلی<input v-model="item.quantity" maxlength="60" class="w-full rounded-lg border-slate-200 text-sm" /></label>
                <label class="space-y-1 text-xs font-bold text-slate-600 sm:col-span-2">دستور مصرف / توضیحات<input v-model="item.instructions" maxlength="2000" class="w-full rounded-lg border-slate-200 text-sm" /></label>
              </div>
            </article>
          </section>

          <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="text-sm font-black text-amber-950">تأیید بازبینی اطلاعات پیش از نهایی‌سازی</h2>
            <p class="mt-1 text-xs leading-6 text-amber-900">هر مورد را پس از بررسی پرونده و تطبیق با بیمار تأیید کنید. این چک‌لیست جایگزین قضاوت بالینی نیست.</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
              <label class="flex items-start gap-2 text-xs font-bold text-slate-800"><input v-model="form.reviewed_allergies" type="checkbox" class="mt-0.5 rounded border-slate-300" /> حساسیت‌ها با بیمار/پرونده بازبینی شد</label>
              <label class="flex items-start gap-2 text-xs font-bold text-slate-800"><input v-model="form.reviewed_medications" type="checkbox" class="mt-0.5 rounded border-slate-300" /> داروهای جاری بازبینی شد</label>
              <label class="flex items-start gap-2 text-xs font-bold text-slate-800"><input v-model="form.reviewed_vitals" type="checkbox" class="mt-0.5 rounded border-slate-300" /> علائم حیاتی بازبینی شد / در دسترس نبود</label>
            </div>
          </section>
        </fieldset>

        <div v-if="!readonly" class="flex flex-wrap justify-end gap-2">
          <button type="submit" :disabled="processing" class="rounded-xl bg-slate-800 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">{{ processing ? 'در حال ذخیره...' : 'ذخیره پیش‌نویس' }}</button>
          <button v-if="encounter" type="button" @click="signEncounter" :disabled="processing || !canSign" class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">نهایی‌سازی و قفل یادداشت</button>
        </div>
      </form>

      <section v-if="readonly" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-7 text-emerald-900">
        این یادداشت در تاریخ {{ formatDate(encounter.signed_at) }} نهایی شده است. محتوای یادداشت و نسخه‌ها پس از امضا قابل ویرایش نیستند.
      </section>
      <p v-if="errors && Object.keys(errors).length" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">{{ Object.values(errors).join(' · ') }}</p>
    </main>
  </PortalLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object, appointment: Object, patient: Object, context: Object,
  encounter: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
})
const readonly = computed(() => props.encounter?.status === 'signed')
const processing = ref(false)
const form = reactive({
  note_type: props.encounter?.note_type || 'consultation',
  subjective: props.encounter?.subjective || '',
  objective: props.encounter?.objective || '',
  assessment: props.encounter?.assessment || '',
  plan: props.encounter?.plan || '',
  diagnosis: props.encounter?.diagnosis || '',
  diagnosis_code: props.encounter?.diagnosis_code || '',
  reviewed_allergies: Boolean(props.encounter?.reviewed_allergies),
  reviewed_medications: Boolean(props.encounter?.reviewed_medications),
  reviewed_vitals: Boolean(props.encounter?.reviewed_vitals),
  prescriptions: (props.encounter?.prescriptions || []).map(x => ({ ...x, duration_days: x.duration_days || '' })),
})
const canSign = computed(() => form.reviewed_allergies && form.reviewed_medications && form.reviewed_vitals && form.assessment.trim() && form.plan.trim())
const addPrescription = () => form.prescriptions.push({ medication_name: '', strength: '', dosage: '', route: '', frequency: '', duration_days: '', quantity: '', instructions: '' })
const removePrescription = index => form.prescriptions.splice(index, 1)
const formatDate = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const payload = () => ({ ...form, prescriptions: form.prescriptions.map(x => ({ ...x, duration_days: x.duration_days || null })) })
const saveDraft = () => {
  processing.value = true
  const options = { preserveScroll: true, onFinish: () => { processing.value = false } }
  if (props.encounter) router.put(route('portal.encounters.update', props.encounter.id), payload(), options)
  else router.post(route('portal.encounters.store', props.appointment.id), payload(), options)
}
const signEncounter = () => {
  if (!canSign.value || !props.encounter) return
  if (window.confirm('پس از نهایی‌سازی، یادداشت و داروهای ثبت‌شده قفل می‌شوند. ادامه می‌دهید؟')) {
    processing.value = true
    router.post(route('portal.encounters.sign', props.encounter.id), {}, { preserveScroll: true, onFinish: () => { processing.value = false } })
  }
}
</script>
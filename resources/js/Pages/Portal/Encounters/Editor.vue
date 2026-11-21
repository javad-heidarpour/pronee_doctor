<template>
  <PortalLayout :doctor="doctor">
    <ActionDialog :open="signDialogOpen" title="نهایی‌سازی یادداشت ویزیت" description="پس از نهایی‌سازی، یادداشت و داروهای ثبت‌شده قفل می‌شوند و امکان ویرایش مستقیم وجود نخواهد داشت." submit-label="نهایی‌سازی و قفل یادداشت" :processing="processing" @close="signDialogOpen = false" @submit="confirmSignEncounter" />
    <main dir="rtl" class="mx-auto max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div>
          <p class="text-xs font-bold text-teal-700">مستندسازی بالینی پرونیکس</p>
          <h1 class="mt-1 text-xl font-black text-slate-900">{{ encounter ? 'یادداشت ویزیت' : 'ثبت ویزیت پزشکی' }}</h1>
          <p class="mt-2 text-sm text-slate-500">{{ patient.name }} · {{ appointment.clinic?.name || 'مطب' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link :href="route('portal.encounters.index', appointment.id)" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-700">تاریخچه ویزیت‌ها</Link>
          <Link :href="route('portal.follow-ups.index', { patient_id: patient.id, appointment_id: appointment.id })" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-bold text-teal-800">برنامه پیگیری بیمار</Link>
          <Link :href="route('portal.patients.show', appointment.id)" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-700">بازگشت به نوبت</Link>
          <span v-if="readonly" class="rounded-xl bg-emerald-100 px-4 py-2.5 text-sm font-bold text-emerald-800">نهایی و قفل‌شده</span>
          <span v-if="amendsEncounter" class="rounded-xl bg-violet-100 px-4 py-2.5 text-sm font-bold text-violet-800">اصلاحیه یادداشت شماره {{ amendsEncounter.id }}</span>
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

      <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
        <p class="text-xs font-bold text-indigo-700">PRONEEX CLINICAL DECISION SUPPORT</p><h2 class="mt-1 text-base font-black text-indigo-950">مراجعه‌گر هوشمند پیش از نهایی‌سازی</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3"><div v-for="item in context.decision_support?.checks || []" :key="item.key" class="rounded-xl bg-white p-3"><p class="text-xs font-black">{{item.title}}</p><p class="mt-1 text-xs leading-6" :class="item.status==='available'?'text-emerald-700':'text-amber-800'">{{item.message}}</p></div></div>
        <div v-if="prescriptionReview.length" class="mt-3 space-y-2"><div v-for="(item,index) in prescriptionReview" :key="index" class="rounded-lg border border-amber-200 bg-amber-100 p-3 text-xs leading-6"><strong>{{item.title}}</strong> {{item.message}}</div></div>
        <p class="mt-3 text-[11px] leading-6 text-indigo-900">این بررسی فقط کامل‌بودن و تازگی اطلاعات را ارزیابی می‌کند؛ تشخیص، تطبیق دارو با حساسیت، تداخل دارویی یا توصیه دوز انجام نمی‌شود. نبود داده به معنی طبیعی بودن وضعیت نیست و تصمیم نهایی با پزشک است.</p>
      </section>
      <form @submit.prevent="saveDraft" class="min-w-0 space-y-5">
        <div v-if="errors && Object.keys(errors).length" role="alert" aria-live="assertive" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm leading-7 text-rose-800">
          <p class="font-black">ثبت اطلاعات با خطا روبه‌رو شد. موارد زیر را اصلاح کنید:</p>
          <ul class="mt-2 list-inside list-disc"><li v-for="(message, field) in errors" :key="field">{{ message }}</li></ul>
        </div>
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

          <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 class="text-sm font-black text-indigo-950">استخراج ساختاریافته از متن بالینی</h2>
                <p class="mt-1 text-xs leading-6 text-indigo-900">متن دارای برچسب‌هایی مانند «شرح حال:»، «معاینه:»، «ارزیابی:» و «برنامه درمانی:» را به بخش‌های SOAP تفکیک کنید.</p>
              </div>
              <button type="button" @click="extractClinicalText" :disabled="readonly || extracting || !rawClinicalText.trim()" class="rounded-xl bg-indigo-700 px-4 py-2.5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">
                {{ extracting ? 'در حال استخراج...' : 'استخراج بخش‌ها' }}
              </button>
            </div>
            <textarea v-model="rawClinicalText" :disabled="readonly" rows="5" maxlength="12000" class="mt-3 w-full rounded-xl border-indigo-200 bg-white text-sm" placeholder="شرح حال: ...&#10;معاینه: ...&#10;ارزیابی: ...&#10;برنامه درمانی: ..."></textarea>
            <p class="mt-2 text-[11px] leading-6 text-indigo-900">استخراج بر اساس برچسب‌های صریح و بدون تفسیر پزشکی انجام می‌شود. متن خام به‌صورت خودکار ذخیره نمی‌شود؛ پیش از ثبت یا امضا، همه بخش‌ها را بازبینی کنید.</p>
            <div v-if="extractionError" role="alert" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">{{ extractionError }}</div>
            <div v-if="extractionResult" class="mt-3 space-y-3 rounded-xl bg-white p-4">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-black text-slate-800">نتیجه استخراج: {{ extractionResult.matched_sections.length }} بخش شناسایی‌شده</p>
                <button type="button" @click="applyExtractedText" :disabled="readonly || !extractionResult.matched_sections.length" class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white disabled:opacity-50">افزودن به فیلدهای خالی</button>
              </div>
              <p v-if="extractionNotice" class="text-xs leading-6 text-teal-800">{{ extractionNotice }}</p>
              <div class="grid gap-2 sm:grid-cols-2">
                <div v-for="key in ['subjective','objective','assessment','plan','diagnosis']" :key="key" class="rounded-lg border border-slate-100 p-3">
                  <p class="text-[11px] font-black text-slate-600">{{ sectionTitles[key] }}</p>
                  <p class="mt-1 whitespace-pre-wrap text-xs leading-6 text-slate-800">{{ extractionResult.sections[key] || 'بخشی با این برچسب در متن پیدا نشد.' }}</p>
                </div>
              </div>
              <div v-if="extractionResult.unclassified" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                <p class="text-xs font-black text-amber-900">متن بدون برچسب (جدا نگه داشته شده)</p>
                <p class="mt-1 whitespace-pre-wrap text-xs leading-6 text-amber-900">{{ extractionResult.unclassified }}</p>
              </div>
              <p class="text-[11px] leading-6 text-slate-500">فیلدهایی که از قبل متن دارند بازنویسی نمی‌شوند. برای استفاده از نتیجه در آن فیلدها، ابتدا محتوای فعلی را خودتان بررسی و در صورت لزوم پاک کنید.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-900">یادداشت ساختاریافته بالینی (SOAP)</h2>
            <div class="grid gap-4">
              <label class="space-y-1 text-xs font-bold text-slate-600">S · شرح حال و شکایت اصلی
                <VoiceDictationButton v-model="form.subjective" />
                <textarea v-model="form.subjective" rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="علت مراجعه، شرح علائم، مدت، شدت و سابقه مرتبط..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">O · یافته‌های معاینه و بررسی‌ها
                <VoiceDictationButton v-model="form.objective" />
                <textarea v-model="form.objective" rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="یافته‌های معاینه، نتایج بررسی‌ها و مشاهدات عینی..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">A · ارزیابی و استدلال بالینی <span class="text-rose-600">*</span>
                <VoiceDictationButton v-model="form.assessment" />
                <textarea v-model="form.assessment" required rows="4" maxlength="12000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="جمع‌بندی و ارزیابی پزشک..." />
              </label>
              <label class="space-y-1 text-xs font-bold text-slate-600">P · برنامه درمانی و توصیه‌ها <span class="text-rose-600">*</span>
                <VoiceDictationButton v-model="form.plan" />
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
        <p class="mt-1">امضاکننده: {{ encounter.signer?.name || '—' }} {{ encounter.signer?.family || '' }}</p>
        <Link :href="route('portal.encounters.amend', encounter.id)" class="mt-3 inline-flex rounded-lg bg-white px-3 py-2 text-xs font-bold text-violet-800">ایجاد اصلاحیه بدون تغییر سابقه اصلی</Link>
      </section>
      <p v-if="errors && Object.keys(errors).length" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">{{ Object.values(errors).join(' · ') }}</p>
    </main>
  </PortalLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import axios from 'axios'
import { router, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import VoiceDictationButton from '@/Components/Portal/VoiceDictationButton.vue'
import ActionDialog from '@/Components/Portal/ActionDialog.vue'

const props = defineProps({
  doctor: Object, appointment: Object, patient: Object, context: Object,
  encounter: { type: Object, default: null },
  amendsEncounter: { type: Object, default: null },
  errors: { type: Object, default: () => ({}) },
})
const readonly = computed(() => props.encounter?.status === 'signed')
const source = props.encounter || props.amendsEncounter
const processing = ref(false)
const signDialogOpen = ref(false)
const rawClinicalText = ref('')
const extracting = ref(false)
const extractionResult = ref(null)
const extractionError = ref('')
const extractionNotice = ref('')
const sectionTitles = {
  subjective: 'S · شرح حال',
  objective: 'O · یافته‌های عینی',
  assessment: 'A · ارزیابی',
  plan: 'P · برنامه درمانی',
  diagnosis: 'تشخیص ثبت‌شده',
}
const form = reactive({
  note_type: source?.note_type || 'consultation',
  subjective: source?.subjective || '',
  objective: source?.objective || '',
  assessment: source?.assessment || '',
  plan: source?.plan || '',
  diagnosis: source?.diagnosis || '',
  diagnosis_code: source?.diagnosis_code || '',
  amends_encounter_id: props.amendsEncounter?.id || null,
  reviewed_allergies: Boolean(props.encounter?.reviewed_allergies),
  reviewed_medications: Boolean(props.encounter?.reviewed_medications),
  reviewed_vitals: Boolean(props.encounter?.reviewed_vitals),
  prescriptions: (source?.prescriptions || []).map(x => ({ ...x, duration_days: x.duration_days || '' })),
})
const prescriptionReview = computed(() => {
  const seen = new Set(), warnings = []
  form.prescriptions.forEach(item => {
    const name = String(item.medication_name || '').trim()
    if (!name) return
    const key = name.toLocaleLowerCase().replace(/[\s\u200c\u200f-]+/g, '')
    if (seen.has(key)) warnings.push({title:'نام داروی تکراری:',message:name+' بیش از یک بار وارد شده؛ احتمال تکرار بررسی شود.'})
    else seen.add(key)
    if (!String(item.route || '').trim() || !String(item.frequency || '').trim()) warnings.push({title:'اطلاعات نسخه ناقص:',message:name+' فاقد راه یا دفعات مصرف کامل است.'})
  })
  return warnings
})
const canSign = computed(() => form.reviewed_allergies && form.reviewed_medications && form.reviewed_vitals && form.assessment.trim() && form.plan.trim())
const extractClinicalText = async () => {
  if (!rawClinicalText.value.trim() || extracting.value) return
  extracting.value = true
  extractionError.value = ''
  extractionNotice.value = ''
  extractionResult.value = null
  try {
    const response = await axios.post(route('portal.encounters.extract', props.appointment.id), {
      raw_text: rawClinicalText.value,
    })
    extractionResult.value = response.data
  } catch (error) {
    extractionError.value = error.response?.data?.message || 'استخراج متن انجام نشد. متن و دسترسی خود را بررسی و دوباره تلاش کنید.'
  } finally {
    extracting.value = false
  }
}
const applyExtractedText = () => {
  if (!extractionResult.value) return
  const sections = extractionResult.value.sections || {}
  const applied = []
  const skipped = []
  for (const key of ['subjective', 'objective', 'assessment', 'plan', 'diagnosis']) {
    const value = String(sections[key] || '').trim()
    if (!value) continue
    if (String(form[key] || '').trim()) {
      skipped.push(sectionTitles[key])
      continue
    }
    form[key] = value
    applied.push(sectionTitles[key])
  }
  extractionNotice.value = [
    applied.length ? 'به فیلدهای خالی افزوده شد: ' + applied.join('، ') : 'هیچ فیلد خالیِ قابل تکمیلی وجود نداشت.',
    skipped.length ? 'به دلیل وجود متن قبلی، این فیلدها بدون تغییر ماندند: ' + skipped.join('، ') : '',
  ].filter(Boolean).join(' ')
}
const addPrescription = () => form.prescriptions.push({ medication_name: '', strength: '', dosage: '', route: '', frequency: '', duration_days: '', quantity: '', instructions: '' })
const removePrescription = index => form.prescriptions.splice(index, 1)
const formatDate = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
const payload = () => ({ ...form, amends_encounter_id: props.amendsEncounter?.id || null, prescriptions: form.prescriptions.map(x => ({ ...x, duration_days: x.duration_days || null })) })
const saveDraft = () => {
  processing.value = true
  const options = { preserveScroll: true, onFinish: () => { processing.value = false } }
  if (props.encounter) router.put(route('portal.encounters.update', props.encounter.id), payload(), options)
  else router.post(route('portal.encounters.store', props.appointment.id), payload(), options)
}
const signEncounter = () => {
  if (!canSign.value || !props.encounter || processing.value) return
  signDialogOpen.value = true
}
const confirmSignEncounter = () => {
  if (!canSign.value || !props.encounter || processing.value) return
  processing.value = true
  router.post(route('portal.encounters.sign', props.encounter.id), {}, {
    preserveScroll: true,
    onSuccess: () => { signDialogOpen.value = false },
    onFinish: () => { processing.value = false },
  })
}
</script>
<script setup>
import { computed, reactive } from 'vue'
import ActionDialog from '@/Components/Portal/ActionDialog.vue'
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  providers: Object, outgoing: Array, incoming: Array, patients: Array, clinics: Array,
  filters: Object, providerTypes: Array, stats: Object, networkIntelligence: Object, collaborationOpportunities: Object,
})
const filter = reactive({ type: props.filters?.type || '', city: props.filters?.city || '', search: props.filters?.search || '' })
const providerForm = useForm({ provider_type: 'laboratory', name: '', city: '', address: '', phone: '', email: '', license_number: '', specialties: '', services: '', description: '' })
const referralForm = useForm({ patient_id: '', source_clinic_id: '', target_provider_id: '', service_type: 'laboratory', requested_services_text: '', priority: 'normal', reason: '', clinical_summary: '', patient_message: '', consent_confirmed: false })
const labelType = value => ({ doctor: 'پزشک', psychiatrist: 'روان‌پزشک', psychologist: 'روان‌شناس', counselor: 'مشاور', social_worker: 'مددکار اجتماعی', speech_therapist: 'گفتاردرمانگر', occupational_therapist: 'کاردرمانگر', nutritionist: 'متخصص تغذیه', nurse: 'پرستار', midwife: 'ماما', physiotherapist: 'فیزیوتراپیست', laboratory: 'آزمایشگاه', imaging: 'تصویربرداری', rehabilitation: 'توان‌بخشی', other: 'سایر گروه‌ها' }[value] || value)
const labelStatus = value => ({ sent: 'ارسال‌شده', accepted: 'پذیرفته‌شده', scheduled: 'زمان‌بندی‌شده', in_progress: 'در حال انجام', completed: 'تکمیل‌شده', rejected: 'عدم پذیرش', cancelled: 'لغوشده' }[value] || value)
const chooseProvider = provider => { referralForm.target_provider_id = provider.id; referralForm.service_type = ['doctor','psychiatrist','psychologist','counselor','social_worker','speech_therapist','occupational_therapist','nutritionist','nurse','midwife','physiotherapist','other'].includes(provider.provider_type) ? 'consultation' : provider.provider_type }
const submitFilters = () => router.get(route('portal.medical-network.index'), filter, { preserveState: true, preserveScroll: true })
const submitProvider = () => providerForm.transform(data => ({ ...data, specialties: data.specialties.split(/[,،]/).map(x => x.trim()).filter(Boolean), services: data.services.split(/[,،]/).map(x => x.trim()).filter(Boolean) })).post(route('portal.medical-network.providers.store'), { onSuccess: () => providerForm.reset() })
const submitReferral = () => referralForm.transform(data => ({ ...data, requested_services: ['laboratory', 'imaging'].includes(data.service_type) ? data.requested_services_text.split(/[,،\\n]/).map(item => item.trim()).filter(Boolean) : [] })).post(route('portal.medical-network.referrals.store'), { onSuccess: () => referralForm.reset() })
const actionDialog = reactive({ open:false,title:'',description:'',fields:[],errors:{},processing:false,submit:null })
const closeAction = () => { if (!actionDialog.processing) actionDialog.open = false }
const submitAction = values => {
  if (typeof actionDialog.submit !== 'function') return
  actionDialog.processing = true
  actionDialog.submit(values, { preserveScroll:true, onSuccess:()=>{actionDialog.open=false;actionDialog.errors={}}, onError:errors=>{actionDialog.errors=errors||{}}, onFinish:()=>{actionDialog.processing=false} })
}
const updateReferral = (item, status) => {
  const payload = { status }
  const fields = []
  if (status === 'scheduled') fields.push({key:'scheduled_at',label:'زمان مراجعه',type:'datetime-local',required:true})
  if (status === 'rejected') fields.push({key:'provider_response',label:'علت عدم پذیرش ارجاع',type:'textarea',required:true,maxlength:2000})
  if (status === 'completed') fields.push({key:'result_summary',label:'خلاصه نتیجه خدمت',type:'textarea',required:true,maxlength:4000})
  if (!fields.length) { router.patch(route('portal.medical-network.referrals.update', item.id), payload, { preserveScroll:true }); return }
  Object.assign(actionDialog,{open:true,title:status==='scheduled'?'زمان‌بندی ارجاع':status==='rejected'?'ثبت علت عدم پذیرش':'ثبت نتیجه خدمت',description:'اطلاعات زیر در سابقه ارجاع ثبت می‌شود.',fields,errors:{},processing:false,submit:(values,callbacks)=>router.patch(route('portal.medical-network.referrals.update',item.id),{...payload,...values},callbacks)})
}
const canRespond = item => ['sent','accepted','scheduled','in_progress'].includes(item.status)
const receivedCount = computed(() => props.incoming.filter(x => ['sent','accepted','scheduled','in_progress'].includes(x.status)).length)
</script>

<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto w-full min-w-0 max-w-7xl space-y-6 p-3 sm:p-4">
      <header class="rounded-2xl bg-gradient-to-l from-teal-900 to-cyan-800 p-6 text-white">
        <p class="text-xs font-bold text-teal-100">PRONEEX MEDICAL NETWORK</p>
        <h1 class="mt-2 text-2xl font-black">شبکه همکاری و ارجاع پزشکی</h1>
        <a :href="route('portal.medical-network.contracts.index')" class="mt-3 inline-flex rounded-lg bg-white/15 px-3 py-2 text-xs font-bold text-white hover:bg-white/25">مدیریت قراردادها و تسویه همکاری</a>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-teal-50">اتصال حرفه‌ای پزشکان، آزمایشگاه‌ها، مراکز تصویربرداری و توان‌بخشی؛ ثبت ارجاع قابل رهگیری و پیگیری پاسخ مرکز مقصد.</p>
        <div class="mt-4 flex flex-wrap gap-3 text-xs"><span class="rounded-full bg-white/15 px-3 py-2">{{ providers.total }} مرکز فعال در فهرست</span><span class="rounded-full bg-white/15 px-3 py-2">{{ receivedCount }} ارجاع دریافتی باز</span><span class="rounded-full bg-white/15 px-3 py-2">{{ outgoing.length }} ارجاع ارسالی اخیر</span></div>
      </header>

      <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">ارجاع‌های باز ارسالی</p><strong class="mt-1 block text-2xl">{{ stats.outgoing_open }}</strong></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">ارجاع‌های باز دریافتی</p><strong class="mt-1 block text-2xl">{{ stats.incoming_open }}</strong></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">نیازمند پیگیری</p><strong class="mt-1 block text-2xl text-rose-700">{{ stats.overdue }}</strong></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">تکمیل‌شده در ماه جاری</p><strong class="mt-1 block text-2xl text-teal-700">{{ stats.completed_month }}</strong></div>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div><h2 class="font-black">هوشمندی عملکرد شبکه ارجاع</h2><p class="mt-1 text-xs leading-6 text-slate-500">شاخص‌های عملیاتی بر اساس ارجاعات {{ networkIntelligence.window_days }} روز اخیر؛ این آمار معیار کیفیت تشخیص یا درمان نیست.</p></div>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs">حداقل نمونه معتبر: {{ networkIntelligence.minimum_sample }} ارجاع</span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <article v-for="metric in networkIntelligence.providers" :key="metric.provider_id" class="rounded-xl border p-3">
            <div class="flex items-start justify-between gap-2"><div><b class="text-sm">{{ metric.provider_name }}</b><p class="text-xs text-slate-500">{{ metric.city || 'شهر نامشخص' }} · {{ labelType(metric.provider_type) }}</p></div><span v-if="!metric.data_sufficient" class="rounded-full bg-amber-50 px-2 py-1 text-[10px] text-amber-800">نمونه ناکافی</span><span v-else class="rounded-full bg-teal-50 px-2 py-1 text-[10px] text-teal-800">داده کافی</span></div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <p>تعداد ارجاع: <b>{{ metric.sample_size }}</b></p><p>تکمیل‌شده: <b>{{ metric.completed_count }}</b></p>
              <p>نرخ پذیرش: <b>{{ metric.acceptance_rate === null ? '—' : metric.acceptance_rate + '%' }}</b></p>
              <p>نرخ تکمیل: <b>{{ metric.completion_rate === null ? '—' : metric.completion_rate + '%' }}</b></p>
              <p>میانگین پاسخ: <b>{{ metric.average_response_hours === null ? '—' : metric.average_response_hours + ' ساعت' }}</b></p>
              <p class="text-rose-700">معطل: <b>{{ metric.overdue_count }}</b></p>
            </div>
          </article>
          <p v-if="!networkIntelligence.providers.length" class="col-span-full rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-500">هنوز مرکز تأییدشده‌ای برای تحلیل وجود ندارد.</p>
        </div>
        <p class="mt-3 text-[11px] leading-6 text-slate-500">نرخ‌ها فقط با حداقل نمونه تعیین‌شده نمایش داده می‌شوند. اطلاعات بیمار در این گزارش تجمیعی نمایش داده نمی‌شود؛ «معطل» یعنی ارجاع ارسال‌شده بدون پاسخ بیش از ۴۸ ساعت یا زمان مراجعه گذشته در وضعیت باز.</p>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div><h2 class="font-black">فرصت‌های هوشمند همکاری</h2><p class="mt-1 text-xs leading-6 text-slate-500">پیشنهادها از تقاضای واقعی ارجاعات مطب شما در {{ collaborationOpportunities.window_days }} روز اخیر استخراج شده‌اند؛ امتیاز فقط تناسب کسب‌وکاری و جغرافیایی است.</p></div>
          <span class="rounded-full bg-teal-50 px-3 py-1 text-xs text-teal-800">پیشنهاد مبتنی بر داده</span>
        </div>
        <div v-for="group in collaborationOpportunities.items" :key="group.service_type" class="mt-4">
          <div class="mb-2 flex items-center justify-between gap-2"><h3 class="text-sm font-bold">{{ labelType(group.service_type === 'consultation' ? 'doctor' : group.service_type) }}</h3><span class="text-xs text-slate-500">{{ group.demand_count }} ارجاع مرتبط</span></div>
          <div class="grid gap-3 md:grid-cols-2">
            <article v-for="candidate in group.opportunities" :key="candidate.provider_id" class="rounded-xl border p-3">
              <div class="flex items-start justify-between gap-2"><div><b class="text-sm">{{ candidate.name }}</b><p class="mt-1 text-xs text-slate-500">{{ candidate.city || 'شهر ثبت نشده' }} · {{ labelType(candidate.provider_type) }}</p></div><span class="rounded-full bg-slate-100 px-2 py-1 text-xs">تناسب {{ candidate.fit_score }} از ۱۰۰</span></div>
              <p class="mt-2 text-xs leading-6 text-slate-600">{{ candidate.reasons.join(' · ') }}</p>
              <div v-if="candidate.services?.length" class="mt-2 flex flex-wrap gap-1"><span v-for="service in candidate.services.slice(0,3)" :key="service" class="rounded-full bg-slate-50 px-2 py-1 text-[10px]">{{ service }}</span></div>
              <div class="mt-3 flex items-center justify-between gap-2"><span class="text-[11px] text-slate-500">{{ candidate.phone || 'تلفن ثبت نشده' }} <span v-if="candidate.average_response_hours !== null">· پاسخ میانگین {{ candidate.average_response_hours }} ساعت</span></span><button @click="chooseProvider({ id: candidate.provider_id, provider_type: candidate.provider_type })" class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">انتخاب برای ارجاع</button></div>
            </article>
          </div>
        </div>
        <p v-if="!collaborationOpportunities.items.length" class="mt-3 rounded-xl bg-slate-50 p-4 text-xs leading-6 text-slate-500">برای پیشنهاد همکاری، هنوز تقاضای ارجاع کافی در بازه اخیر ثبت نشده یا مرکز تأییدشده منطبق در شبکه وجود ندارد.</p>
        <p class="mt-3 text-[11px] leading-6 text-slate-500">امتیاز از ترکیب حجم تقاضا (حداکثر ۴۰)، هم‌شهری بودن (۳۰)، سابقه ارجاع شبکه (حداکثر ۱۵) و نرخ تکمیل با حداقل ۵ نمونه (حداکثر ۱۵) محاسبه می‌شود. این امتیاز تضمین ظرفیت، کیفیت درمان یا پذیرش مرکز نیست.</p>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="mb-2 font-black">درخواست عضویت پزشک یا مرکز جدید</h2>
        <p class="mb-3 text-xs leading-6 text-slate-500">پس از ثبت، مدیر شبکه مدارک و مجوزها را بررسی می‌کند. پروفایل تا زمان تأیید در جست‌وجوی عمومی نمایش داده نمی‌شود.</p>
        <form @submit.prevent="submitProvider" class="grid gap-3 md:grid-cols-2">
          <label class="text-xs font-semibold">نوع عضو<select v-model="providerForm.provider_type" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="doctor">پزشک</option><option value="psychiatrist">روان‌پزشک</option><option value="psychologist">روان‌شناس</option><option value="counselor">مشاور</option><option value="social_worker">مددکار اجتماعی</option><option value="speech_therapist">گفتاردرمانگر</option><option value="occupational_therapist">کاردرمانگر</option><option value="nutritionist">متخصص تغذیه</option><option value="nurse">پرستار</option><option value="midwife">ماما</option><option value="physiotherapist">فیزیوتراپیست</option><option value="laboratory">آزمایشگاه</option><option value="imaging">مرکز تصویربرداری</option><option value="rehabilitation">مرکز توان‌بخشی</option><option value="other">سایر گروه‌ها</option></select></label>
          <label class="text-xs font-semibold">نام پزشک یا مرکز<input v-model="providerForm.name" required maxlength="180" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="نام کامل و رسمی"></label>
          <input v-model="providerForm.city" class="rounded-xl border-slate-300 text-sm" placeholder="شهر">
          <input v-model="providerForm.phone" class="rounded-xl border-slate-300 text-sm" placeholder="تلفن تماس">
          <input v-model="providerForm.email" type="email" class="rounded-xl border-slate-300 text-sm" placeholder="ایمیل">
          <input v-model="providerForm.license_number" class="rounded-xl border-slate-300 text-sm" placeholder="شماره مجوز / نظام پزشکی">
          <input v-model="providerForm.address" class="rounded-xl border-slate-300 text-sm md:col-span-2" placeholder="نشانی">
          <input v-model="providerForm.specialties" class="rounded-xl border-slate-300 text-sm" placeholder="تخصص‌ها (با ویرگول جدا کنید)">
          <input v-model="providerForm.services" class="rounded-xl border-slate-300 text-sm" placeholder="خدمات (با ویرگول جدا کنید)">
          <textarea v-model="providerForm.description" rows="2" class="rounded-xl border-slate-300 text-sm md:col-span-2" placeholder="معرفی کوتاه و امکانات مرکز"></textarea>
          <button :disabled="providerForm.processing" class="rounded-xl bg-slate-800 px-4 py-3 text-sm font-bold text-white disabled:opacity-50 md:col-span-2">ارسال درخواست عضویت برای بررسی</button>
        </form>
      </section>
      <section class="rounded-2xl border bg-white p-4">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2"><h2 class="font-black">جست‌وجوی همکاران شبکه</h2><span class="text-xs text-slate-500">نمایش مراکز تأییدشده</span></div>
        <form class="grid gap-2 md:grid-cols-4" @submit.prevent="submitFilters">
          <input v-model="filter.search" class="rounded-xl border-slate-300 text-sm" placeholder="نام مرکز، خدمت یا نشانی">
          <select v-model="filter.type" class="rounded-xl border-slate-300 text-sm"><option value="">همه گروه‌ها</option><option v-for="type in providerTypes" :key="type.value" :value="type.value">{{ type.label }}</option></select>
          <input v-model="filter.city" class="rounded-xl border-slate-300 text-sm" placeholder="شهر">
          <button class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-bold text-white">جست‌وجو</button>
        </form>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <article v-for="provider in providers.data" :key="provider.id" class="rounded-xl border p-4">
            <div class="flex items-start justify-between gap-2"><div><span class="text-xs font-bold text-teal-700">{{ labelType(provider.provider_type) }}</span><h3 class="mt-1 font-extrabold">{{ provider.name }}</h3></div><span v-if="provider.is_verified" class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] text-emerald-700">تأییدشده</span></div>
            <p class="mt-2 text-xs leading-6 text-slate-500">{{ provider.city || 'شهر ثبت نشده' }} · {{ provider.address || 'نشانی ثبت نشده' }}</p>
            <p v-if="provider.organization" class="text-xs text-slate-500">{{ provider.organization.name }}</p>
            <p class="mt-2 text-xs leading-6">{{ provider.description }}</p>
            <div class="mt-3 flex flex-wrap gap-1"><span v-for="service in (provider.services || []).slice(0,4)" :key="service" class="rounded-full bg-slate-100 px-2 py-1 text-[11px]">{{ service }}</span></div>
            <p class="mt-3 text-xs text-slate-500">{{ provider.phone || 'تلفن ثبت نشده' }} <span v-if="provider.rating">· امتیاز {{ provider.rating }}</span></p>
            <button @click="chooseProvider(provider)" class="mt-3 w-full rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">ثبت ارجاع به این مرکز</button>
          </article>
          <p v-if="!providers.data.length" class="col-span-full rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">مرکز فعالی با این مشخصات یافت نشد.</p>
        </div>
      </section>

      <section class="rounded-2xl border bg-white p-4">
        <h2 class="mb-3 font-black">ثبت ارجاع الکترونیکی بیمار</h2>
        <form @submit.prevent="submitReferral" class="grid gap-3 md:grid-cols-2">
          <label class="text-xs font-semibold">بیمار<select v-model="referralForm.patient_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب بیمار</option><option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.name }} · {{ patient.phone || 'بدون تلفن' }}</option></select></label>
          <label class="text-xs font-semibold">مطب مبدأ<select v-model="referralForm.source_clinic_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب مطب</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select></label>
          <label class="text-xs font-semibold">مرکز مقصد<select v-model="referralForm.target_provider_id" required class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="">انتخاب مرکز</option><option v-for="provider in providers.data" :key="provider.id" :value="provider.id">{{ provider.name }} ({{ labelType(provider.provider_type) }})</option></select></label>
          <label class="text-xs font-semibold">نوع خدمت<select v-model="referralForm.service_type" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="laboratory">آزمایش</option><option value="imaging">تصویربرداری</option><option value="rehabilitation">توان‌بخشی</option><option value="consultation">مشاوره تخصصی</option></select></label>
          <label v-if="['laboratory','imaging'].includes(referralForm.service_type)" class="text-xs font-semibold md:col-span-2">{{ referralForm.service_type === 'laboratory' ? 'آزمایش‌های درخواستی' : 'خدمات تصویربرداری درخواستی' }}<textarea v-model="referralForm.requested_services_text" required rows="3" maxlength="3000" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="هر مورد را در یک خط یا با ویرگول جدا کنید"></textarea><span class="mt-1 block text-[11px] font-normal text-slate-500">حداکثر ۳۰ مورد؛ نام هر آزمایش یا خدمت را جداگانه وارد کنید.</span></label>
          <label class="text-xs font-semibold">اولویت<select v-model="referralForm.priority" class="mt-1 w-full rounded-xl border-slate-300 text-sm"><option value="normal">عادی</option><option value="urgent">فوری</option></select></label>
          <label class="text-xs font-semibold md:col-span-2">علت ارجاع<input v-model="referralForm.reason" required maxlength="240" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="علت و خدمت موردنیاز"></label>
          <label class="text-xs font-semibold md:col-span-2">خلاصه بالینی (فقط اطلاعات ضروری)<textarea v-model="referralForm.clinical_summary" rows="3" maxlength="4000" class="mt-1 w-full rounded-xl border-slate-300 text-sm" placeholder="اطلاعات مرتبط برای انجام خدمت"></textarea></label>
          <label class="text-xs font-semibold md:col-span-2">پیام برای مرکز مقصد<textarea v-model="referralForm.patient_message" rows="2" maxlength="1000" class="mt-1 w-full rounded-xl border-slate-300 text-sm"></textarea></label>
          <label class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-xs leading-6 text-amber-900 md:col-span-2"><input v-model="referralForm.consent_confirmed" type="checkbox" required class="mt-1"><span>تأیید می‌کنم رضایت آگاهانه بیمار برای ارسال این ارجاع و اطلاعات بالینی ضروری دریافت شده است. اطلاعات بیمار فقط برای رسیدگی به همین ارجاع در اختیار مرکز مقصد قرار می‌گیرد.</span></label>
          <button :disabled="referralForm.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50 md:col-span-2">ثبت و ارسال ارجاع</button>
        </form>
      </section>

      <section class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border bg-white p-4"><h2 class="mb-3 font-black">ارجاعات ارسالی من</h2><div v-for="item in outgoing" :key="item.id" class="mb-2 rounded-xl bg-slate-50 p-3"><div class="flex flex-wrap justify-between gap-2"><b class="text-sm">{{ item.patient?.name }} ← {{ item.target_provider?.name }}</b><span class="text-xs text-teal-700">{{ labelStatus(item.status) }}</span></div><p class="mt-1 text-xs text-slate-500">کد رهگیری: {{ item.tracking_code }} · {{ item.reason }} · {{ item.source_clinic?.name || 'مبدأ نامشخص' }}</p><p v-if="item.requested_services?.length" class="mt-1 text-xs text-slate-600">موارد درخواستی: {{ item.requested_services.join('، ') }}</p><p v-if="item.scheduled_at" class="mt-1 text-xs text-indigo-700">زمان مراجعه: {{ new Date(item.scheduled_at).toLocaleString('fa-IR') }}</p><p v-if="item.result_summary" class="mt-1 text-xs text-emerald-800">نتیجه: {{ item.result_summary }}</p><button v-if="['sent','accepted','scheduled','in_progress'].includes(item.status)" @click="updateReferral(item,'cancelled')" class="mt-2 text-xs text-rose-600">لغو ارجاع</button></div><p v-if="!outgoing.length" class="text-xs text-slate-500">هنوز ارجاعی ثبت نشده است.</p></div>
        <div class="rounded-2xl border bg-white p-4"><h2 class="mb-3 font-black">ارجاعات دریافتی مراکز من</h2><div v-for="item in incoming" :key="item.id" class="mb-2 rounded-xl bg-slate-50 p-3"><div class="flex flex-wrap justify-between gap-2"><b class="text-sm">{{ item.patient?.name }} · {{ item.referring_doctor?.name }} {{ item.referring_doctor?.family }}</b><span class="text-xs text-teal-700">{{ labelStatus(item.status) }}</span></div><p class="mt-1 text-xs text-slate-500">کد {{ item.tracking_code }} · {{ item.reason }}</p><p v-if="item.clinical_summary" class="mt-2 text-xs leading-6">{{ item.clinical_summary }}</p><p v-if="item.requested_services?.length" class="mt-2 rounded-lg bg-indigo-50 p-2 text-xs leading-6 text-indigo-900">موارد تشخیصی درخواستی: {{ item.requested_services.join('، ') }}</p>
            <p v-if="item.scheduled_at" class="mt-1 text-xs font-semibold text-indigo-700">زمان مراجعه: {{ new Date(item.scheduled_at).toLocaleString('fa-IR') }}</p>
            <p v-if="item.result_summary" class="mt-2 rounded-lg bg-emerald-50 p-2 text-xs leading-6 text-emerald-900">نتیجه: {{ item.result_summary }}</p>
            <div v-if="item.events?.length" class="mt-2 border-t pt-2"><p class="text-[11px] font-bold text-slate-500">سوابق گردش ارجاع</p><p v-for="event in item.events.slice(0,3)" :key="event.id" class="mt-1 text-[11px] text-slate-500">{{ labelStatus(event.to_status || item.status) }} · {{ new Date(event.created_at).toLocaleString('fa-IR') }} {{ event.description || '' }}</p></div><div v-if="canRespond(item)" class="mt-3 flex flex-wrap gap-2"><button v-if="item.status==='sent'" @click="updateReferral(item,'accepted')" class="rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white">پذیرش</button><button v-if="['accepted','scheduled'].includes(item.status)" @click="updateReferral(item,'in_progress')" class="rounded-lg border px-3 py-2 text-xs">شروع خدمت</button><button v-if="['scheduled','in_progress'].includes(item.status)" @click="updateReferral(item,'completed')" class="rounded-lg border px-3 py-2 text-xs">ثبت نتیجه و تکمیل</button><button v-if="item.status==='accepted'" @click="updateReferral(item,'scheduled')" class="rounded-lg border px-3 py-2 text-xs">تعیین زمان مراجعه</button><button @click="updateReferral(item,'rejected')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs text-rose-600">عدم پذیرش</button></div></div><p v-if="!incoming.length" class="text-xs text-slate-500">ارجاع دریافتی وجود ندارد.</p></div>
      </section>
      <p class="rounded-xl bg-slate-100 p-3 text-xs leading-6 text-slate-600">توجه: پروفایل مراکز جدید پس از بررسی مدیر شبکه فعال می‌شود. ارجاع پزشکی جایگزین تماس در شرایط اورژانسی نیست. برای استفاده عملیاتی باید رضایت بیمار و سیاست‌های محرمانگی و دسترسی مرکز رعایت شود.</p>
    </main>
    <ActionDialog :open="actionDialog.open" :title="actionDialog.title" :description="actionDialog.description" :fields="actionDialog.fields" :errors="actionDialog.errors" :processing="actionDialog.processing" @close="closeAction" @submit="submitAction" />
  </PortalLayout>
</template>

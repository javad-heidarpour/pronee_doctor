<template>
<PortalLayout :doctor="doctor">
<div dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
  <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-indigo-950 to-teal-900 p-5 text-white shadow-lg sm:p-7">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div><p class="text-xs font-bold tracking-widest text-teal-200">PRONEEX PATIENT 360</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">نمای یکپارچه پرونده بیمار</h1><p class="mt-2 text-sm text-slate-200">مرور سریع اطلاعات ثبت‌شده در زیرسامانه‌های پرونیکس</p></div>
      <div class="flex flex-wrap gap-2">
        <Link v-if="canViewClinical" :href="route('portal.patients.insurance.index', patient.id)" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-bold text-white">سوابق بیمه</Link>
        <Link :href="route('portal.patients.directory')" class="rounded-xl border border-white/30 px-4 py-2 text-sm font-bold text-white">بازگشت به فهرست بیماران</Link>
      </div>
    </div>
    <div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3">
      <div><p class="text-xs text-teal-200">نام بیمار</p><p class="mt-1 text-xl font-black">{{patient.name}}</p></div>
      <div><p class="text-xs text-teal-200">شماره تماس</p><p class="mt-1 font-bold" dir="ltr">{{patient.phone || 'ثبت نشده'}}</p></div>
      <div><p class="text-xs text-teal-200">شناسه پرونده</p><p class="mt-1 font-bold">{{fa(patient.id)}}</p></div>
      <div v-if="patient.national_code"><p class="text-xs text-teal-200">کد ملی</p><p class="mt-1 font-bold">{{patient.national_code}}</p></div>
    </div>
  </header>

  <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    <Metric label="نوبت‌های اخیر" :value="summary.appointments"/><Metric label="ویزیت‌های ثبت‌شده" :value="summary.encounters"/>
    <Metric label="حساسیت‌های فعال" :value="summary.active_allergies"/><Metric label="داروهای ثبت‌شده" :value="summary.active_medications"/>
    <Metric label="مشکلات فعال" :value="summary.active_problems"/><Metric label="پیگیری‌های باز" :value="summary.open_follow_ups"/><Metric label="مسیرهای مراقبت فعال" :value="summary.active_pathways"/>
  </section>

  <section v-if="!canViewClinical" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-900">سطح دسترسی این حساب فقط اجازه مشاهده اطلاعات عمومی نوبت‌ها را می‌دهد. برای مشاهده اطلاعات بالینی، مجوز «مشاهده پرونده بالینی» باید توسط مدیر دسترسی فعال شود.</section>

  <section v-if="canViewClinical && clinicalSummary" class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-black text-slate-900">خلاصه ساختاریافته سوابق بیمار</h2><span class="text-[11px] text-slate-500">تولید {{dateTime(clinicalSummary.generated_at)}}</span></div>
    <div v-if="clinicalSummary.latest_encounter" class="mt-3 rounded-xl bg-white p-3"><strong class="text-sm">آخرین ویزیت ثبت‌شده</strong><p class="mt-1 text-xs text-slate-500">{{dateTime(clinicalSummary.latest_encounter.date)}} · {{statusLabel(clinicalSummary.latest_encounter.status)}}</p><p v-if="clinicalSummary.latest_encounter.diagnosis" class="mt-2 text-sm">تشخیص ثبت‌شده: {{clinicalSummary.latest_encounter.diagnosis}}</p><p v-if="clinicalSummary.latest_encounter.assessment" class="mt-1 text-xs leading-6">ارزیابی: {{clinicalSummary.latest_encounter.assessment}}</p><p v-if="clinicalSummary.latest_encounter.plan" class="mt-1 text-xs leading-6">برنامه ثبت‌شده: {{clinicalSummary.latest_encounter.plan}}</p></div>
    <div class="mt-3 grid gap-3 md:grid-cols-2">
      <article class="rounded-xl bg-white p-3"><h3 class="text-sm font-bold">حساسیت‌های ثبت‌شده</h3><ul v-if="clinicalSummary.allergies.length" class="mt-2 space-y-1 text-xs"><li v-for="(item,i) in clinicalSummary.allergies" :key="i">{{item.title}} <span v-if="item.description" class="text-slate-500">· {{item.description}}</span></li></ul><p v-else class="mt-2 text-xs text-slate-500">اطلاعات حساسیت در داده‌های بارگذاری‌شده موجود نیست؛ این به معنی نداشتن حساسیت نیست.</p></article>
      <article class="rounded-xl bg-white p-3"><h3 class="text-sm font-bold">داروهای فعال ثبت‌شده</h3><ul v-if="clinicalSummary.medications.length" class="mt-2 space-y-1 text-xs"><li v-for="(item,i) in clinicalSummary.medications" :key="i">{{item.title}} · {{item.dosage||'دوز ثبت نشده'}} · {{item.frequency||'تواتر ثبت نشده'}}</li></ul><p v-else class="mt-2 text-xs text-slate-500">فهرست داروی فعال در داده‌های بارگذاری‌شده موجود نیست.</p></article>
    </div>
    <p class="mt-3 text-[11px] leading-6 text-slate-500">این خلاصه فقط داده‌های ثبت‌شده و محدود به آخرین رکوردهای بارگذاری‌شده را بازچینش می‌کند؛ نبود داده، طبیعی بودن وضعیت یا نبود بیماری را ثابت نمی‌کند.</p>
  </section>

  <section v-if="canViewClinical && workflowSuggestions" class="rounded-2xl border border-teal-200 bg-white p-5">
    <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-black text-slate-900">دستیار گردش کار پزشک</h2><span class="rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-800">پیشنهادهای قاعده‌محور</span></div>
    <div v-if="workflowSuggestions.tasks.length" class="mt-3 grid gap-2 md:grid-cols-2"><article v-for="task in workflowSuggestions.tasks" :key="task.key" class="rounded-xl border p-3"><div class="flex items-center justify-between gap-2"><strong class="text-sm">{{task.title}}</strong><span class="text-[10px]" :class="task.priority==='high'?'text-rose-700':'text-slate-500'">{{task.priority==='high'?'اولویت بالاتر':'عادی'}}</span></div><p class="mt-1 text-xs leading-6 text-slate-600">{{task.reason}}</p></article></div>
    <p v-else class="mt-3 text-sm text-slate-500">بر اساس داده‌های موجود، یادآوری تکمیلی فعالی تولید نشد؛ همچنان اطلاعات را با بیمار تطبیق دهید.</p>
    <p class="mt-3 text-[11px] leading-6 text-slate-500">{{workflowSuggestions.disclaimer}}</p>
  </section>

  <section v-if="canViewClinical" class="rounded-2xl border bg-white p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="font-black text-slate-900">تشخیص‌ها و مشکلات بیمار</h2>
      <span class="text-xs text-slate-500">{{ fa(problems.length) }} مورد ثبت‌شده</span>
    </div>
    <div v-if="problems.length" class="mt-3 grid gap-3 md:grid-cols-2">
      <article v-for="problem in problems" :key="problem.id" class="rounded-xl border border-slate-100 p-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <span class="rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">{{ problemCategoryLabel(problem.category) }}</span>
            <h3 class="mt-2 text-sm font-bold text-slate-800">{{ problem.title }}</h3>
            <p v-if="problem.code" class="mt-1 text-xs text-slate-500" dir="ltr">{{ problem.code }} · {{ problem.code_system.toUpperCase() }}</p>
          </div>
          <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] text-slate-600">{{ problemStatusLabel(problem.status) }}</span>
        </div>
        <p v-if="problem.description" class="mt-2 whitespace-pre-line text-xs leading-6 text-slate-600">{{ problem.description }}</p>
        <p class="mt-2 text-[11px] text-slate-500">
          <span v-if="problem.severity">شدت: {{ problemSeverityLabel(problem.severity) }} · </span>
          <span v-if="problem.onset_date">شروع: {{ problem.onset_date }} · </span>
          {{ problem.source === 'patient_reported' ? 'اظهار بیمار' : 'ارزیابی پزشک' }}
        </p>
      </article>
    </div>
    <p v-else class="mt-3 text-sm text-slate-500">تشخیص یا مشکل ساختاریافته‌ای ثبت نشده است.</p>
  </section>

  <section v-if="canViewClinical" class="space-y-3">
    <div class="flex items-center justify-between"><h2 class="text-lg font-black text-slate-900">آخرین علائم حیاتی</h2><span class="text-xs text-slate-500">آخرین مقدار ثبت‌شده از هر نوع</span></div>
    <div v-if="latestVitals.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
      <article v-for="v in latestVitals" :key="v.type" class="rounded-2xl border bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">{{vitalLabel(v.type)}}</p><p class="mt-2 text-2xl font-black text-slate-900">{{v.value}} <span class="text-xs font-medium text-slate-500">{{v.unit}}</span></p><p class="mt-2 text-[11px] text-slate-400">{{dateTime(v.measured_at)}}</p></article>
    </div>
    <div v-else class="rounded-xl border border-dashed p-5 text-sm text-slate-500">اندازه‌گیری ثبت‌شده‌ای در دسترس نیست؛ این وضعیت به معنی طبیعی بودن علائم حیاتی نیست.</div>
  </section>

  <section v-if="canViewClinical" class="grid gap-4 xl:grid-cols-2">
    <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">حساسیت‌ها و سوابق پزشکی</h2><div v-if="clinicalRecords.length" class="mt-3 space-y-3"><div v-for="r in clinicalRecords" :key="r.id" class="rounded-xl bg-slate-50 p-3"><div class="flex flex-wrap justify-between gap-2"><strong class="text-sm">{{r.title}}</strong><span class="rounded-full bg-white px-2 py-1 text-[11px] text-slate-600">{{recordLabel(r.kind)}}</span></div><p v-if="r.description" class="mt-2 whitespace-pre-line text-xs leading-6 text-slate-600">{{r.description}}</p><p v-if="r.dosage||r.frequency" class="mt-1 text-xs text-slate-500">{{r.dosage}} {{r.frequency}}</p></div></div><p v-else class="mt-3 text-sm text-slate-500">سابقه فعال ثبت نشده است.</p></article>
    <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">داروهای همراه بیمار</h2><div v-if="medicationPlans.length" class="mt-3 space-y-3"><div v-for="m in medicationPlans" :key="m.id" class="rounded-xl bg-slate-50 p-3"><div class="flex justify-between gap-2"><strong class="text-sm">{{m.medication || 'داروی ثبت‌شده'}}</strong><span class="text-xs text-slate-500">{{m.status}}</span></div><p class="mt-1 text-xs text-slate-600">{{m.strength}} · {{m.dosage}} · {{m.frequency}}</p><p class="mt-1 text-[11px] text-slate-400">{{m.start_date || '—'}} تا {{m.end_date || '—'}}</p></div></div><p v-else class="mt-3 text-sm text-slate-500">برنامه دارویی همراه متصل به نسخه موجود نیست.</p><p class="mt-3 text-[11px] leading-5 text-amber-700">فهرست فوق جایگزین تطبیق داروهای مصرفی واقعی بیمار نیست.</p></article>
  </section>

  <section v-if="canViewClinical" class="grid gap-4 xl:grid-cols-2">
    <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">ویزیت‌ها و نسخه‌ها</h2><div v-if="encounters.length" class="mt-3 space-y-3"><div v-for="e in encounters" :key="e.id" class="rounded-xl border p-3"><div class="flex flex-wrap justify-between gap-2"><strong class="text-sm">{{e.diagnosis || 'ویزیت بالینی'}}</strong><span class="text-xs text-slate-500">{{dateTime(e.date)}}</span></div><p v-if="e.assessment" class="mt-2 text-xs leading-6 text-slate-600">{{e.assessment}}</p><p v-if="e.plan" class="mt-1 text-xs leading-6 text-slate-600">برنامه: {{e.plan}}</p><div v-if="e.prescriptions.length" class="mt-3 border-t pt-2"><p class="text-xs font-bold">اقلام نسخه</p><p v-for="(rx,i) in e.prescriptions" :key="i" class="mt-1 text-xs text-slate-600">{{rx.name}} {{rx.strength}} — {{rx.dosage}}، {{rx.route}}، {{rx.frequency}}</p></div></div></div><p v-else class="mt-3 text-sm text-slate-500">ویزیت ثبت‌شده‌ای وجود ندارد.</p></article>
    <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">پیگیری‌ها</h2><div v-if="followUps.length" class="mt-3 space-y-2"><div v-for="f in followUps" :key="f.id" class="flex items-start justify-between gap-3 rounded-xl bg-slate-50 p-3"><div><strong class="text-sm">{{f.title}}</strong><p v-if="f.description" class="mt-1 text-xs text-slate-500">{{f.description}}</p><p class="mt-1 text-[11px] text-slate-400">موعد: {{dateTime(f.due_at)}}</p></div><span class="shrink-0 rounded-full bg-white px-2 py-1 text-[11px]">{{statusLabel(f.status)}}</span></div></div><p v-else class="mt-3 text-sm text-slate-500">پیگیری ثبت‌شده‌ای وجود ندارد.</p></article>
  </section>

  <section v-if="canViewClinical" class="rounded-2xl border bg-white p-5"><h2 class="font-black">مسیرهای مراقبت</h2><div v-if="pathways.length" class="mt-3 grid gap-3 md:grid-cols-2"><article v-for="p in pathways" :key="p.id" class="rounded-xl bg-slate-50 p-4"><div class="flex justify-between gap-2"><strong class="text-sm">{{p.title}}</strong><span class="text-xs text-slate-500">{{statusLabel(p.status)}}</span></div><p v-if="p.goal" class="mt-2 text-xs text-slate-600">{{p.goal}}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-teal-600" :style="{width:(p.steps_total?100*p.steps_verified/p.steps_total:0)+'%'}"></div></div><p class="mt-1 text-[11px] text-slate-500">{{fa(p.steps_verified)}} از {{fa(p.steps_total)}} گام تأییدشده</p><div v-for="step in p.steps" :key="step.title" class="mt-2 flex justify-between gap-2 text-xs"><span>{{step.title}}</span><span class="text-slate-500">{{statusLabel(step.status)}}</span></div></article></div><p v-else class="mt-3 text-sm text-slate-500">مسیر مراقبتی ثبت نشده است.</p></section>

  <section class="rounded-2xl border bg-white p-5"><div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-black">خط زمانی مراقبت</h2><span class="text-xs text-slate-500">{{fa(timeline.length)}} رویداد اخیر</span></div><div v-if="timeline.length" class="mt-4 space-y-0"><div v-for="(item,index) in timeline" :key="item.id" class="relative flex gap-3 pb-5"><div class="flex w-5 shrink-0 flex-col items-center"><span class="mt-1 h-3 w-3 rounded-full bg-indigo-600 ring-4 ring-indigo-50"></span><span v-if="index!==timeline.length-1" class="mt-1 w-px flex-1 bg-slate-200"></span></div><div class="min-w-0 flex-1 rounded-xl bg-slate-50 p-3"><div class="flex flex-wrap justify-between gap-2"><strong class="text-sm">{{item.title}}</strong><span class="text-[11px] text-slate-500">{{dateTime(item.date)}}</span></div><p v-if="item.description" class="mt-1 text-xs leading-6 text-slate-600">{{item.description}}</p><span class="mt-2 inline-block rounded-full bg-white px-2 py-1 text-[10px] text-slate-500">{{statusLabel(item.status)}}</span></div></div></div><p v-else class="mt-3 text-sm text-slate-500">رویدادی برای نمایش وجود ندارد.</p></section>

  <footer class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950"><strong>ملاحظات:</strong> این صفحه تجمیع اطلاعات ثبت‌شده است، نه پرونده کامل تضمین‌شده یا توصیه تشخیصی. داده‌های هر بخش محدود به آخرین رکوردهای بارگذاری‌شده‌اند؛ نبود داده به معنی نبود بیماری یا طبیعی بودن وضعیت نیست. اطلاعات باید پیش از تصمیم بالینی با بیمار و منبع اصلی تطبیق داده شود.</footer>
</div>
</PortalLayout>
</template>

<script setup>
import {defineComponent,h} from 'vue'
import {Link} from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props=defineProps({
 doctor:{type:Object,required:true},patient:{type:Object,required:true},summary:{type:Object,required:true},
 clinicalSummary:{type:Object,default:null},workflowSuggestions:{type:Object,default:null},
 canViewClinical:{type:Boolean,default:false},clinicalRecords:{type:Array,default:()=>[]},
 vitals:{type:Array,default:()=>[]},latestVitals:{type:Array,default:()=>[]},encounters:{type:Array,default:()=>[]},
 appointments:{type:Array,default:()=>[]},followUps:{type:Array,default:()=>[]},pathways:{type:Array,default:()=>[]},
 medicationPlans:{type:Array,default:()=>[]},problems:{type:Array,default:()=>[]},timeline:{type:Array,default:()=>[]},dataMeta:{type:Object,default:()=>({})}
})
const Metric=defineComponent({props:{label:String,value:[String,Number]},setup(p){return()=>h('div',{class:'rounded-2xl border bg-white p-4 shadow-sm'},[h('p',{class:'text-xs text-slate-500'},p.label),h('p',{class:'mt-2 text-2xl font-black text-slate-900'},Number(p.value||0).toLocaleString('fa-IR'))])}})
const fa=v=>Number(v||0).toLocaleString('fa-IR')
const dateTime=v=>v?new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)):'—'
const vitalLabel=k=>({systolic:'فشار سیستولیک',diastolic:'فشار دیاستولیک',heart_rate:'ضربان قلب',temperature:'دما',oxygen_saturation:'اشباع اکسیژن',blood_glucose:'قند خون',weight:'وزن',height:'قد'}[k]||k)
const problemCategoryLabel=v=>({diagnosis:'تشخیص',condition:'بیماری / وضعیت',symptom:'علامت',risk_factor:'عامل خطر'}[v]||'مشکل بالینی')
const problemStatusLabel=v=>({active:'فعال',resolved:'برطرف‌شده',inactive:'غیرفعال'}[v]||'نامشخص')
const problemSeverityLabel=v=>({mild:'خفیف',moderate:'متوسط',severe:'شدید',critical:'بحرانی'}[v]||'نامشخص')
const recordLabel=k=>({allergy:'حساسیت',medication:'دارو',medical_history:'سابقه پزشکی',adverse_reaction:'عارضه دارویی'}[k]||'سابقه')
const statusLabel=s=>({draft:'پیش‌نویس',signed:'نهایی‌شده',pending:'در انتظار',in_progress:'در حال انجام',completed:'تکمیل‌شده',verified:'تأییدشده',active:'فعال',resolved:'برطرف‌شده',inactive:'غیرفعال',cancelled:'لغوشده',confirmed:'تأییدشده',visited:'ویزیت‌شده',recorded:'ثبت‌شده',fulfilled:'انجام‌شده',offered:'پیشنهادشده'}[s]||s||'نامشخص')
</script>

<template>
  <PortalLayout :doctor="doctor" title="مسیر مراقبت بیمار">
    <div dir="rtl" class="space-y-5">
      <section class="rounded-2xl bg-gradient-to-l from-teal-950 via-slate-900 to-cyan-900 p-6 text-white">
        <p class="text-xs font-bold tracking-widest text-teal-200">PRONEEX PERSONALIZED CARE PATHWAY</p>
        <h1 class="mt-2 text-2xl font-black">برنامه مراقبت پیوسته بیمار</h1>
        <p class="mt-2 text-sm leading-7 text-slate-200">اهداف و گام‌های مراقبتی را تعریف کنید، پیش‌نویس را بررسی کنید و پس از تأیید پزشک برای بیمار فعال سازید.</p>
      </section>
      <section class="grid grid-cols-3 gap-3">
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-slate-500">پیش‌نویس</p><p class="mt-2 text-2xl font-black">{{ fa(stats.draft) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-teal-700">فعال</p><p class="mt-2 text-2xl font-black text-teal-700">{{ fa(stats.active) }}</p></div>
        <div class="rounded-xl border bg-white p-4"><p class="text-xs text-emerald-700">تکمیل‌شده</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ fa(stats.completed) }}</p></div>
      </section>
      <form v-if="canManageClinical" @submit.prevent="submit" class="space-y-4 rounded-2xl border bg-white p-5">
        <h2 class="font-black">ایجاد برنامه جدید</h2>
        <div class="grid gap-3 md:grid-cols-2">
          <select v-model="form.patient_id" required class="rounded-xl border-slate-200 text-sm"><option value="">انتخاب بیمار</option><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }}</option></select>
          <select v-model="form.clinic_id" required class="rounded-xl border-slate-200 text-sm"><option value="">انتخاب مطب</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <input v-model="form.title" required maxlength="180" placeholder="عنوان برنامه مراقبتی" class="rounded-xl border-slate-200 text-sm">
          <input v-model="form.goal" maxlength="500" placeholder="هدف مراقبتی (اختیاری)" class="rounded-xl border-slate-200 text-sm">
          <input v-model="form.starts_at" type="date" class="rounded-xl border-slate-200 text-sm">
          <input v-model="form.ends_at" type="date" class="rounded-xl border-slate-200 text-sm">
        </div>
        <textarea v-model="form.description" rows="2" maxlength="4000" placeholder="توضیحات و ملاحظات برنامه" class="w-full rounded-xl border-slate-200 text-sm"></textarea>
        <div class="space-y-3">
          <div class="flex items-center justify-between"><h3 class="font-bold">گام‌های مراقبت</h3><button type="button" @click="addStep" :disabled="form.steps.length >= 20" class="rounded-lg border px-3 py-2 text-xs font-bold">افزودن گام +</button></div>
          <div v-for="(step,index) in form.steps" :key="index" class="grid gap-2 rounded-xl bg-slate-50 p-3 md:grid-cols-[1fr_180px_180px_auto]">
            <input v-model="step.title" required maxlength="180" placeholder="عنوان گام" class="rounded-lg border-slate-200 text-sm">
            <select v-model="step.category" class="rounded-lg border-slate-200 text-sm"><option value="general">عمومی</option><option value="education">آموزش بیمار</option><option value="measurement">اندازه‌گیری</option><option value="appointment">مراجعه</option><option value="medication_adherence">پایبندی به نسخه</option><option value="lifestyle">سبک زندگی</option></select>
            <input v-model="step.due_at" type="datetime-local" class="rounded-lg border-slate-200 text-sm">
            <button type="button" @click="removeStep(index)" :disabled="form.steps.length === 1" class="rounded-lg px-3 text-rose-600">حذف</button>
            <textarea v-model="step.description" maxlength="2000" rows="2" placeholder="توضیح گام (اختیاری)" class="rounded-lg border-slate-200 text-sm md:col-span-4"></textarea>
          </div>
        </div>
        <p v-if="form.errors.steps" class="text-xs text-rose-600">{{ form.errors.steps }}</p>
        <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">ذخیره پیش‌نویس</button>
        <p class="text-xs leading-6 text-slate-500">ذخیره برنامه آن را برای بیمار قابل مشاهده نمی‌کند. فقط پزشک می‌تواند پیش‌نویس را فعال کند.</p>
      </form>
      <p v-if="!canManageClinical" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-900">مجوز ایجاد برنامه مراقبتی یا تأیید گام‌های آن برای حساب شما فعال نیست. امکان مشاهده برنامه‌های مجاز همچنان برقرار است.</p>
      <section class="space-y-3">
        <h2 class="font-black">برنامه‌های ثبت‌شده</h2>
        <article v-for="pathway in pathways.data" :key="pathway.id" class="rounded-2xl border bg-white p-4">
          <div class="flex flex-wrap items-start justify-between gap-2"><div><h3 class="font-black">{{ pathway.title }}</h3><p class="mt-1 text-xs text-slate-500">{{ pathway.patient?.name }} · {{ pathway.clinic?.name }}</p></div><span class="rounded-full px-3 py-1 text-xs font-bold" :class="statusClass(pathway.status)">{{ statusLabel(pathway.status) }}</span></div>
          <p v-if="pathway.goal" class="mt-2 text-sm text-slate-700">هدف: {{ pathway.goal }}</p>
          <div class="mt-3 space-y-2">
            <div v-for="step in pathway.steps" :key="step.id" class="rounded-lg bg-slate-50 p-3">
              <div class="flex flex-wrap justify-between gap-2"><strong class="text-sm">{{ step.title }}</strong><span class="text-xs text-slate-500">{{ stepStatus(step.status) }}</span></div>
              <p v-if="step.description" class="mt-1 text-xs leading-6 text-slate-600">{{ step.description }}</p>
              <p v-if="step.patient_reported_at" class="mt-1 text-xs text-amber-700">بیمار انجام این گام را گزارش کرده است.</p>
              <button v-if="canManageClinical && step.status === 'patient_reported'" @click="verify(step.id)" class="mt-2 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white">تأیید انجام گام</button>
            </div>
          </div>
          <button v-if="pathway.status === 'draft'" @click="activate(pathway.id)" class="mt-3 rounded-xl bg-indigo-700 px-4 py-2 text-xs font-bold text-white">تأیید پزشک و فعال‌سازی</button>
        </article>
        <p v-if="!pathways.data?.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-slate-500">هنوز برنامه مراقبتی ثبت نشده است.</p>
      </section>
      <div v-if="pathways.links?.length > 3" class="flex flex-wrap gap-2"><template v-for="link in pathways.links" :key="link.label"><button v-if="link.url" @click="router.get(link.url)" v-html="link.label" class="rounded-lg border px-3 py-2 text-xs"></button></template></div>
      <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950">این ابزار برای سازمان‌دهی برنامه‌ای است که پزشک تعیین می‌کند. گام‌های دارویی یا درمانی نباید بدون نسخه و تأیید پزشک ایجاد شوند. گزارش بیمار به‌تنهایی تأیید بالینی محسوب نمی‌شود.</section>
    </div>
  </PortalLayout>
</template>
<script setup>
import { useForm, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props = defineProps({ doctor:Object, pathways:Object, patients:Array, clinics:Array, stats:Object, canManageClinical:Boolean })
const form = useForm({ patient_id:'', clinic_id:'', title:'', goal:'', description:'', starts_at:'', ends_at:'', steps:[{title:'',description:'',category:'general',due_at:''}] })
const addStep = () => { if(form.steps.length<20) form.steps.push({title:'',description:'',category:'general',due_at:''}) }
const removeStep = i => { if(form.steps.length>1) form.steps.splice(i,1) }
const submit = () => form.transform(d=>({...d,starts_at:d.starts_at||null,ends_at:d.ends_at||null,steps:d.steps.map(s=>({...s,due_at:s.due_at||null}))})).post(route('portal.care-pathways.store'),{onSuccess:()=>form.reset('title','goal','description','steps')})
const activate = id => { if(window.confirm('پس از فعال‌سازی، برنامه برای صاحب پرونده قابل مشاهده می‌شود. ادامه می‌دهید؟')) router.post(route('portal.care-pathways.activate',id)) }
const verify = id => router.post(route('portal.care-pathway-steps.verify',id))
const fa = n => Number(n||0).toLocaleString('fa-IR')
const statusLabel = s => ({draft:'پیش‌نویس',active:'فعال',completed:'تکمیل‌شده',archived:'بایگانی'}[s]||s)
const statusClass = s => s==='active'?'bg-teal-100 text-teal-800':s==='completed'?'bg-emerald-100 text-emerald-800':'bg-slate-100 text-slate-700'
const stepStatus = s => ({pending:'در انتظار انجام',patient_reported:'گزارش بیمار؛ منتظر بررسی',verified:'تأییدشده',skipped:'انجام‌نشده'}[s]||s)
</script>

<script setup>
import { reactive } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props=defineProps({doctor:Object,clinics:Array,leads:Object,filters:Object,canManage:Boolean})
const form=useForm({name:'',phone:'',email:'',clinic_id:'',campaign_id:'',source:'website',contact_consent:false,notes:'',next_follow_up_at:''})
const edits=reactive({})
const statusLabel=v=>({new:'جدید',contacted:'تماس گرفته‌شده',qualified:'واجد شرایط',converted:'تبدیل به نوبت',lost:'از دست‌رفته'}[v]||v)
const dateTime=v=>v?new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)):'—'
const submit=()=>form.post(route('portal.marketing.leads.store'),{preserveScroll:true,onSuccess:()=>form.reset('name','phone','email','campaign_id','notes','next_follow_up_at')})
const editFor=id=>{if(!edits[id]){const item=props.leads.data.find(x=>x.id===id);edits[id]={status:item.status,next_follow_up_at:item.next_follow_up_at?String(item.next_follow_up_at).slice(0,16):'',notes:item.notes||'',converted_appointment_id:''}}return edits[id]}
const save=item=>router.patch(route('portal.marketing.leads.update',item.id),editFor(item.id),{preserveScroll:true})
const filter=status=>router.get(route('portal.marketing.leads.index'),{status,clinic_id:props.filters.clinic_id},{preserveState:true,replace:true})
</script>
<template>
  <Head title="سرنخ‌ها و ارتباطات کسب‌وکار" />
  <PortalLayout :doctor="doctor" title="مدیریت سرنخ‌ها">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black">مدیریت سرنخ‌ها و ارتباطات کسب‌وکار</h1><p class="mt-1 text-sm text-slate-500">ثبت رضایت تماس، پیگیری مرحله‌ای و اتصال تبدیل فقط به نوبت واقعی</p></div><Link :href="route('portal.marketing.index')" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت به بازاریابی</Link></header>
      <section v-if="canManage" class="rounded-2xl border bg-white p-5"><h2 class="font-black">ثبت سرنخ جدید</h2><p class="mt-1 text-xs leading-6 text-amber-700">اطلاعات بالینی یا دلیل مراجعه بیمار را در این بخش وارد نکنید. ثبت شماره تماس مستلزم رضایت صریح برای پیگیری است.</p>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-bold">نام<input v-model="form.name" required maxlength="160" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شماره همراه<input v-model="form.phone" required maxlength="45" inputmode="tel" dir="ltr" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">ایمیل (اختیاری)<input v-model="form.email" type="email" maxlength="180" dir="ltr" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شعبه<select v-model="form.clinic_id" class="mt-1 w-full rounded-xl border-slate-300"><option value="">کل مجموعه</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{c.name}}</option></select></label>
          <label class="text-xs font-bold">منبع<select v-model="form.source" class="mt-1 w-full rounded-xl border-slate-300"><option value="website">وب‌سایت</option><option value="social">شبکه اجتماعی</option><option value="sms">پیامک</option><option value="partner">همکار</option><option value="walk_in">مراجعه حضوری</option><option value="other">سایر</option></select></label>
          <label class="text-xs font-bold">زمان پیگیری بعدی<input v-model="form.next_follow_up_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold sm:col-span-2 lg:col-span-3">یادداشت ارتباطی (بدون اطلاعات سلامت)<textarea v-model="form.notes" maxlength="2000" rows="2" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
          <label class="flex items-start gap-2 text-xs sm:col-span-2 lg:col-span-3"><input v-model="form.contact_consent" type="checkbox" required class="mt-0.5 rounded"><span>رضایت صریح فرد برای تماس و پیگیری ارتباطی دریافت شده است.</span></label>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50 sm:col-span-2 lg:col-span-3">ثبت سرنخ</button>
        </form>
      </section>
      <section class="rounded-2xl border bg-white p-5"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-black">فهرست سرنخ‌ها</h2><select :value="filters.status" @change="filter($event.target.value)" class="rounded-xl border-slate-300 text-xs"><option value="">همه وضعیت‌ها</option><option value="new">جدید</option><option value="contacted">تماس‌گرفته</option><option value="qualified">واجد شرایط</option><option value="converted">تبدیل‌شده</option><option value="lost">از دست‌رفته</option></select></div>
        <div v-if="!leads.data?.length" class="py-8 text-center text-sm text-slate-500">سرنخی برای نمایش وجود ندارد.</div>
        <div v-else class="mt-3 space-y-3"><article v-for="item in leads.data" :key="item.id" class="rounded-xl border p-4"><div class="flex flex-wrap items-start justify-between gap-2"><div><h3 class="font-black">{{item.name}}</h3><p class="mt-1 text-xs text-slate-500">{{item.phone_masked}} · {{item.clinic||'کل مجموعه'}} · منبع {{item.source}}</p><p class="mt-1 text-[11px] text-slate-400">ثبت: {{dateTime(item.created_at)}} · رضایت تماس: {{item.contact_consent?'ثبت شده':'ثبت نشده'}}</p></div><span class="rounded-lg bg-slate-100 px-3 py-1 text-xs">{{statusLabel(item.status)}}</span></div>
          <div v-if="canManage" class="mt-4 grid gap-2 border-t pt-3 sm:grid-cols-2"><label class="text-xs">وضعیت<select v-model="editFor(item.id).status" class="mt-1 w-full rounded-lg border-slate-300"><option value="new">جدید</option><option value="contacted">تماس‌گرفته</option><option value="qualified">واجد شرایط</option><option value="converted">تبدیل به نوبت</option><option value="lost">از دست‌رفته</option></select></label><label class="text-xs">پیگیری بعدی<input v-model="editFor(item.id).next_follow_up_at" type="datetime-local" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label v-if="editFor(item.id).status==='converted'" class="text-xs sm:col-span-2">شناسه نوبت واقعی<input v-model="editFor(item.id).converted_appointment_id" type="number" min="1" required class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label class="text-xs sm:col-span-2">یادداشت ارتباطی<textarea v-model="editFor(item.id).notes" maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-slate-300"></textarea></label>
            <button @click="save(item)" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white sm:col-span-2">ذخیره تغییرات</button>
          </div>
        </article></div>
        <nav v-if="leads.links?.length" class="mt-4 flex flex-wrap gap-2"><Link v-for="l in leads.links" :key="l.label" :href="l.url||'#'" class="rounded-lg border px-3 py-2 text-xs" :class="l.active?'bg-slate-900 text-white':'bg-white'" v-html="l.label"/></nav>
      </section>
    </main>
  </PortalLayout>
</template>

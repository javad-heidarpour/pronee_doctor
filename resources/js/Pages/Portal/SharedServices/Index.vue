<script setup>
import { reactive } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props=defineProps({doctor:Object,organizations:Array,clinics:Array,ownServices:Array,availableServices:Array,requests:Array})
const publish=useForm({organization_id:props.organizations?.[0]?.id??'',clinic_id:props.clinics?.[0]?.id??'',title:'',category:'',description:'',price_rial:0,weekly_capacity:1,available_from:'',available_until:''})
const requestForms=reactive({})
const money=v=>new Intl.NumberFormat('fa-IR').format(Number(v||0))+' ریال'
const statusLabel=v=>({published:'منتشرشده',paused:'متوقف',pending:'در انتظار پاسخ',accepted:'پذیرفته‌شده',rejected:'ردشده'}[v]||v||'—')
const submitPublish=()=>publish.post(route('portal.shared-services.store'),{preserveScroll:true,onSuccess:()=>publish.reset('title','category','description','price_rial','available_from','available_until')})
const ensureRequest=id=>{if(!requestForms[id])requestForms[id]={organization_id:props.organizations?.[0]?.id??'',clinic_id:props.clinics?.[0]?.id??'',requested_capacity:1,request_note:''};return requestForms[id]}
const requestService=item=>router.post(route('portal.shared-services.requests.store',item.id),ensureRequest(item.id),{preserveScroll:true})
const respond=(item,decision)=>router.patch(route('portal.shared-services.requests.respond',item.id),{decision,response_note:''},{preserveScroll:true})
const ownOrgIds=new Set((props.organizations||[]).map(x=>Number(x.id)))
</script>
<template>
  <Head title="خدمات مشترک مراکز" />
  <PortalLayout :doctor="doctor" title="خدمات مشترک مراکز">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black">بازار خدمات مشترک مراکز درمانی</h1><p class="mt-1 text-sm text-slate-500">انتشار ظرفیت قابل ارائه، درخواست همکاری بین سازمانی و پاسخ‌گویی کنترل‌شده</p></div><Link :href="route('portal.medical-network.index')" class="rounded-xl border px-4 py-2 text-sm font-bold">شبکه پزشکان</Link></header>
      <section v-if="organizations.length && clinics.length" class="rounded-2xl border bg-white p-5">
        <h2 class="font-black">انتشار خدمت یا ظرفیت</h2><form @submit.prevent="submitPublish" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-bold">سازمان<select v-model="publish.organization_id" required class="mt-1 w-full rounded-xl border-slate-300"><option v-for="o in organizations" :key="o.id" :value="o.id">{{o.name}}</option></select></label>
          <label class="text-xs font-bold">شعبه ارائه‌دهنده<select v-model="publish.clinic_id" required class="mt-1 w-full rounded-xl border-slate-300"><option v-for="c in clinics" :key="c.id" :value="c.id">{{c.name}}</option></select></label>
          <label class="text-xs font-bold">عنوان خدمت<input v-model="publish.title" required maxlength="160" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">دسته‌بندی<input v-model="publish.category" required maxlength="60" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">هزینه هر خدمت (ریال)<input v-model="publish.price_rial" type="number" min="0" required class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">ظرفیت هفتگی<input v-model="publish.weekly_capacity" type="number" min="1" max="500" required class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شروع ارائه<input v-model="publish.available_from" type="date" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">پایان ارائه<input v-model="publish.available_until" type="date" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold sm:col-span-2 lg:col-span-3">توضیحات<textarea v-model="publish.description" required minlength="10" maxlength="3000" rows="3" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
          <button :disabled="publish.processing" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50 sm:col-span-2 lg:col-span-3">انتشار خدمت</button>
        </form>
      </section>
      <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">خدمات منتشرشده سایر مراکز</h2><div v-if="!availableServices.length" class="py-8 text-center text-sm text-slate-500">خدمت قابل درخواست دیگری در شبکه منتشر نشده است.</div>
        <div v-else class="mt-3 grid gap-3 lg:grid-cols-2"><article v-for="item in availableServices" :key="item.id" class="rounded-xl border p-4"><div class="flex justify-between gap-2"><h3 class="font-black">{{item.title}}</h3><span class="text-xs text-slate-500">{{item.category}}</span></div><p class="mt-1 text-xs text-slate-500">{{item.organization?.name}} · {{item.clinic?.name}}</p><p class="mt-3 text-sm leading-7">{{item.description}}</p><div class="mt-3 flex flex-wrap justify-between gap-2 text-xs"><strong>{{money(item.price_rial)}}</strong><span>ظرفیت هفتگی: {{item.weekly_capacity}}</span></div>
          <form v-if="organizations.length && clinics.length" @submit.prevent="requestService(item)" class="mt-4 grid gap-2 border-t pt-3 sm:grid-cols-2"><select v-model="ensureRequest(item.id).organization_id" class="rounded-lg border-slate-300 text-xs"><option v-for="o in organizations" :key="o.id" :value="o.id">{{o.name}}</option></select><select v-model="ensureRequest(item.id).clinic_id" class="rounded-lg border-slate-300 text-xs"><option v-for="c in clinics" :key="c.id" :value="c.id">{{c.name}}</option></select><input v-model="ensureRequest(item.id).requested_capacity" type="number" min="1" max="500" class="rounded-lg border-slate-300 text-xs" placeholder="ظرفیت درخواستی"><input v-model="ensureRequest(item.id).request_note" maxlength="2000" class="rounded-lg border-slate-300 text-xs" placeholder="توضیح درخواست"><button class="rounded-lg bg-indigo-700 px-3 py-2 text-xs font-bold text-white sm:col-span-2">درخواست همکاری</button></form>
        </article></div>
      </section>
      <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">خدمات سازمان من</h2><div v-if="!ownServices.length" class="py-5 text-sm text-slate-500">هنوز خدمتی منتشر نکرده‌اید.</div><div v-else class="mt-3 space-y-2"><article v-for="item in ownServices" :key="item.id" class="rounded-xl border p-3"><div class="flex flex-wrap justify-between gap-2"><strong>{{item.title}} · {{item.organization?.name}}</strong><span class="text-xs">{{item.requests_count}} درخواست · {{statusLabel(item.status)}}</span></div><p class="mt-1 text-xs text-slate-500">{{item.clinic?.name}} · {{money(item.price_rial)}}</p></article></div></section>
      <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">درخواست‌های همکاری</h2><div v-if="!requests.length" class="py-5 text-sm text-slate-500">درخواستی ثبت نشده است.</div><div v-else class="mt-3 space-y-2"><article v-for="item in requests" :key="item.id" class="rounded-xl border p-3"><div class="flex flex-wrap justify-between gap-2"><strong>{{item.service?.title}} · {{item.requestingOrganization?.name}}</strong><span class="rounded-lg bg-slate-100 px-2 py-1 text-xs">{{statusLabel(item.status)}}</span></div><p class="mt-1 text-xs text-slate-500">ظرفیت درخواستی: {{item.requested_capacity}} · {{item.request_note||'بدون توضیح'}}</p><div v-if="item.status==='pending' && ownOrgIds.has(Number(item.service?.organization_id))" class="mt-3 flex gap-2"><button @click="respond(item,'accept')" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white">پذیرش</button><button @click="respond(item,'reject')" class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-bold text-white">رد درخواست</button></div><p v-if="item.response_note" class="mt-2 text-xs text-slate-500">{{item.response_note}}</p></article></div></section>
    </main>
  </PortalLayout>
</template>

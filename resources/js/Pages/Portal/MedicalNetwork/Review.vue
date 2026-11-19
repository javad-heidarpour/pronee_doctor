<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ActionDialog from '@/Components/Portal/ActionDialog.vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
defineProps({ providers: Object })
const approve = provider => router.patch(route('portal.medical-network.providers.approve', provider.id), {}, { preserveScroll: true })
const dialog=reactive({open:false,errors:{},processing:false,provider:null})
const reject = provider => Object.assign(dialog,{open:true,provider,errors:{},processing:false})
const submitReject = values => {
  dialog.processing=true
  router.patch(route('portal.medical-network.providers.reject',dialog.provider.id),values,{preserveScroll:true,onSuccess:()=>{dialog.open=false;dialog.errors={}},onError:errors=>{dialog.errors=errors||{}},onFinish:()=>{dialog.processing=false}})
}
</script>
<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto max-w-5xl space-y-4 p-4">
      <header><h1 class="text-xl font-black">بررسی عضویت مراکز شبکه پزشکی</h1><p class="mt-1 text-sm text-slate-500">پیش از تأیید، هویت مرکز، شماره مجوز، اطلاعات تماس و مالکیت آن را راستی‌آزمایی کنید.</p></header>
      <article v-for="provider in providers.data" :key="provider.id" class="space-y-3 rounded-2xl border bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-2"><div><span class="text-xs font-bold text-teal-700">{{ provider.provider_type }}</span><h2 class="font-extrabold">{{ provider.name }}</h2><p class="text-xs text-slate-500">{{ provider.city }} · {{ provider.phone }} · {{ provider.email }}</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs text-amber-700">در انتظار بررسی</span></div>
        <p class="text-sm leading-6">{{ provider.address }}</p><p class="text-sm leading-6">{{ provider.description }}</p>
        <div class="grid gap-2 text-xs text-slate-600 sm:grid-cols-2"><p>شماره مجوز: {{ provider.license_number || 'ثبت نشده' }}</p><p>ثبت‌کننده: {{ provider.owner?.name }} {{ provider.owner?.family }} · {{ provider.owner?.phone }}</p><p>مجموعه: {{ provider.organization?.name || 'مستقل' }}</p><p>خدمات: {{ (provider.services || []).join('، ') || 'ثبت نشده' }}</p></div>
        <div class="flex flex-wrap gap-2"><button @click="approve(provider)" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">تأیید و انتشار در شبکه</button><button @click="reject(provider)" class="rounded-lg border border-rose-200 px-4 py-2 text-xs font-bold text-rose-700">رد درخواست</button></div>
      </article>
      <p v-if="!providers.data.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-slate-500">درخواست تأییدنشده‌ای وجود ندارد.</p>
    </main>
    <ActionDialog :open="dialog.open" title="رد درخواست عضویت مرکز" description="دلیل رد درخواست را ثبت کنید تا در سابقه بررسی باقی بماند." :fields="[{key:'reason',label:'دلیل رد درخواست',type:'textarea',required:true,maxlength:2000}]" :errors="dialog.errors" :processing="dialog.processing" @close="dialog.open=false" @submit="submitReject" />
  </PortalLayout>
</template>

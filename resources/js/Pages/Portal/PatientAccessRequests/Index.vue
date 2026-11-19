<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import ActionDialog from '@/Components/Portal/ActionDialog.vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'
defineProps({ requests: Object, doctor: Object })
const approvalDialog=reactive({open:false,processing:false,item:null,errors:{}})
const approve = item => Object.assign(approvalDialog,{open:true,item,processing:false,errors:{}})
const submitApprove = () => {
  approvalDialog.processing=true
  router.patch(route('portal.patient-access-requests.approve',approvalDialog.item.id),{},{
    preserveScroll:true,
    onSuccess:()=>{approvalDialog.open=false;approvalDialog.errors={}},
    onError:errors=>{approvalDialog.errors=errors||{}},
    onFinish:()=>{approvalDialog.processing=false},
  })
}
const dialog=reactive({open:false,errors:{},processing:false,item:null})
const reject = item => Object.assign(dialog,{open:true,item,errors:{},processing:false})
const submitReject = values => {
  dialog.processing=true
  router.patch(route('portal.patient-access-requests.reject',dialog.item.id),{review_note:values.review_note},{preserveScroll:true,onSuccess:()=>{dialog.open=false;dialog.errors={}},onError:errors=>{dialog.errors=errors||{}},onFinish:()=>{dialog.processing=false}})
}
</script>
<template>
  <PortalLayout>
    <main dir="rtl" class="mx-auto max-w-5xl space-y-4 p-4">
      <header><h1 class="text-xl font-black">درخواست‌های اتصال پرونده بیمار</h1><p class="mt-1 text-sm text-slate-500">تأیید فقط پس از احراز هویت بیمار از مسیر معتبر مرکز انجام شود.</p></header>
      <article v-for="item in requests.data" :key="item.id" class="space-y-3 rounded-2xl border bg-white p-4">
        <div class="flex flex-wrap justify-between gap-2"><div><h2 class="font-extrabold">{{ item.patient?.name }}</h2><p class="mt-1 text-xs text-slate-500">حساب درخواست‌کننده: {{ item.user?.name }} {{ item.user?.family }} · {{ item.user?.phone }} · {{ item.user?.email }}</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs text-amber-700">در انتظار بررسی</span></div>
        <p class="text-xs text-slate-500">تلفن پرونده: {{ item.patient?.phone }} · کد ملی پرونده: {{ item.patient?.national_code }}</p>
        <div class="flex gap-2"><button @click="approve(item)" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white">تأیید پس از احراز هویت</button><button @click="reject(item)" class="rounded-lg border border-rose-200 px-4 py-2 text-xs text-rose-700">رد درخواست</button></div>
      </article>
      <p v-if="!requests.data.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-slate-500">درخواست در انتظار بررسی وجود ندارد.</p>
    </main>
    <ActionDialog :open="approvalDialog.open" title="تأیید دسترسی پرونده" description="پس از احراز هویت بیمار از مسیر معتبر مرکز، دسترسی پرونده فعال شود؟" :fields="[]" :errors="approvalDialog.errors" :processing="approvalDialog.processing" submit-label="تأیید دسترسی" @close="approvalDialog.open=false" @submit="submitApprove" />
    <ActionDialog :open="dialog.open" title="رد درخواست دسترسی پرونده" description="دلیل رد درخواست را ثبت کنید." :fields="[{key:'review_note',label:'دلیل رد درخواست',type:'textarea',required:true,maxlength:2000}]" :errors="dialog.errors" :processing="dialog.processing" @close="dialog.open=false" @submit="submitReject" />
  </PortalLayout>
</template>

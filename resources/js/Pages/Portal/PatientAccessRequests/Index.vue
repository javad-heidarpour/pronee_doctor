<script setup>
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
defineProps({ requests: Object, doctor: Object })
const approve = item => {
  if (window.confirm('پس از احراز هویت بیمار، دسترسی پرونده فعال شود؟')) router.patch(route('portal.patient-access-requests.approve', item.id), {}, { preserveScroll: true })
}
const reject = item => {
  const note = window.prompt('دلیل رد درخواست را ثبت کنید:')
  if (note?.trim()) router.patch(route('portal.patient-access-requests.reject', item.id), { review_note: note }, { preserveScroll: true })
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
  </PortalLayout>
</template>

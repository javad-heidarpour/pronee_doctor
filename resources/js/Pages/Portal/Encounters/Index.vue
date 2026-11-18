<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-white p-5">
        <div><p class="text-xs font-bold text-teal-700">پرونده بالینی</p><h1 class="mt-1 text-xl font-black">تاریخچه ویزیت‌های پزشکی</h1><p class="mt-2 text-sm text-slate-500">{{ appointment.patient?.name }} · {{ appointment.clinic?.name }}</p></div>
        <div class="flex gap-2"><Link :href="route('portal.patients.show', appointment.id)" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت</Link><Link :href="route('portal.encounters.create', appointment.id)" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white">ثبت ویزیت جدید</Link></div>
      </header>
      <section v-if="!encounters.length" class="rounded-2xl border border-dashed bg-white p-10 text-center"><p class="font-bold text-slate-700">هنوز یادداشت ویزیتی ثبت نشده است.</p><Link :href="route('portal.encounters.create', appointment.id)" class="mt-4 inline-flex rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white">شروع ثبت ویزیت</Link></section>
      <section v-else class="space-y-3">
        <article v-for="item in encounters" :key="item.id" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-black text-slate-900">{{ item.diagnosis || noteType(item.note_type) }}</h2><span class="rounded-lg px-2 py-1 text-[11px] font-bold" :class="item.status === 'signed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">{{ item.status === 'signed' ? 'نهایی‌شده' : 'پیش‌نویس' }}</span></div><p class="mt-2 text-xs text-slate-500">{{ date(item.created_at) }} · نویسنده: {{ item.author?.name || 'حساب غیرفعال/حذف‌شده' }} {{ item.author?.family || '' }}</p><p v-if="item.amended_from" class="mt-1 text-xs font-bold text-violet-700">اصلاحیه یادداشت شماره {{ item.amended_from.id }}</p></div>
            <Link :href="route('portal.encounters.show', item.id)" class="rounded-xl border px-4 py-2 text-xs font-bold text-teal-800">{{ item.status === 'signed' ? 'مشاهده یادداشت' : 'ادامه پیش‌نویس' }}</Link>
          </div>
          <p v-if="item.assessment" class="mt-3 line-clamp-2 text-sm leading-7 text-slate-700">{{ item.assessment }}</p>
          <p class="mt-2 text-xs text-slate-500">{{ item.prescriptions?.length || 0 }} قلم دارو · {{ item.clinic?.name || 'مطب' }}</p>
        </article>
      </section>
    </main>
  </PortalLayout>
</template>
<script setup>
import { Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
defineProps({ doctor: Object, appointment: Object, encounters: Array })
const noteType = value => ({ consultation: 'یادداشت مشاوره / ویزیت', progress: 'یادداشت پیشرفت درمان', emergency: 'یادداشت اورژانس', procedure: 'یادداشت اقدام / پروسیجر', discharge: 'خلاصه ترخیص' }[value] || 'یادداشت ویزیت')
const date = value => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
</script>
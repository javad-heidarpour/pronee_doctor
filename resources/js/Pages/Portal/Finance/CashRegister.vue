<script setup>
import { reactive, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props = defineProps({ doctor:Object, clinics:Array, clinic:Object, session:Object, expected_balance_rial:Number, recent_sessions:Array })
const opening = reactive({ clinic_id:props.clinic.id, opening_balance_rial:0, opening_note:'' })
const entry = reactive({ direction:'in', category:'service_receipt', amount_rial:'', description:'' })
const closing = reactive({ closing_count_rial:'', closing_note:'' })
const busy = ref(false)
const money = v => new Intl.NumberFormat('fa-IR').format(Number(v||0))+' ریال'
const dateTime = v => v ? new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium',timeStyle:'short'}).format(new Date(v)) : '—'
const categories = () => entry.direction === 'in' ? [{value:'service_receipt',label:'دریافت بابت خدمت'},{value:'other_receipt',label:'سایر دریافت‌ها'}] : [{value:'operating_expense',label:'هزینه جاری'},{value:'supplier_payment',label:'پرداخت تأمین‌کننده'}]
const changeClinic = () => router.get(route('portal.finance.cash-register'),{clinic_id:opening.clinic_id},{preserveState:true,replace:true})
const post = (url,data) => { if(busy.value)return;busy.value=true;router.post(url,data,{preserveScroll:true,onFinish:()=>busy.value=false}) }
const openRegister = () => post(route('portal.finance.cash-register.open'),opening)
const addTransaction = () => { const key = (crypto?.randomUUID?.() || String(Date.now())+'-'+Math.random().toString(16).slice(2));post(route('portal.finance.cash-register.transactions.store',props.session.id),{...entry,idempotency_key:key}) }
const closeRegister = () => post(route('portal.finance.cash-register.close',props.session.id),closing)
</script>
<template>
  <Head title="صندوق و دریافت حضوری" />
  <PortalLayout :doctor="doctor" title="صندوق و دریافت حضوری">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black">مدیریت صندوق شعبه</h1><p class="mt-1 text-sm text-slate-500">ثبت دریافت و پرداخت نقدی، کنترل مانده و بستن صندوق با ثبت مغایرت</p></div><Link :href="route('portal.finance.index')" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت به امور مالی</Link></header>
      <section class="rounded-2xl border bg-white p-4"><label class="text-xs font-bold">شعبه فعال<select v-model="opening.clinic_id" @change="changeClinic" class="mt-1 w-full max-w-md rounded-xl border-slate-300"><option v-for="c in clinics" :key="c.id" :value="c.id">{{c.name}}</option></select></label></section>
      <section v-if="!session" class="rounded-2xl border bg-white p-5 sm:p-6">
        <h2 class="font-black">شروع نوبت کاری صندوق · {{clinic.name}}</h2><form @submit.prevent="openRegister" class="mt-4 grid gap-3 sm:grid-cols-2">
          <label class="text-xs font-bold">موجودی اولیه (ریال)<input v-model="opening.opening_balance_rial" type="number" min="0" max="1000000000000" required class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">یادداشت افتتاح<textarea v-model="opening.opening_note" maxlength="1000" rows="1" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
          <button :disabled="busy" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">بازکردن صندوق</button>
        </form>
      </section>
      <template v-else>
        <section class="grid gap-3 sm:grid-cols-3">
          <article class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">موجودی افتتاحیه</p><strong class="mt-2 block text-xl">{{money(session.opening_balance_rial)}}</strong></article>
          <article class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">مانده مورد انتظار</p><strong class="mt-2 block text-xl text-teal-700">{{money(expected_balance_rial)}}</strong></article>
          <article class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">شروع صندوق</p><strong class="mt-2 block text-sm">{{dateTime(session.opened_at)}}</strong></article>
        </section>
        <section class="grid gap-4 xl:grid-cols-2">
          <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">ثبت گردش جدید</h2><form @submit.prevent="addTransaction" class="mt-4 space-y-3">
            <div class="grid grid-cols-2 gap-2"><label class="text-xs font-bold">جهت<select v-model="entry.direction" @change="entry.category=categories()[0].value" class="mt-1 w-full rounded-xl border-slate-300"><option value="in">دریافت</option><option value="out">پرداخت</option></select></label><label class="text-xs font-bold">دسته<select v-model="entry.category" class="mt-1 w-full rounded-xl border-slate-300"><option v-for="c in categories()" :key="c.value" :value="c.value">{{c.label}}</option></select></label></div>
            <label class="block text-xs font-bold">مبلغ (ریال)<input v-model="entry.amount_rial" type="number" min="1" max="1000000000000" required class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block text-xs font-bold">شرح گردش<input v-model="entry.description" minlength="4" maxlength="500" required class="mt-1 w-full rounded-xl border-slate-300"></label>
            <button :disabled="busy" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">ثبت گردش و سند حسابداری</button>
          </form></article>
          <article class="rounded-2xl border bg-white p-5"><h2 class="font-black">بستن صندوق</h2><p class="mt-1 text-xs leading-6 text-slate-500">موجودی واقعی شمارش‌شده را وارد کنید؛ اختلاف با مانده مورد انتظار ثبت می‌شود و قابل حذف نیست.</p><form @submit.prevent="closeRegister" class="mt-4 space-y-3">
            <label class="block text-xs font-bold">موجودی شمارش‌شده (ریال)<input v-model="closing.closing_count_rial" type="number" min="0" max="1000000000000" required class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block text-xs font-bold">یادداشت بستن<textarea v-model="closing.closing_note" maxlength="1000" rows="2" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
            <button :disabled="busy" class="rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">بستن صندوق و ثبت مغایرت</button>
          </form></article>
        </section>
        <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">گردش‌های این نوبت کاری</h2><div v-if="!session.transactions?.length" class="py-8 text-center text-sm text-slate-500">هنوز گردش مالی ثبت نشده است.</div><div v-else class="mt-3 space-y-2"><article v-for="item in session.transactions" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border p-3 text-sm"><div><strong>{{item.description}}</strong><p class="mt-1 text-xs text-slate-500">{{item.direction==='in'?'دریافت':'پرداخت'}} · {{item.category}} · {{dateTime(item.created_at)}}</p></div><strong :class="item.direction==='in'?'text-emerald-700':'text-rose-700'">{{item.direction==='in'?'+':'−'}} {{money(item.amount_rial)}}</strong></article></div></section>
      </template>
      <section class="rounded-2xl border bg-white p-5"><h2 class="font-black">نوبت‌های کاری بسته‌شده اخیر</h2><div v-if="!recent_sessions.length" class="mt-3 text-sm text-slate-500">سابقه‌ای ثبت نشده است.</div><div v-else class="mt-3 overflow-x-auto"><table class="w-full min-w-[650px] text-right text-xs"><thead><tr class="border-b text-slate-500"><th class="p-2">شروع</th><th class="p-2">پایان</th><th class="p-2">افتتاحیه</th><th class="p-2">شمارش</th><th class="p-2">مغایرت</th></tr></thead><tbody><tr v-for="s in recent_sessions" :key="s.id" class="border-b"><td class="p-2">{{dateTime(s.opened_at)}}</td><td class="p-2">{{dateTime(s.closed_at)}}</td><td class="p-2">{{money(s.opening_balance_rial)}}</td><td class="p-2">{{money(s.closing_count_rial)}}</td><td class="p-2 font-bold">{{money(s.difference_rial)}}</td></tr></tbody></table></div></section>
    </main>
  </PortalLayout>
</template>

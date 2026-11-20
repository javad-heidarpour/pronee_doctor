<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { Head, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props = defineProps({ doctor:Object, clinics:Array, codes:Object, canManage:Boolean })
const form = useForm({code:'',name:'',clinic_id:'',discount_type:'percent',discount_value:10,minimum_amount_rial:0,maximum_discount_rial:'',usage_limit:'',per_patient_limit:1,starts_at:'',ends_at:''})
const money = v => new Intl.NumberFormat('fa-IR').format(Number(v||0))+' ریال'
const submit = () => form.post(route('portal.marketing.discounts.store'),{preserveScroll:true,onSuccess:()=>form.reset('code','name','discount_value','maximum_discount_rial','usage_limit','starts_at','ends_at')})
const toggle = item => router.post(route('portal.marketing.discounts.toggle',item.id),{}, {preserveScroll:true})
const statusLabel = v => v==='active'?'فعال':'متوقف'
</script>
<template>
  <Head title="کدهای تخفیف" />
  <PortalLayout :doctor="doctor" title="کدهای تخفیف">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-black">مدیریت کدهای تخفیف و اعتبار</h1><p class="mt-1 text-sm text-slate-500">کدها به پزشک و در صورت نیاز به یک شعبه محدود می‌شوند؛ سقف مصرف و تخفیف در لحظه رزرو دوباره کنترل می‌شود.</p></div><Link :href="route('portal.marketing.index')" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت به بازاریابی</Link></header>
      <section v-if="canManage" class="rounded-2xl border bg-white p-5">
        <h2 class="font-black">ساخت کد جدید</h2>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label class="text-xs font-bold">کد تخفیف<input v-model="form.code" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_-]+" class="mt-1 w-full rounded-xl border-slate-300 uppercase" placeholder="WELCOME10"></label>
          <label class="text-xs font-bold">عنوان<input v-model="form.name" required maxlength="160" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شعبه<select v-model="form.clinic_id" class="mt-1 w-full rounded-xl border-slate-300"><option value="">همه شعب مجاز</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{c.name}}</option></select></label>
          <label class="text-xs font-bold">نوع تخفیف<select v-model="form.discount_type" class="mt-1 w-full rounded-xl border-slate-300"><option value="percent">درصدی</option><option value="fixed">مبلغ ثابت</option></select></label>
          <label class="text-xs font-bold">{{form.discount_type==='percent'?'درصد (۱ تا ۱۰۰)':'مبلغ (ریال)'}}<input v-model.number="form.discount_value" type="number" min="1" :max="form.discount_type==='percent'?100:1000000000000" required class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">حداقل مبلغ رزرو (ریال)<input v-model.number="form.minimum_amount_rial" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">سقف تخفیف (ریال)<input v-model="form.maximum_discount_rial" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">سقف کل استفاده<input v-model="form.usage_limit" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-300" placeholder="بدون سقف"></label>
          <label class="text-xs font-bold">سقف استفاده هر بیمار<input v-model.number="form.per_patient_limit" type="number" min="1" max="20" required class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">شروع اعتبار<input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="text-xs font-bold">پایان اعتبار<input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <button :disabled="form.processing" class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50 sm:col-span-2 lg:col-span-3">ایجاد کد تخفیف</button>
        </form>
      </section>
      <section class="space-y-3 rounded-2xl border bg-white p-5"><h2 class="font-black">کدهای تعریف‌شده</h2>
        <p v-if="!codes.data?.length" class="py-8 text-center text-sm text-slate-500">کدی ثبت نشده است.</p>
        <article v-for="item in codes.data" :key="item.id" class="rounded-xl border p-4">
          <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-black">{{item.name}} · <span class="font-mono text-teal-700">{{item.code}}</span></h3><p class="mt-1 text-xs text-slate-500">{{item.clinic?.name || 'همه شعب'}} · {{item.discount_type==='percent'?item.discount_value+'٪':money(item.discount_value)}} · استفاده: {{item.redemptions_count}}{{item.usage_limit?' / '+item.usage_limit:''}}</p></div><div class="flex items-center gap-2"><span class="rounded-lg bg-slate-100 px-3 py-1 text-xs">{{statusLabel(item.status)}}</span><button v-if="canManage" @click="toggle(item)" class="rounded-lg border px-3 py-2 text-xs font-bold">{{item.status==='active'?'توقف':'فعال‌سازی'}}</button></div></div>
          <p class="mt-2 text-[11px] text-slate-400">حداقل خرید: {{money(item.minimum_amount_rial)}} · سقف هر بیمار: {{item.per_patient_limit}} بار · اعتبار: {{item.starts_at||'بدون شروع'}} تا {{item.ends_at||'بدون پایان'}}</p>
        </article>
        <nav v-if="codes.links?.length" class="flex flex-wrap gap-2"><Link v-for="l in codes.links" :key="l.label" :href="l.url||'#'" class="rounded-lg border px-3 py-2 text-xs" :class="l.active?'bg-slate-900 text-white':'bg-white'" v-html="l.label"/></nav>
      </section>
    </main>
  </PortalLayout>
</template>

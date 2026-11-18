<template>
<PortalLayout :doctor="doctor" title="موتور حفظ و بازگشت بیماران">
<div dir="rtl" class="space-y-5">
<section class="rounded-2xl bg-gradient-to-l from-slate-950 via-indigo-950 to-cyan-900 p-5 text-white sm:p-7">
<p class="text-xs font-bold tracking-widest text-cyan-200">PRONEEX PATIENT RETENTION ENGINE</p>
<h2 class="mt-2 text-2xl font-black">شناسایی فرصت‌های پیگیری و بازگشت بیمار</h2>
<p class="mt-2 max-w-3xl text-sm leading-7 text-slate-200">فاصله از آخرین نوبت غیرلغوشده، نوبت آینده و پیگیری‌های باز را کنار هم قرار می‌دهد تا تیم درمان فرصت‌های ارتباط مناسب را بررسی کند.</p>
<p class="mt-3 text-xs text-cyan-100">به‌روزرسانی: {{ formatDate(generatedAt) }}</p></section>
<section class="grid grid-cols-2 gap-3 lg:grid-cols-4"><Metric label="بیماران دارای سابقه" :value="stats.total"/><Metric :label="filters.inactive_days+' روز یا بیشتر بدون مراجعه (صفحه جاری)'" :value="stats.win_back"/><Metric label="پیگیری‌های باز (صفحه جاری)" :value="stats.open_followups"/><Metric label="دارای نوبت آینده (صفحه جاری)" :value="stats.scheduled"/></section>
<form @submit.prevent="apply" class="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-[1fr_190px_190px_auto]">
<input v-model="search" maxlength="120" type="search" placeholder="جستجوی نام یا تلفن" class="rounded-xl border-slate-200 text-sm"/>
<select v-model="segment" class="rounded-xl border-slate-200 text-sm"><option value="all">همه گروه‌ها</option><option value="win_back">عبور از آستانه عدم مراجعه</option><option value="attention">نزدیک به آستانه</option><option value="follow_up">دارای پیگیری باز</option><option value="scheduled">دارای نوبت آینده</option><option value="recent">مراجعه اخیر</option><option value="unknown">سابقه ناکافی</option></select>
<select v-model="inactiveDays" class="rounded-xl border-slate-200 text-sm"><option value="60">آستانه ۶۰ روز</option><option value="90">آستانه ۹۰ روز</option><option value="120">آستانه ۱۲۰ روز</option><option value="180">آستانه ۱۸۰ روز</option></select>
<button class="rounded-xl bg-indigo-700 px-5 py-2 text-sm font-bold text-white">اعمال فیلتر</button></form>
<section class="space-y-3"><article v-for="p in patients" :key="p.id" class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5">
<div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-black text-slate-900">{{p.name}}</h3><p class="mt-1 text-xs text-slate-500">{{p.phone||'شماره تماس ثبت نشده'}} · {{fa(p.appointment_count)}} نوبت معتبر</p></div><span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(p.key)">{{p.label}}</span></div>
<div class="mt-3 grid gap-2 text-xs sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">آخرین نوبت</span><p class="mt-1 font-bold">{{formatDate(p.last_appointment_at)}}</p></div><div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">روزهای سپری‌شده</span><p class="mt-1 font-bold">{{p.days_inactive===null?'نامشخص':fa(p.days_inactive)+' روز'}}</p></div><div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">پیگیری باز</span><p class="mt-1 font-bold">{{fa(p.open_followups)}}</p></div></div>
<p v-if="p.key==='win_back'||p.key==='attention'" class="mt-3 rounded-lg bg-amber-50 p-3 text-xs leading-6 text-amber-900">پیشنهاد: ابتدا پرونده و برنامه درمان را بررسی کنید؛ در صورت مناسب بودن و وجود رضایت معتبر، پیگیری انسانی و غیرتبلیغاتی انجام شود.</p>
<p v-else-if="p.key==='unknown'" class="mt-3 text-xs text-slate-500">تاریخ مراجعه قابل اتکایی برای تعیین فاصله زمانی وجود ندارد.</p>
</article><div v-if="!patients.length" class="rounded-xl border border-dashed p-12 text-center text-sm text-slate-500">بیماری با این فیلتر در این صفحه پیدا نشد.</div></section>
<div class="flex items-center justify-between rounded-xl border bg-white p-3 text-xs text-slate-600"><span>صفحه {{fa(pagination.current_page)}} از {{fa(pagination.last_page)}} · {{fa(pagination.total)}} بیمار</span><div class="flex gap-2"><button :disabled="pagination.current_page<=1" class="rounded-lg border px-3 py-2 disabled:opacity-40" @click="go(pagination.current_page-1)">قبلی</button><button :disabled="pagination.current_page>=pagination.last_page" class="rounded-lg border px-3 py-2 disabled:opacity-40" @click="go(pagination.current_page+1)">بعدی</button></div></div>
<section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-7 text-amber-950"><strong>محدودیت و ایمنی:</strong><ul class="mt-1 list-disc space-y-1 pr-5"><li>این دسته‌بندی پیش‌بینی ریزش، تشخیص پزشکی یا امتیاز احتمال نیست؛ فقط قواعد زمانی قابل توضیح است.</li><li>آستانه عمومی است و باید متناسب با تخصص و برنامه درمان تفسیر شود.</li><li>هیچ پیامک، تماس خودکار یا کمپین تبلیغاتی اجرا نمی‌شود؛ رضایت ارتباطی باید جداگانه بررسی شود.</li><li>وجود نوبت آینده یا پیگیری باز مانع قرار گرفتن بیمار در گروه بازگشت می‌شود.</li></ul></section>
</div></PortalLayout></template>
<script setup>
import {ref,defineComponent,h} from 'vue'
import {router} from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props=defineProps({doctor:{type:Object,required:true},patients:{type:Array,default:()=>[]},stats:{type:Object,default:()=>({total:0,win_back:0,open_followups:0,scheduled:0})},pagination:{type:Object,default:()=>({current_page:1,last_page:1,total:0})},filters:{type:Object,default:()=>({search:'',segment:'all',inactive_days:90})},generatedAt:{type:String,default:null}})
const Metric=defineComponent({props:{label:String,value:[String,Number]},setup(p){return()=>h('div',{class:'rounded-xl border bg-white p-4'},[h('p',{class:'text-xs text-slate-500'},p.label),h('p',{class:'mt-2 text-2xl font-black'},Number(p.value||0).toLocaleString('fa-IR'))])}})
const search=ref(props.filters.search||''),segment=ref(props.filters.segment||'all'),inactiveDays=ref(String(props.filters.inactive_days||90))
const fa=v=>Number(v||0).toLocaleString('fa-IR')
const formatDate=v=>v?new Intl.DateTimeFormat('fa-IR',{dateStyle:'medium'}).format(new Date(v)):'—'
const badgeClass=k=>({win_back:'bg-rose-100 text-rose-800',attention:'bg-amber-100 text-amber-800',follow_up:'bg-cyan-100 text-cyan-800',scheduled:'bg-emerald-100 text-emerald-800',recent:'bg-slate-100 text-slate-700',unknown:'bg-gray-100 text-gray-700'}[k]||'bg-slate-100 text-slate-700')
const query=page=>({search:search.value||undefined,segment:segment.value,inactive_days:inactiveDays.value,page})
const apply=()=>router.get(route('portal.patient-retention.index'),query(1),{preserveState:true,preserveScroll:true})
const go=page=>router.get(route('portal.patient-retention.index'),query(page),{preserveState:true,preserveScroll:true})
</script>
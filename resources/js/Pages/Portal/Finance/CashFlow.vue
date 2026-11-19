<script setup>
import { computed } from 'vue'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({ doctor: Object, forecast: Object })
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const scenarios = computed(() => [
  { key: 'conservative', title: 'محافظه‌کارانه', hint: 'وصول ۶۵٪ مطالبات', data: props.forecast.scenarios.conservative },
  { key: 'realistic', title: 'واقع‌بینانه', hint: 'وصول ۸۵٪ مطالبات', data: props.forecast.scenarios.realistic },
  { key: 'optimistic', title: 'خوش‌بینانه', hint: 'وصول ۱۰۰٪ مطالبات', data: props.forecast.scenarios.optimistic },
])
</script>

<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="space-y-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-black text-slate-900">پیش‌بینی جریان نقدینگی</h1>
          <p class="mt-1 text-sm text-slate-500">مانده واقعی صندوق و بانک به‌همراه جریان‌های نقدی قابل استناد · {{ forecast.as_of }}</p>
        </div>
        <a :href="route('portal.finance.index')" class="rounded-xl border px-4 py-2 text-sm font-bold">بازگشت به مدیریت مالی</a>
      </header>

      <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border bg-white p-4">
          <p class="text-xs text-slate-500">مانده آغاز دوره</p>
          <p class="mt-2 text-lg font-black">{{ money(forecast.starting_balance) }}</p>
        </article>
        <article class="rounded-2xl border bg-white p-4">
          <p class="text-xs text-slate-500">افق پیش‌بینی</p>
          <p class="mt-2 text-lg font-black">{{ forecast.horizon_days }} روز</p>
        </article>
        <article class="rounded-2xl border bg-white p-4">
          <p class="text-xs text-slate-500">سناریوی واقع‌بینانه؛ مانده پایان</p>
          <p class="mt-2 text-lg font-black">{{ money(forecast.scenarios.realistic.ending_balance) }}</p>
        </article>
        <article class="rounded-2xl border bg-white p-4">
          <p class="text-xs text-slate-500">اولین کسری نقدینگی</p>
          <p class="mt-2 text-lg font-black" :class="forecast.scenarios.realistic.first_deficit_date ? 'text-rose-700' : 'text-emerald-700'">
            {{ forecast.scenarios.realistic.first_deficit_date || 'در افق انتخابی پیش‌بینی نشد' }}
          </p>
        </article>
      </section>

      <section class="grid gap-4 lg:grid-cols-3">
        <article v-for="scenario in scenarios" :key="scenario.key" class="rounded-2xl border bg-white p-4">
          <h2 class="font-extrabold">{{ scenario.title }}</h2>
          <p class="mt-1 text-xs text-slate-500">{{ scenario.hint }}</p>
          <p class="mt-4 text-xs text-slate-500">مانده پایان دوره</p>
          <p class="mt-1 text-xl font-black" :class="scenario.data.ending_balance < 0 ? 'text-rose-700' : 'text-slate-900'">{{ money(scenario.data.ending_balance) }}</p>
          <div class="mt-3 space-y-1 text-xs text-slate-600">
            <p>مجموع ورودی: {{ money(scenario.data.total_inflows) }}</p>
            <p>مجموع خروجی: {{ money(scenario.data.total_outflows) }}</p>
            <p>کمترین مانده: {{ money(scenario.data.minimum_balance) }}</p>
            <p>اولین کسری: {{ scenario.data.first_deficit_date || 'ندارد' }}</p>
          </div>
        </article>
      </section>

      <section class="rounded-2xl border bg-white p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-extrabold">جریان روزانه — سناریوی واقع‌بینانه</h2>
          <form method="get" :action="route('portal.finance.cash-flow')" class="flex items-center gap-2 text-sm">
            <label for="days" class="text-xs text-slate-500">افق</label>
            <select id="days" name="days" :value="forecast.horizon_days" class="rounded-lg border-slate-300 text-sm">
              <option value="30">۳۰ روز</option><option value="60">۶۰ روز</option><option value="90">۹۰ روز</option><option value="180">۱۸۰ روز</option>
            </select>
            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white">اعمال</button>
          </form>
        </div>
        <div class="mt-4 space-y-2 sm:hidden">
          <article v-for="day in forecast.scenarios.realistic.days" :key="'mobile-'+day.date" class="rounded-xl border border-slate-200 p-3">
            <div class="flex items-center justify-between gap-2"><b class="text-sm text-slate-800">{{ day.date }}</b><span class="text-xs font-bold" :class="day.closing_balance < 0 ? 'text-rose-700' : 'text-slate-700'">مانده: {{ money(day.closing_balance) }}</span></div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
              <p><span class="text-slate-500">ورودی</span><b class="mt-1 block text-emerald-700">{{ money(day.inflows) }}</b></p>
              <p><span class="text-slate-500">خروجی</span><b class="mt-1 block text-rose-700">{{ money(day.outflows) }}</b></p>
              <p class="col-span-2"><span class="text-slate-500">خالص روز</span><b class="mr-2">{{ money(day.net) }}</b></p>
            </div>
          </article>
        </div>
        <div class="mt-4 hidden overflow-x-auto sm:block">
          <table class="w-full min-w-[620px] text-right text-xs">
            <thead><tr class="border-b text-slate-500"><th class="p-2">تاریخ</th><th class="p-2">ورودی برآوردی</th><th class="p-2">خروجی قطعی/تعهدشده</th><th class="p-2">خالص روز</th><th class="p-2">مانده پایان روز</th></tr></thead>
            <tbody>
              <tr v-for="day in forecast.scenarios.realistic.days" :key="day.date" class="border-b last:border-0">
                <td class="p-2">{{ day.date }}</td><td class="p-2 text-emerald-700">{{ money(day.inflows) }}</td>
                <td class="p-2 text-rose-700">{{ money(day.outflows) }}</td><td class="p-2">{{ money(day.net) }}</td>
                <td class="p-2 font-bold" :class="day.closing_balance < 0 ? 'text-rose-700' : ''">{{ money(day.closing_balance) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <h2 class="font-extrabold text-amber-950">محدودیت‌ها و فرض‌های محاسبه</h2>
        <ul class="mt-2 list-disc space-y-1 pr-5 text-xs leading-6 text-amber-900">
          <li>مانده نقد از گردش حساب ۱۰۰۰ (صندوق و بانک) در اسناد قطعی دفتر کل محاسبه می‌شود؛ جمع بدهکار و بستانکار کل حساب‌ها مبنای مانده نقد نیست.</li>
          <li>ورودی‌ها شامل مانده اقساط بیمار و اقساط سررسیدشده مساعده/وام کارکنان است؛ وصول آن‌ها قطعی فرض نشده و در سه سناریو با ضریب متفاوت برآورد می‌شود.</li>
          <li>خروجی‌ها شامل فیش حقوقی تأییدشده و پرداخت‌نشده و مساعده/وام تأییدشده اما پرداخت‌نشده است.</li>
          <li>هزینه‌های تکرارشونده فاقد سررسید، چک‌های صادرنشده و گردش بانکی برخط در این نسخه منظور نشده‌اند.</li>
          <li>این موتور پیش‌بینی قاعده‌محور و قابل توضیح است؛ ادعای اتصال بانکی یا پیش‌بینی هوش مصنوعی ندارد.</li>
        </ul>
      </section>
    </div>
  </PortalLayout>
</template>

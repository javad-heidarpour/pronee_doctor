<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object, clinics: Array, patients: Array, forecast: Object, programs: Array,
  memberships: Array, reminders: Array, scenarios: Array, permissions: Object, baseline: Object,
})
const horizon = useForm({ horizon: props.forecast?.horizon_days || 30 })
const program = useForm({ clinic_id: '', title: '', category: 'general_checkup', description: '', interval_days: 365 })
const enrollment = useForm({ patient_id: '', program_id: '', consent: false })
const simulation = useForm({
  clinic_id: props.clinics?.[0]?.id || '', scenario_type: 'hire_doctor', name: '',
  extra_appointments_month: 80, average_fee: props.baseline?.average_fee || 0,
  monthly_fixed_cost: 0, one_time_cost: 0, fee_change_percent: 0,
  volume_change_percent: 0, capacity_monthly: 100,
})
const money = value => new Intl.NumberFormat('fa-IR').format(Math.round(Number(value || 0))) + ' ریال'
const riskLabel = risk => ({ out: 'اتمام موجودی', critical: 'بحرانی', warning: 'نیازمند سفارش', healthy: 'مناسب' })[risk] || risk
const confidenceLabel = value => ({ service_mix: 'الگوی تخصص/خدمت', historical_fallback: 'مصرف تاریخی', insufficient_data: 'داده ناکافی' })[value] || value
const updateSettings = item => {
  const lead = window.prompt('زمان تأمین کالا به روز', String(item.lead_time_days))
  if (lead === null) return
  const safety = window.prompt('ذخیره احتیاطی به روز', String(item.safety_stock_days))
  if (safety === null) return
  const supplier = window.prompt('تأمین‌کننده پیشنهادی', item.preferred_supplier || '') ?? ''
  router.post(route('portal.intelligence.inventory.settings', item.id), {
    lead_time_days: Number(lead), safety_stock_days: Number(safety), preferred_supplier: supplier,
  })
}
const award = membership => {
  const activity_key = window.prompt('نوع فعالیت: education_completed / self_care_goal_completed / preventive_checklist_completed', 'education_completed')
  if (!activity_key) return
  const description = window.prompt('شرح فعالیت انجام‌شده', 'تکمیل محتوای آموزشی سلامت')
  if (!description) return
  const source_key = window.prompt('شناسه یکتای رویداد (برای جلوگیری از امتیاز تکراری)', '')
  if (!source_key) return
  router.post(route('portal.intelligence.health.points', membership.id), { activity_key, description, source_key })
}
const saveSimulation = () => simulation.post(route('portal.intelligence.simulate'))
const result = computed(() => props.scenarios?.[0]?.results ? (typeof props.scenarios[0].results === 'string' ? JSON.parse(props.scenarios[0].results) : props.scenarios[0].results) : null)
</script>

<template>
  <PortalLayout :doctor="doctor" title="هوشمندی کلینیک">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 via-teal-950 to-teal-800 p-6 text-white">
        <p class="text-xs font-bold text-teal-200">PRONEEX · CLINIC INTELLIGENCE</p>
        <h1 class="mt-2 text-2xl font-black">مرکز تصمیم‌یار هوشمند کلینیک</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-teal-50/80">پیش‌بینی موجودی، مراقبت پیشگیرانه مبتنی بر رضایت بیمار و ارزیابی سناریوهای اقتصادی پیش از تصمیم مدیریتی.</p>
      </header>

      <section v-if="permissions.inventory" class="space-y-4 rounded-2xl border bg-white p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div><h2 class="text-lg font-black">۱. پیش‌بینی هوشمند مواد مصرفی</h2><p class="mt-1 text-xs text-slate-500">ترکیب نوبت‌های آینده با نسبت واقعی خدمات و مصرف ثبت‌شده در ۹۰ روز گذشته</p></div>
          <form @submit.prevent="router.get(route('portal.intelligence.index'), { horizon: horizon.horizon }, { preserveState: true })" class="flex items-center gap-2">
            <select v-model.number="horizon.horizon" class="rounded-xl border-slate-300 text-sm"><option :value="14">۱۴ روز</option><option :value="30">۳۰ روز</option><option :value="60">۶۰ روز</option><option :value="90">۹۰ روز</option></select>
            <button class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white">محاسبه مجدد</button>
          </form>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">اقلام بررسی‌شده</span><b class="mt-1 block text-xl">{{ forecast.summary?.total || 0 }}</b></div>
          <div class="rounded-xl bg-rose-50 p-3"><span class="text-xs text-rose-600">بحرانی / تمام‌شده</span><b class="mt-1 block text-xl text-rose-700">{{ forecast.summary?.critical || 0 }}</b></div>
          <div class="rounded-xl bg-amber-50 p-3"><span class="text-xs text-amber-700">در آستانه سفارش</span><b class="mt-1 block text-xl text-amber-800">{{ forecast.summary?.warning || 0 }}</b></div>
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">داده ناکافی</span><b class="mt-1 block text-xl">{{ forecast.summary?.insufficient_data || 0 }}</b></div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[850px] text-right text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">ماده / شعبه</th><th class="p-3">موجودی</th><th class="p-3">تقاضای پیش‌بینی</th><th class="p-3">روز تا اتمام</th><th class="p-3">زمان سفارش</th><th class="p-3">مقدار پیشنهادی</th><th class="p-3">وضعیت</th><th class="p-3">تنظیم</th></tr></thead>
            <tbody>
              <tr v-for="item in forecast.items" :key="item.id" class="border-t">
                <td class="p-3"><b>{{ item.name }}</b><p class="text-xs text-slate-500">{{ item.clinic_name }} · {{ item.unit }}</p><p class="mt-1 text-[11px] text-slate-400">{{ confidenceLabel(item.confidence) }}</p></td>
                <td class="p-3 tabular-nums">{{ item.current_quantity }}</td>
                <td class="p-3 tabular-nums">{{ item.forecast_demand }} <span class="text-xs text-slate-400">/ {{ forecast.horizon_days }} روز</span></td>
                <td class="p-3">{{ item.days_remaining === null ? 'نامشخص' : item.days_remaining + ' روز' }}</td>
                <td class="p-3">{{ item.reorder_in_days === null ? 'نیازمند ثبت داده' : item.reorder_in_days === 0 ? 'اکنون' : item.reorder_in_days + ' روز دیگر' }}</td>
                <td class="p-3 font-bold">{{ item.suggested_order_quantity }} {{ item.unit }}<p class="text-xs font-normal text-slate-500">{{ money(item.estimated_order_cost) }}</p></td>
                <td class="p-3"><span class="rounded-full px-2 py-1 text-xs" :class="item.risk === 'out' || item.risk === 'critical' ? 'bg-rose-100 text-rose-700' : item.risk === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'">{{ riskLabel(item.risk) }}</span></td>
                <td class="p-3"><button @click="updateSettings(item)" class="text-xs font-bold text-teal-700">زمان تأمین</button></td>
              </tr>
              <tr v-if="!forecast.items?.length"><td colspan="8" class="p-6 text-center text-slate-500">برای شعبه‌های مجاز، کالای فعالی در انبار ثبت نشده است.</td></tr>
            </tbody>
          </table>
        </div>
        <p class="text-xs leading-6 text-slate-500">پیش‌بینی، برآورد تصمیم‌یار است نه تضمین مصرف. اگر الگوی خدمات یا گردش مصرف ثبت نشده باشد، سطح اطمینان «داده ناکافی» یا «مصرف تاریخی» نمایش داده می‌شود؛ پیش از سفارش، موجودی فیزیکی و سفارش‌های در راه بررسی شود.</p>
      </section>

      <section v-if="permissions.health" class="space-y-4 rounded-2xl border bg-white p-4 sm:p-6">
        <div><h2 class="text-lg font-black">۲. باشگاه هوشمند سلامت</h2><p class="mt-1 text-xs text-slate-500">برنامه‌های پیشگیری و مراقبت دوره‌ای؛ عضویت فقط با رضایت ثبت‌شده بیمار</p></div>
        <form @submit.prevent="program.post(route('portal.intelligence.health.programs.store'))" class="grid gap-3 rounded-xl bg-slate-50 p-3 md:grid-cols-2">
          <input v-model="program.title" required placeholder="عنوان برنامه؛ مثلاً یادآوری چکاپ سالانه" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="program.category" class="rounded-xl border-slate-300 text-sm"><option value="general_checkup">چکاپ عمومی</option><option value="diabetes">پایش دیابت</option><option value="cardiovascular">سلامت قلب و عروق</option><option value="dental">بهداشت دهان و دندان</option><option value="vaccination">یادآوری واکسیناسیون</option><option value="maternal">مراقبت مادران</option><option value="other">سایر</option></select>
          <select v-model="program.clinic_id" class="rounded-xl border-slate-300 text-sm"><option value="">همه شعبه‌های مجاز</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select>
          <input v-model.number="program.interval_days" type="number" min="30" max="1095" class="rounded-xl border-slate-300 text-sm" placeholder="فاصله یادآوری به روز" />
          <textarea v-model="program.description" rows="2" placeholder="شرح و هدف پیشگیرانه" class="rounded-xl border-slate-300 text-sm md:col-span-2" />
          <button class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-2">ایجاد برنامه سلامت</button>
        </form>
        <form @submit.prevent="enrollment.post(route('portal.intelligence.health.enroll'))" class="grid gap-3 rounded-xl border p-3 md:grid-cols-[1fr_1fr_auto]">
          <select v-model="enrollment.patient_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب بیمار</option><option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.name }} · {{ patient.phone }}</option></select>
          <select v-model="enrollment.program_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب برنامه</option><option v-for="item in programs" :key="item.id" :value="item.id">{{ item.title }}</option></select>
          <label class="flex items-center gap-2 text-xs"><input v-model="enrollment.consent" type="checkbox" required /> رضایت آگاهانه بیمار دریافت و ثبت شد</label>
          <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white md:col-span-3">ثبت عضویت داوطلبانه</button>
        </form>
        <div class="grid gap-3 lg:grid-cols-2">
          <article v-for="reminder in reminders" :key="reminder.membership_id" class="rounded-xl border p-3">
            <div class="flex items-start justify-between gap-2"><div><b>{{ reminder.patient_name }}</b><p class="text-xs text-slate-500">{{ reminder.program_title }} · {{ reminder.phone }}</p></div><span class="rounded-full bg-teal-50 px-2 py-1 text-xs text-teal-700">{{ reminder.points }} امتیاز</span></div>
            <p class="mt-2 text-xs" :class="reminder.overdue ? 'text-rose-600' : 'text-slate-500'">موعد مراقبت پیشنهادی: {{ reminder.due_date }} <span v-if="reminder.overdue">· موعد گذشته</span></p>
            <button @click="award(memberships.find(m => m.id === reminder.membership_id))" class="mt-2 text-xs font-bold text-teal-700">ثبت فعالیت آموزشی/پیشگیرانه</button>
          </article>
          <p v-if="!reminders.length" class="text-sm text-slate-500">هنوز عضویت فعالی ثبت نشده است.</p>
        </div>
        <p class="rounded-xl bg-amber-50 p-3 text-xs leading-6 text-amber-900">سیاست اخلاقی باشگاه: امتیاز فقط برای آموزش سلامت، تکمیل چک‌لیست پیشگیری و اهداف خودمراقبتی ثبت می‌شود؛ هیچ امتیازی بابت خرید، تعداد مراجعه، انجام درمان، معرفی بیمار یا ثبت نظر تعلق نمی‌گیرد. امتیازها ابزار تشویق آموزشی‌اند و نباید به تخفیف درمان غیرضروری یا توصیه پزشکی ناموجه تبدیل شوند. ارسال پیام و یادآوری نیز باید با رضایت معتبر بیمار انجام شود.</p>
      </section>

      <section v-if="permissions.simulator" class="space-y-4 rounded-2xl border bg-white p-4 sm:p-6">
        <div><h2 class="text-lg font-black">۳. شبیه‌ساز اقتصادی کلینیک</h2><p class="mt-1 text-xs text-slate-500">اثر مالی، ظرفیت و نقطه سربه‌سر تصمیم را پیش از اجرا بررسی کنید.</p></div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">درآمد ماهانه مبنا</span><b class="mt-1 block text-sm">{{ money(baseline.monthly_revenue) }}</b></div>
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">هزینه ماهانه ثبت‌شده</span><b class="mt-1 block text-sm">{{ money(baseline.monthly_expenses) }}</b></div>
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">نوبت ماهانه</span><b class="mt-1 block text-sm">{{ baseline.monthly_appointments }}</b></div>
          <div class="rounded-xl bg-slate-50 p-3"><span class="text-xs text-slate-500">میانگین تعرفه</span><b class="mt-1 block text-sm">{{ money(baseline.average_fee) }}</b></div>
        </div>
        <form @submit.prevent="saveSimulation" class="grid gap-3 md:grid-cols-2">
          <select v-model="simulation.scenario_type" class="rounded-xl border-slate-300 text-sm"><option value="hire_doctor">استخدام پزشک</option><option value="new_branch">افتتاح شعبه</option><option value="equipment">خرید دستگاه</option><option value="fee_change">تغییر تعرفه</option></select>
          <input v-model="simulation.name" required placeholder="نام سناریو" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="simulation.clinic_id" class="rounded-xl border-slate-300 text-sm"><option value="">کل مجموعه</option><option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option></select>
          <input v-model.number="simulation.average_fee" type="number" min="0" placeholder="میانگین تعرفه هر نوبت (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <label class="text-xs text-slate-500">نوبت افزوده ماهانه<input v-model.number="simulation.extra_appointments_month" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">ظرفیت ماهانه قابل ارائه<input v-model.number="simulation.capacity_monthly" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">هزینه ثابت افزوده ماهانه (ریال)<input v-model.number="simulation.monthly_fixed_cost" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">هزینه اولیه/سرمایه‌گذاری (ریال)<input v-model.number="simulation.one_time_cost" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">درصد تغییر تعرفه<input v-model.number="simulation.fee_change_percent" type="number" min="-50" max="100" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="text-xs text-slate-500">درصد تغییر حجم مراجعه<input v-model.number="simulation.volume_change_percent" type="number" min="-80" max="200" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <button class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-2">محاسبه و ذخیره سناریو</button>
        </form>
        <div v-if="result" class="grid gap-3 rounded-2xl bg-slate-950 p-4 text-white sm:grid-cols-2 lg:grid-cols-4">
          <div><span class="text-xs text-slate-300">اثر خالص ماهانه</span><b class="mt-1 block">{{ money(result.monthly_net_impact) }}</b></div>
          <div><span class="text-xs text-slate-300">اثر خالص سال اول</span><b class="mt-1 block">{{ money(result.year_one_impact) }}</b></div>
          <div><span class="text-xs text-slate-300">نقطه سربه‌سر</span><b class="mt-1 block">{{ result.break_even_months === null ? 'با این فرض‌ها محقق نمی‌شود' : result.break_even_months + ' ماه' }}</b></div>
          <div><span class="text-xs text-slate-300">استفاده از ظرفیت</span><b class="mt-1 block">{{ result.capacity_utilization_percent }}٪</b></div>
          <p class="text-xs leading-6 text-slate-300 sm:col-span-2 lg:col-span-4">{{ result.assumption }}</p>
        </div>
        <div v-if="scenarios.length" class="space-y-2"><h3 class="font-bold">سناریوهای ذخیره‌شده</h3><div v-for="scenario in scenarios" :key="scenario.id" class="flex flex-wrap justify-between gap-2 rounded-xl border p-3 text-sm"><b>{{ scenario.name }}</b><span class="text-slate-500">{{ scenario.scenario_type }} · {{ new Date(scenario.created_at).toLocaleDateString('fa-IR') }}</span></div></div>
      </section>
    </main>
  </PortalLayout>
</template>

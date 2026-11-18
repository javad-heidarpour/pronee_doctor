<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object, clinics: Array, patients: Array, organizations: Array, payrollOrganizations: Array, plans: Array,
  services: Array, inventory: Array, dentalChart: Array, settlements: Array, payrolls: Array,
  lowStockCount: Number, canManage: Boolean, canManageInventory: Boolean, canManageFinance: Boolean, canViewClinical: Boolean, canManageClinical: Boolean, canViewInventory: Boolean, canViewServices: Boolean,
})
const serviceForm = useForm({ clinic_id: props.clinics?.[0]?.id ?? '', name: '', code: '', specialty_module: 'dentistry', default_duration_minutes: 30, default_price: 0, doctor_share_basis_points: 7000, requires_follow_up: false, follow_up_after_days: 30 })
const itemForm = useForm({ plan_id: '', service_id: '', title: '', item_type: 'procedure', tooth_number: '', tooth_surface: '', sequence: 1, price: 0, doctor_share_basis_points: '', follow_up_after_days: '', clinical_notes: '' })
const dentalForm = useForm({ patient_id: '', clinic_id: props.clinics?.[0]?.id ?? '', dentition: 'permanent', tooth_number: '', surface: '', condition: 'caries', severity: 'medium', notes: '' })
const stockForm = useForm({ clinic_id: props.clinics?.[0]?.id ?? '', name: '', sku: '', unit: 'عدد', minimum_quantity: 1, opening_quantity: 0, unit_cost: 0 })
const settlementForm = useForm({ organization_id: props.payrollOrganizations?.[0]?.id ?? '', doctor_id: '', period_start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10), period_end: new Date().toISOString().slice(0,10) })
const selectedPlan = computed(() => props.plans.find(p => String(p.id) === String(itemForm.plan_id)))
const settlementDoctors = computed(() => props.payrollOrganizations.find(o => String(o.id) === String(settlementForm.organization_id))?.doctors || [])
const payrollStaff = computed(() => (props.payrollOrganizations.find(o => String(o.id) === String(payrollForm.organization_id))?.members || []).filter(m => m.pivot?.role !== 'owner'))
const money = value => new Intl.NumberFormat('fa-IR').format(Number(value || 0)) + ' ریال'
const saveService = () => serviceForm.post(route('portal.operations.services.store'), { onSuccess: () => serviceForm.reset('name','code','default_price') })
const saveItem = () => itemForm.transform(data => ({...data, price: Number(data.price), service_id: data.service_id || null, doctor_share_basis_points: data.doctor_share_basis_points === '' ? null : Number(data.doctor_share_basis_points), follow_up_after_days: data.follow_up_after_days === '' ? null : Number(data.follow_up_after_days)})).post(route('portal.operations.treatment-items.store', itemForm.plan_id), { onSuccess: () => itemForm.reset('title','tooth_number','tooth_surface','clinical_notes') })
const updateItem = (item, stage) => router.put(route('portal.operations.treatment-items.update', item.id), { stage, clinical_notes: item.clinical_notes || '' })
const saveDental = () => dentalForm.post(route('portal.operations.dental-chart.store'), { onSuccess: () => dentalForm.reset('tooth_number','surface','notes') })
const saveStock = () => stockForm.post(route('portal.operations.inventory.store'), { onSuccess: () => stockForm.reset('name','sku','opening_quantity','unit_cost') })
const moveStock = item => { const type = window.prompt('نوع گردش: purchase / use / return / adjustment', 'purchase'); if (!type) return; const quantity = window.prompt('مقدار', '1'); if (!quantity) return; const unit_cost = type === 'purchase' ? window.prompt('بهای واحد به ریال', String(item.average_unit_cost || 0)) : null; router.post(route('portal.operations.inventory.movements.store', item.id), { movement_type: type, quantity: Number(quantity), unit_cost: unit_cost === null ? null : Number(unit_cost) }) }
const createSettlement = () => settlementForm.post(route('portal.operations.settlements.store'))
const payrollForm = useForm({ organization_id: props.payrollOrganizations?.[0]?.id ?? '', clinic_id: '', staff_user_id: '', period_start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0,10), period_end: new Date().toISOString().slice(0,10), gross_amount: 0, deduction_amount: 0, notes: '' })
const savePayroll = () => payrollForm.post(route('portal.operations.payrolls.store'))
const statusLabel = s => ({planned:'برنامه‌ریزی‌شده', scheduled:'زمان‌بندی‌شده', in_progress:'در حال انجام', completed:'انجام‌شده', cancelled:'لغوشده'})[s] || s
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-6 p-4">
      <header><h1 class="text-xl font-black text-slate-900">عملیات تخصصی کلینیک</h1><p class="mt-1 text-sm text-slate-500">خدمات، مراحل درمان، پرونده دندان، انبار و تسویه پزشک</p></header>
      <section class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">خدمات فعال</p><b class="mt-2 block text-xl">{{ services.length }}</b></div>
        <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">طرح‌های درمان اخیر</p><b class="mt-2 block text-xl">{{ plans.length }}</b></div>
        <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">اقلام انبار</p><b class="mt-2 block text-xl">{{ inventory.length }}</b></div>
        <div class="rounded-2xl border bg-white p-4"><p class="text-xs text-slate-500">اقلام زیر حداقل موجودی</p><b class="mt-2 block text-xl" :class="lowStockCount ? 'text-rose-600' : 'text-emerald-700'">{{ lowStockCount }}</b></div>
      </section>

      <section v-if="canViewServices" class="rounded-2xl border bg-white p-5">
        <h2 class="mb-4 font-bold">تعریف خدمت و تعرفه</h2>
        <form @submit.prevent="saveService" class="grid gap-3 md:grid-cols-3">
          <select v-model="serviceForm.clinic_id" required class="rounded-xl border-slate-300 text-sm"><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <input v-model="serviceForm.name" required placeholder="نام خدمت؛ مثلاً عصب‌کشی" class="rounded-xl border-slate-300 text-sm" />
          <input v-model="serviceForm.code" placeholder="کد خدمت" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="serviceForm.specialty_module" class="rounded-xl border-slate-300 text-sm"><option value="dentistry">دندانپزشکی</option><option value="general">عمومی</option><option value="dermatology">پوست و زیبایی</option><option value="physiotherapy">فیزیوتراپی</option><option value="surgery">جراحی</option><option value="other">سایر</option></select>
          <input v-model.number="serviceForm.default_duration_minutes" type="number" min="5" max="600" placeholder="مدت (دقیقه)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="serviceForm.default_price" type="number" min="0" placeholder="تعرفه (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <label class="text-xs text-slate-500">سهم پزشک درصد<input :value="serviceForm.doctor_share_basis_points/100" type="number" min="0" max="100" @input="serviceForm.doctor_share_basis_points = Math.round(Number($event.target.value)*100)" class="mt-1 w-full rounded-xl border-slate-300 text-sm" /></label>
          <label class="flex items-center gap-2 text-sm"><input v-model="serviceForm.requires_follow_up" type="checkbox" /> نیازمند پیگیری دوره‌ای</label>
          <input v-model.number="serviceForm.follow_up_after_days" type="number" min="1" max="3650" placeholder="روز تا پیگیری" class="rounded-xl border-slate-300 text-sm" />
          <button :disabled="serviceForm.processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-3">ثبت خدمت</button>
        </form>
        <div class="mt-4 flex flex-wrap gap-2"><span v-for="service in services" :key="service.id" class="rounded-lg bg-slate-50 px-3 py-2 text-xs">{{ service.name }} · {{ money(service.default_price) }} · {{ service.default_duration_minutes }} دقیقه</span></div>
      </section>

      <section v-if="canViewClinical" class="rounded-2xl border bg-white p-5">
        <h2 class="mb-4 font-bold">مراحل درمان و پیگیری خودکار</h2>
        <form v-if="canManageClinical" @submit.prevent="saveItem" class="grid gap-3 md:grid-cols-3">
          <select v-model="itemForm.plan_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب طرح درمان</option><option v-for="p in plans" :key="p.id" :value="p.id">{{ p.patient?.name }} — {{ p.title }}</option></select>
          <select v-model="itemForm.service_id" class="rounded-xl border-slate-300 text-sm"><option value="">خدمت آزاد</option><option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option></select>
          <input v-model="itemForm.title" required placeholder="عنوان مرحله درمان" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="itemForm.item_type" class="rounded-xl border-slate-300 text-sm"><option value="procedure">اقدام درمانی</option><option value="consultation">مشاوره</option><option value="lab">آزمایشگاه</option><option value="imaging">تصویربرداری</option><option value="medication">دارو</option><option value="other">سایر</option></select>
          <input v-model="itemForm.tooth_number" placeholder="شماره دندان (FDI؛ مثلاً 36)" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="itemForm.tooth_surface" class="rounded-xl border-slate-300 text-sm"><option value="">سطح دندان</option><option v-for="x in ['M','D','O','I','B','L','F','P']" :key="x" :value="x">{{ x }}</option></select>
          <input v-model.number="itemForm.sequence" type="number" min="1" placeholder="ترتیب مرحله" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="itemForm.price" type="number" min="0" placeholder="هزینه مرحله (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="itemForm.follow_up_after_days" type="number" min="1" placeholder="روز تا پیگیری" class="rounded-xl border-slate-300 text-sm" />
          <textarea v-model="itemForm.clinical_notes" rows="2" placeholder="شرح بالینی" class="rounded-xl border-slate-300 text-sm md:col-span-3" />
          <button :disabled="itemForm.processing || !itemForm.plan_id" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white md:col-span-3">افزودن مرحله درمان</button>
        </form>
        <div class="mt-5 space-y-3">
          <article v-for="plan in plans" :key="plan.id" class="rounded-xl border p-3">
            <div class="font-bold">{{ plan.patient?.name }} — {{ plan.title }}</div>
            <div v-for="item in plan.items" :key="item.id" class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-3 text-sm">
              <div><b>{{ item.title }}</b><p class="text-xs text-slate-500">{{ item.tooth_number ? 'دندان '+item.tooth_number : '' }} · {{ money(item.price) }} · {{ statusLabel(item.stage) }}</p><p v-if="item.follow_up_due_at" class="text-xs text-teal-700">پیگیری: {{ item.follow_up_due_at }}</p></div>
              <select v-if="canManageClinical && !['completed','cancelled'].includes(item.stage)" :value="item.stage" @change="updateItem(item,$event.target.value)" class="rounded-lg border-slate-300 text-xs"><option value="planned">برنامه‌ریزی‌شده</option><option value="scheduled">زمان‌بندی‌شده</option><option value="in_progress">در حال انجام</option><option value="completed">انجام شد</option><option value="cancelled">لغو</option></select>
            </div>
          </article>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <div v-if="canViewClinical" class="rounded-2xl border bg-white p-5">
          <h2 class="mb-4 font-bold">ثبت وضعیت نمودار دندان</h2>
          <form v-if="canManageClinical" @submit.prevent="saveDental" class="grid gap-3 sm:grid-cols-2">
            <select v-model="dentalForm.patient_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب بیمار</option><option v-for="p in patients" :key="p.id" :value="p.id">{{ p.name }} — {{ p.phone }}</option></select>
            <select v-model="dentalForm.clinic_id" required class="rounded-xl border-slate-300 text-sm"><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <select v-model="dentalForm.dentition" class="rounded-xl border-slate-300 text-sm"><option value="permanent">دندان دائمی</option><option value="primary">دندان شیری</option></select>
            <input v-model="dentalForm.tooth_number" required placeholder="شماره دندان FDI" class="rounded-xl border-slate-300 text-sm" />
            <select v-model="dentalForm.surface" class="rounded-xl border-slate-300 text-sm"><option value="">کل دندان</option><option v-for="x in ['M','D','O','I','B','L','F','P']" :key="x" :value="x">{{ x }}</option></select>
            <select v-model="dentalForm.condition" class="rounded-xl border-slate-300 text-sm"><option value="caries">پوسیدگی</option><option value="filled">ترمیم‌شده</option><option value="root_canal">عصب‌کشی</option><option value="crown">روکش</option><option value="missing">از دست‌رفته</option><option value="implant">ایمپلنت</option><option value="fracture">شکستگی</option><option value="healthy">سالم</option><option value="watch">نیازمند پایش</option></select>
            <textarea v-model="dentalForm.notes" rows="2" placeholder="توضیحات" class="rounded-xl border-slate-300 text-sm sm:col-span-2" />
            <button class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white sm:col-span-2">ثبت در نمودار دندان</button>
          </form>
          <div class="mt-4 max-h-64 space-y-2 overflow-auto"><div v-for="entry in dentalChart" :key="entry.id" class="rounded-lg bg-slate-50 p-2 text-xs">{{ entry.patient?.name }} · دندان {{ entry.tooth_number }} · {{ entry.condition }} · {{ entry.recorded_at }}</div></div>
        </div>
        <div v-if="canViewInventory" class="rounded-2xl border bg-white p-5">
          <h2 class="mb-4 font-bold">انبار و مواد مصرفی</h2>
          <form v-if="canManageInventory" @submit.prevent="saveStock" class="grid gap-3 sm:grid-cols-2">
            <select v-model="stockForm.clinic_id" class="rounded-xl border-slate-300 text-sm"><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            <input v-model="stockForm.name" required placeholder="نام کالا / ماده" class="rounded-xl border-slate-300 text-sm" />
            <input v-model="stockForm.sku" placeholder="کد کالا" class="rounded-xl border-slate-300 text-sm" />
            <input v-model="stockForm.unit" required placeholder="واحد (عدد، بسته، گرم)" class="rounded-xl border-slate-300 text-sm" />
            <input v-model.number="stockForm.opening_quantity" type="number" min="0" step="0.001" placeholder="موجودی اولیه" class="rounded-xl border-slate-300 text-sm" />
            <input v-model.number="stockForm.minimum_quantity" type="number" min="0" step="0.001" placeholder="حداقل موجودی" class="rounded-xl border-slate-300 text-sm" />
            <input v-model.number="stockForm.unit_cost" type="number" min="0" placeholder="بهای واحد (ریال)" class="rounded-xl border-slate-300 text-sm" />
            <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">ثبت کالا</button>
          </form>
          <div class="mt-4 space-y-2"><div v-for="item in inventory" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"><div><b>{{ item.name }}</b><p class="text-xs text-slate-500">{{ item.clinic?.name }} · حداقل {{ item.minimum_quantity }} {{ item.unit }} · بهای واحد {{ money(item.average_unit_cost) }}</p><p class="text-xs" :class="Number(item.current_quantity)<=Number(item.minimum_quantity)?'text-rose-600':'text-emerald-700'">موجودی: {{ item.current_quantity }} {{ item.unit }}</p></div><button v-if="canManageInventory" @click="moveStock(item)" class="rounded-lg border px-3 py-2 text-xs">ثبت ورود / مصرف</button></div></div>
        </div>
      </section>

      <section v-if="canManageFinance" class="rounded-2xl border bg-white p-5">
        <h2 class="mb-3 font-bold">تسویه دوره‌ای پزشک</h2>
        <form @submit.prevent="createSettlement" class="grid gap-3 md:grid-cols-3">
          <select v-model="settlementForm.organization_id" required class="rounded-xl border-slate-300 text-sm"><option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.name }}</option></select>
          <select v-model="settlementForm.doctor_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب پزشک</option><option v-for="d in settlementDoctors" :key="d.id" :value="d.id">{{ d.name }} {{ d.family }}</option></select>
          <input v-model="settlementForm.period_start" type="date" required class="rounded-xl border-slate-300 text-sm" />
          <input v-model="settlementForm.period_end" type="date" required class="rounded-xl border-slate-300 text-sm" />
          <button :disabled="settlementForm.processing || !payrollOrganizations.length" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white md:col-span-3">محاسبه پیش‌نویس تسویه از خدمات انجام‌شده</button>
        </form>
        <h2 class="mb-3 mt-8 font-bold">حقوق و دستمزد کارکنان</h2>
        <form @submit.prevent="savePayroll" class="grid gap-3 md:grid-cols-3">
          <select v-model="payrollForm.organization_id" required class="rounded-xl border-slate-300 text-sm"><option v-for="o in payrollOrganizations" :key="o.id" :value="o.id">{{ o.name }}</option></select>
          <select v-model="payrollForm.staff_user_id" required class="rounded-xl border-slate-300 text-sm"><option value="">انتخاب کارمند</option><option v-for="m in payrollStaff" :key="m.id" :value="m.id">{{ m.name }} {{ m.family }}</option></select>
          <select v-model="payrollForm.clinic_id" class="rounded-xl border-slate-300 text-sm"><option value="">کل مجموعه</option><option v-for="c in clinics" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <input v-model="payrollForm.period_start" type="date" required class="rounded-xl border-slate-300 text-sm" />
          <input v-model="payrollForm.period_end" type="date" required class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="payrollForm.gross_amount" type="number" min="0" required placeholder="حقوق ناخالص (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model.number="payrollForm.deduction_amount" type="number" min="0" placeholder="کسورات (ریال)" class="rounded-xl border-slate-300 text-sm" />
          <input v-model="payrollForm.notes" placeholder="توضیحات" class="rounded-xl border-slate-300 text-sm" />
          <button class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">ثبت پیش‌نویس حقوق</button>
        </form>
        <div class="mt-4 space-y-2"><div v-for="p in payrolls" :key="p.id" class="flex flex-wrap justify-between gap-2 rounded-lg border p-3 text-sm"><div><b>{{ p.staff?.name }} {{ p.staff?.family }}</b><p class="text-xs text-slate-500">{{ p.period_start }} تا {{ p.period_end }} · خالص {{ money(p.net_amount) }} · {{ p.status }}</p></div><div class="flex gap-2"><button v-if="p.status==='draft'" @click="router.post(route('portal.operations.payrolls.approve',p.id))" class="rounded-lg border px-2 py-1 text-xs">تأیید</button><button v-if="p.status==='approved'" @click="router.post(route('portal.operations.payrolls.pay',p.id))" class="rounded-lg bg-slate-900 px-2 py-1 text-xs text-white">پرداخت</button></div></div></div>
        <div class="mt-4 space-y-2"><div v-for="s in settlements" :key="s.id" class="flex flex-wrap justify-between gap-2 rounded-lg border p-3 text-sm"><div><b>{{ s.period_start }} تا {{ s.period_end }}</b><p class="text-xs text-slate-500">ناخالص {{ money(s.gross_amount) }} · سهم کلینیک {{ money(s.clinic_share_amount) }} · سهم پزشک {{ money(s.net_amount) }} · {{ s.status }}</p></div><div class="flex gap-2"><button v-if="s.status==='draft'" @click="router.post(route('portal.operations.settlements.approve',s.id))" class="rounded-lg border px-2 py-1 text-xs">تأیید</button><button v-if="s.status==='approved'" @click="router.post(route('portal.operations.settlements.pay',s.id))" class="rounded-lg bg-slate-900 px-2 py-1 text-xs text-white">ثبت پرداخت</button></div></div></div>
      </section>
    </main>
  </PortalLayout>
</template>

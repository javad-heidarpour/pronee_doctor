<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="space-y-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-black text-slate-900">موتور پیگیری بیماران</h1>
          <p class="mt-1 text-sm text-slate-500">برنامه‌ریزی تماس، آزمایش، بررسی نتیجه و مراجعات بعدی</p>
        </div>
        <span class="rounded-xl bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800">پیگیری مبتنی بر قواعد و زمان‌بندی</span>
      </header>

      <section class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <div v-for="item in statItems" :key="item.label" class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs text-slate-500">{{ item.label }}</p>
          <p class="mt-2 text-2xl font-black text-slate-900">{{ item.value }}</p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 text-base font-extrabold">ثبت پیگیری جدید</h2>
        <form @submit.prevent="createTask" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <select v-model="form.patient_id" required class="rounded-xl border-slate-300 text-sm">
            <option value="">انتخاب بیمار</option>
            <option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.name }} — {{ patient.phone || 'بدون تلفن' }}</option>
          </select>
          <select v-model="form.template_id" @change="applyTemplate" class="rounded-xl border-slate-300 text-sm">
            <option value="">بدون الگو (عنوان دستی)</option>
            <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.title }} ({{ template.default_days }} روز)</option>
          </select>
          <div class="space-y-2 md:col-span-2 xl:col-span-4">
            <div v-for="(step, index) in form.steps" :key="index" class="grid gap-2 rounded-xl bg-slate-50 p-3 md:grid-cols-4">
              <input v-model="step.title" required placeholder="عنوان گام؛ مثلاً تماس با بیمار" class="rounded-lg border-slate-300 text-sm" />
              <input v-model.number="step.days" type="number" min="0" max="365" required placeholder="چند روز پس از تاریخ مبنا؟" class="rounded-lg border-slate-300 text-sm" />
              <select v-model="step.contact_method" class="rounded-lg border-slate-300 text-sm"><option value="call">تماس تلفنی</option><option value="sms">پیامک</option><option value="in_person">مراجعه حضوری</option><option value="review">بررسی پرونده</option></select>
              <div class="flex flex-wrap items-center justify-between gap-2"><div class="flex flex-col gap-1"><label class="flex items-center gap-2 text-xs text-slate-600"><input v-model="step.send_sms" type="checkbox" class="rounded border-slate-300" /> ارسال پیامک</label><label class="flex items-center gap-2 text-xs text-slate-600"><input v-model="step.sms_consent_confirmed" type="checkbox" class="rounded border-slate-300" /> رضایت بیمار ثبت شده</label></div><button v-if="form.steps.length > 1" type="button" @click="removeStep(index)" class="text-xs font-bold text-rose-600">حذف گام</button></div>
            </div>
            <button type="button" @click="addStep" class="rounded-lg border border-dashed border-teal-400 px-3 py-2 text-xs font-bold text-teal-700">افزودن گام به برنامه +</button>
          </div>
          <label class="flex flex-col gap-1 text-xs text-slate-500">تاریخ مبنای برنامه<input v-model="form.due_at" type="datetime-local" required class="rounded-xl border-slate-300 text-sm" /></label>
          <select v-model="form.assigned_to" class="rounded-xl border-slate-300 text-sm">
            <option value="">بدون مسئول مشخص</option>
            <option v-for="secretary in secretaries" :key="secretary.id" :value="secretary.id">{{ secretary.name }} {{ secretary.family || '' }}</option>
          </select>
          <select v-model="form.priority" class="rounded-xl border-slate-300 text-sm">
            <option value="normal">اولویت عادی</option><option value="low">کم</option><option value="high">زیاد</option><option value="urgent">فوری</option>
          </select>
          <select v-model="form.contact_method" class="rounded-xl border-slate-300 text-sm">
            <option value="call">تماس تلفنی</option><option value="sms">پیامک</option><option value="in_person">مراجعه حضوری</option><option value="review">بررسی پرونده</option>
          </select>
          <p class="text-xs text-slate-500">موعد هر گام بر اساس تعداد روزهای واردشده از تاریخ مبنا محاسبه می‌شود. پیامک فقط با انتخاب ارسال و تأیید ثبت رضایت بیمار برای همان گام ارسال می‌شود.</p>
          <button :disabled="processing" class="rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white disabled:opacity-50">ثبت برنامه پیگیری</button>
        </form>
      </section>

      <section v-if="isDoctor" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="mb-4 text-base font-extrabold">{{ editingTemplateId ? 'ویرایش الگوی پیگیری' : 'ساخت الگوی اختصاصی پزشک' }}</h2>
        <form @submit.prevent="createTemplate" class="grid gap-3 md:grid-cols-3">
          <input v-model="templateForm.title" required placeholder="عنوان الگو؛ مثلاً کنترل پس از عمل" class="rounded-xl border-slate-300 text-sm" />
          <select v-model="templateForm.category" class="rounded-xl border-slate-300 text-sm">
            <option value="general">ویزیت عمومی</option><option value="lab">آزمایش</option><option value="chronic">بیماری مزمن</option><option value="surgery">جراحی</option><option value="post_op">مراقبت پس از عمل</option><option value="cosmetic">زیبایی</option>
          </select>
          <input v-model.number="templateForm.default_days" type="number" min="0" max="365" class="rounded-xl border-slate-300 text-sm" placeholder="فاصله پیش‌فرض (روز)" />
          <input v-model="templateForm.description" placeholder="توضیح برای مسئول پیگیری" class="rounded-xl border-slate-300 text-sm md:col-span-2" />
          <select v-model="templateForm.contact_method" class="rounded-xl border-slate-300 text-sm"><option value="call">تماس</option><option value="sms">پیامک</option><option value="review">بررسی پرونده</option><option value="in_person">مراجعه حضوری</option></select>
          <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="templateForm.send_sms" type="checkbox" class="rounded border-slate-300" /> پیشنهاد پیامک برای این الگو (نیازمند رضایت ثبت‌شده بیمار)</label>
          <button class="rounded-xl bg-slate-800 px-4 py-3 text-sm font-bold text-white">{{ editingTemplateId ? 'ذخیره تغییرات' : 'ذخیره الگو' }}</button>
          <button v-if="editingTemplateId" type="button" @click="cancelTemplateEdit" class="rounded-xl border px-4 py-3 text-sm font-bold">انصراف</button>
        </form>
        <div v-if="templates.length" class="mt-4 flex flex-wrap gap-2 border-t pt-3">
          <span v-for="template in templates" :key="template.id" class="inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
            {{ template.title }}
            <button type="button" @click="editTemplate(template)" class="font-bold text-teal-700">ویرایش</button>
            <button type="button" @click="disableTemplate(template.id)" class="font-bold text-rose-600">غیرفعال‌سازی</button>
          </span>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-base font-extrabold">فهرست پیگیری‌ها</h2>
          <div class="flex flex-wrap gap-2">
            <input v-model="filters.search" @keyup.enter="applyFilters" placeholder="جست‌وجوی بیمار" class="w-40 rounded-xl border-slate-300 text-sm" />
            <select v-model="filters.scope" @change="applyFilters" class="rounded-xl border-slate-300 text-sm"><option value="all">همه</option><option value="overdue">معوق</option><option value="today">امروز</option><option value="mine">ارجاع‌شده به من</option></select>
            <select v-model="filters.status" @change="applyFilters" class="rounded-xl border-slate-300 text-sm"><option value="">همه وضعیت‌ها</option><option value="pending">در انتظار</option><option value="completed">انجام‌شده</option><option value="unsuccessful">ناموفق</option><option value="cancelled">لغوشده</option></select>
            <button @click="applyFilters" class="rounded-xl border px-3 py-2 text-sm font-bold">جست‌وجو</button>
          </div>
        </div>

        <div v-if="tasks.data.length" class="space-y-3">
          <article v-for="task in tasks.data" :key="task.id" class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h3 class="font-extrabold text-slate-900">{{ task.title }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ task.patient?.name }} · {{ task.patient?.phone || 'بدون شماره تماس' }}</p>
                <p class="mt-1 text-xs text-slate-500">موعد: {{ formatDate(task.due_at) }} · مطب: {{ task.clinic?.name || 'ثبت نشده' }} · مسئول: {{ task.assignee ? (task.assignee.name + ' ' + (task.assignee.family || '')) : 'تعیین نشده' }}</p>
                <p v-if="task.description" class="mt-2 text-sm text-slate-500">{{ task.description }}</p>
                <p class="mt-1 text-[11px] text-slate-400">کد برنامه: {{ task.plan_uuid?.slice(0, 8) }} · {{ task.send_sms ? (task.sms_sent_at ? 'پیامک ارسال شده' : (task.last_sms_error ? 'خطای ارسال پیامک' : 'پیامک در صف بررسی')) : 'بدون پیامک' }}</p>
                <p v-if="task.result_notes" class="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-slate-600">نتیجه: {{ task.result_notes }}</p>
              </div>
              <span :class="statusClass(task)" class="rounded-lg px-3 py-2 text-xs font-bold">{{ statusLabel(task) }}</span>
            </div>
            <form v-if="task.status === 'pending'" @submit.prevent="saveResult(task)" class="mt-4 grid gap-2 border-t pt-3 md:grid-cols-4">
              <select v-model="task._status" class="rounded-lg border-slate-300 text-sm"><option value="completed">انجام شد</option><option value="unsuccessful">تماس ناموفق</option><option value="pending">در انتظار</option><option value="cancelled">لغو</option></select>
              <select v-model="task._outcome" class="rounded-lg border-slate-300 text-sm"><option value="">نتیجه تماس</option><option value="reached">ارتباط برقرار شد</option><option value="no_answer">پاسخ نداد</option><option value="rescheduled">زمان جدید تعیین شد</option><option value="referred">ارجاع شد</option><option value="needs_visit">نیازمند مراجعه</option><option value="other">سایر</option></select>
              <input v-model="task._notes" placeholder="شرح نتیجه یا اقدام بعدی" class="rounded-lg border-slate-300 text-sm" />
              <select v-model="task.assigned_to" class="rounded-lg border-slate-300 text-sm"><option value="">بدون مسئول مشخص</option><option v-for="secretary in secretaries" :key="secretary.id" :value="secretary.id">{{ secretary.name }} {{ secretary.family || '' }}</option></select>
              <input v-model="task._due_at" type="datetime-local" class="rounded-lg border-slate-300 text-sm" aria-label="زمان جدید پیگیری" />
              <button class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-bold text-white">ثبت نتیجه</button>
            </form>
          </article>
        </div>
        <p v-else class="py-10 text-center text-sm text-slate-500">پیگیری‌ای با این شرایط یافت نشد.</p>
        <div class="mt-4 flex flex-wrap justify-between gap-2 text-xs text-slate-500">
          <span>صفحه {{ tasks.current_page }} از {{ tasks.last_page }}</span>
          <div class="flex gap-2"><Link v-if="tasks.prev_page_url" :href="tasks.prev_page_url" class="rounded-lg border px-3 py-2">قبلی</Link><Link v-if="tasks.next_page_url" :href="tasks.next_page_url" class="rounded-lg border px-3 py-2">بعدی</Link></div>
        </div>
      </section>
    </div>
  </PortalLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object,
  userRole: String,
  selectedPatientId: Number,
  selectedAppointmentId: Number,
  tasks: Object,
  patients: Array,
  templates: Array,
  secretaries: Array,
  filters: Object,
  stats: Object,
})
const processing = ref(false)
const isDoctor = computed(() => ['doctor', 'admin'].includes(props.userRole))
const form = reactive({ patient_id: props.selectedPatientId || '', appointment_id: props.selectedAppointmentId || null, template_id: '', due_at: '', assigned_to: '', priority: 'normal', contact_method: 'call', steps: [{ title: 'تماس اولیه با بیمار', days: 3, contact_method: 'call', send_sms: false, sms_consent_confirmed: false }] })
const templateForm = reactive({ title: '', category: 'general', description: '', default_days: 3, contact_method: 'call', send_sms: false })
const editingTemplateId = ref(null)
const filters = reactive({ ...props.filters })
const statItems = computed(() => [
  { label: 'در انتظار', value: props.stats.pending },
  { label: 'معوق', value: props.stats.overdue },
  { label: 'موعد امروز', value: props.stats.today },
  { label: 'انجام‌شده', value: props.stats.completed },
  { label: 'ناموفق', value: props.stats.unsuccessful },
])
const addStep = () => form.steps.push({ title: '', days: 7, contact_method: 'call', send_sms: false, sms_consent_confirmed: false })
const removeStep = (index) => form.steps.splice(index, 1)
const createTask = () => {
  processing.value = true
  router.post(route('portal.follow-ups.store'), form, { preserveScroll: true, onFinish: () => { processing.value = false } })
}
const createTemplate = () => {
  const options = { preserveScroll: true, onSuccess: () => { Object.assign(templateForm, { title: '', category: 'general', description: '', default_days: 3, contact_method: 'call', send_sms: false }); editingTemplateId.value = null } }
  if (editingTemplateId.value) router.put(route('portal.follow-ups.templates.update', editingTemplateId.value), templateForm, options)
  else router.post(route('portal.follow-ups.templates.store'), templateForm, options)
}
const editTemplate = (template) => {
  editingTemplateId.value = template.id
  Object.assign(templateForm, { title: template.title, category: template.category, description: template.description || '', default_days: template.default_days, contact_method: template.contact_method, send_sms: template.send_sms })
}
const cancelTemplateEdit = () => { editingTemplateId.value = null; Object.assign(templateForm, { title: '', category: 'general', description: '', default_days: 3, contact_method: 'call', send_sms: false }) }
const applyTemplate = () => {
  const selected = props.templates.find((item) => String(item.id) === String(form.template_id))
  if (!selected || !form.steps.length) return
  Object.assign(form.steps[0], { title: selected.title, days: selected.default_days, contact_method: selected.contact_method, send_sms: selected.send_sms, sms_consent_confirmed: false })
}
const disableTemplate = (id) => router.delete(route('portal.follow-ups.templates.destroy', id), { preserveScroll: true })
const saveResult = (task) => router.put(route('portal.follow-ups.update', task.id), {
  status: task._status || 'completed',
  outcome: task._outcome || null,
  result_notes: task._notes || null,
  assigned_to: task.assigned_to || null,
  ...(task._due_at ? { due_at: task._due_at } : {}),
}, { preserveScroll: true })
const applyFilters = () => router.get(route('portal.follow-ups.index'), filters, { preserveState: true, replace: true })
const formatDate = (value) => new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
const statusLabel = (task) => task.status === 'completed' ? 'انجام‌شده' : task.status === 'unsuccessful' ? 'ناموفق' : task.status === 'cancelled' ? 'لغوشده' : (new Date(task.due_at) < new Date() ? 'معوق' : 'در انتظار')
const statusClass = (task) => task.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : task.status === 'unsuccessful' ? 'bg-rose-50 text-rose-700' : task.status === 'cancelled' ? 'bg-slate-100 text-slate-600' : (new Date(task.due_at) < new Date() ? 'bg-amber-100 text-amber-800' : 'bg-sky-50 text-sky-700')
</script>
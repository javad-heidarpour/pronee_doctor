<script setup>
import { computed, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object,
  organizations: Array,
  clinics: Array,
  members: Array,
  responsibilities: Array,
  currentUserId: Number,
})

const form = useForm({
  id: null,
  organization_id: props.organizations?.find(item => item.can_manage)?.organization_id ?? '',
  clinic_id: '',
  staff_user_id: '',
  title: '',
  description: '',
  priority: 'normal',
  status: 'active',
  effective_from: new Date().toISOString().slice(0, 10),
  effective_until: '',
  review_at: '',
})
const editing = computed(() => Boolean(form.id))
const availableClinics = computed(() => props.clinics.filter(item => String(item.organization_id) === String(form.organization_id)))
const availableMembers = computed(() => props.members.filter(item =>
  String(item.organization_id) === String(form.organization_id)
  && item.clinic_ids?.map(String).includes(String(form.clinic_id))
))
const statusLabel = value => ({ active: 'فعال', paused: 'متوقف', completed: 'پایان‌یافته' })[value] || value
const priorityLabel = value => ({ normal: 'عادی', important: 'مهم', critical: 'فوری' })[value] || value

function resetForm() {
  form.reset()
  form.id = null
  form.organization_id = props.organizations?.find(item => item.can_manage)?.organization_id ?? ''
  form.clinic_id = ''
  form.staff_user_id = ''
  form.effective_from = new Date().toISOString().slice(0, 10)
  form.clearErrors()
}

function edit(item) {
  Object.assign(form, {
    id: item.id,
    organization_id: item.organization_id,
    clinic_id: item.clinic_id,
    staff_user_id: item.staff_user_id,
    title: item.title,
    description: item.description,
    priority: item.priority,
    status: item.status,
    effective_from: item.effective_from,
    effective_until: item.effective_until || '',
    review_at: item.review_at || '',
  })
  form.clearErrors()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function submit() {
  const payload = {
    organization_id: form.organization_id,
    clinic_id: form.clinic_id,
    staff_user_id: form.staff_user_id,
    title: form.title,
    description: form.description,
    priority: form.priority,
    status: form.status,
    effective_from: form.effective_from,
    effective_until: form.effective_until || null,
    review_at: form.review_at || null,
  }
  if (editing.value) {
    form.transform(() => payload).put(route('portal.staff-responsibilities.update', form.id), { preserveScroll: true, onSuccess: resetForm })
  } else {
    form.transform(() => payload).post(route('portal.staff-responsibilities.store'), { preserveScroll: true, onSuccess: resetForm })
  }
}

function archive(item) {
  if (!window.confirm('این مسئولیت بایگانی شود؟ سابقه آن برای گزارش و ممیزی حفظ می‌شود.')) return
  router.delete(route('portal.staff-responsibilities.destroy', item.id), { preserveScroll: true })
}
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 to-indigo-800 p-6 text-white">
        <p class="text-xs font-bold text-indigo-200">PRONEEX · HUMAN RESOURCES</p>
        <h1 class="mt-2 text-2xl font-black">شرح وظایف و مسئولیت‌های کارکنان</h1>
        <p class="mt-2 text-sm text-white/80">تعریف مسئولیت هر کارمند در شعبه مشخص، تعیین اولویت و بازه اعتبار و نگهداری تاریخچه تغییرات.</p>
      </header>

      <section v-if="organizations.some(item => item.can_manage)" class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <h2 class="font-black">{{ editing ? 'ویرایش مسئولیت' : 'تعریف مسئولیت جدید' }}</h2>
          <button v-if="editing" type="button" @click="resetForm" class="text-sm font-bold text-slate-500">انصراف از ویرایش</button>
        </div>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 md:grid-cols-2">
          <label class="text-sm font-semibold">مجموعه
            <select v-model="form.organization_id" required class="mt-1 w-full rounded-xl border-slate-300">
              <option value="">انتخاب مجموعه</option>
              <option v-for="org in organizations.filter(item => item.can_manage)" :key="org.organization_id" :value="org.organization_id">{{ org.name }}</option>
            </select>
            <small v-if="form.errors.organization_id" class="text-rose-600">{{ form.errors.organization_id }}</small>
          </label>
          <label class="text-sm font-semibold">شعبه
            <select v-model="form.clinic_id" required class="mt-1 w-full rounded-xl border-slate-300">
              <option value="">انتخاب شعبه</option>
              <option v-for="clinic in availableClinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
            </select>
            <small v-if="form.errors.clinic_id" class="text-rose-600">{{ form.errors.clinic_id }}</small>
          </label>
          <label class="text-sm font-semibold">کارمند
            <select v-model="form.staff_user_id" required class="mt-1 w-full rounded-xl border-slate-300">
              <option value="">انتخاب کارمند</option>
              <option v-for="member in availableMembers" :key="member.user_id" :value="member.user_id">{{ member.name }} {{ member.family || '' }}</option>
            </select>
            <small v-if="form.errors.staff_user_id" class="text-rose-600">{{ form.errors.staff_user_id }}</small>
          </label>
          <label class="text-sm font-semibold">عنوان مسئولیت
            <input v-model="form.title" required maxlength="160" minlength="3" class="mt-1 w-full rounded-xl border-slate-300" placeholder="مثلاً پاسخ‌گویی و پذیرش بیماران">
            <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
          </label>
          <label class="text-sm font-semibold md:col-span-2">شرح وظایف و خروجی مورد انتظار
            <textarea v-model="form.description" required minlength="5" maxlength="5000" rows="4" class="mt-1 w-full rounded-xl border-slate-300" placeholder="شرح دقیق وظایف، حدود مسئولیت و معیار انجام صحیح کار..."></textarea>
            <small v-if="form.errors.description" class="text-rose-600">{{ form.errors.description }}</small>
          </label>
          <label class="text-sm font-semibold">اولویت
            <select v-model="form.priority" class="mt-1 w-full rounded-xl border-slate-300">
              <option value="normal">عادی</option><option value="important">مهم</option><option value="critical">فوری</option>
            </select>
          </label>
          <label class="text-sm font-semibold">وضعیت
            <select v-model="form.status" class="mt-1 w-full rounded-xl border-slate-300">
              <option value="active">فعال</option><option value="paused">متوقف</option><option value="completed">پایان‌یافته</option>
            </select>
          </label>
          <label class="text-sm font-semibold">شروع اعتبار
            <input v-model="form.effective_from" type="date" required class="mt-1 w-full rounded-xl border-slate-300">
          </label>
          <label class="text-sm font-semibold">پایان اعتبار (اختیاری)
            <input v-model="form.effective_until" type="date" class="mt-1 w-full rounded-xl border-slate-300">
            <small v-if="form.errors.effective_until" class="text-rose-600">{{ form.errors.effective_until }}</small>
          </label>
          <label class="text-sm font-semibold">تاریخ بازبینی (اختیاری)
            <input v-model="form.review_at" type="date" class="mt-1 w-full rounded-xl border-slate-300">
            <small v-if="form.errors.review_at" class="text-rose-600">{{ form.errors.review_at }}</small>
          </label>
          <div class="flex items-end gap-2 md:col-span-2">
            <button :disabled="form.processing" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">{{ form.processing ? 'در حال ذخیره...' : editing ? 'ذخیره تغییرات' : 'ثبت مسئولیت' }}</button>
            <button v-if="editing" type="button" @click="resetForm" class="rounded-xl border px-5 py-3 text-sm font-bold">پاک‌کردن فرم</button>
          </div>
        </form>
      </section>

      <section class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="font-black">فهرست مسئولیت‌ها</h2>
          <span class="text-xs text-slate-500">{{ responsibilities.length }} مورد</span>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
          <article v-for="item in responsibilities" :key="item.id" class="rounded-2xl border p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="font-black">{{ item.title }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ item.staff_name }} {{ item.staff_family || '' }} · {{ item.clinic_name }}</p>
              </div>
              <div class="flex flex-wrap gap-1 text-xs">
                <span class="rounded-full bg-slate-100 px-2 py-1">{{ statusLabel(item.status) }}</span>
                <span class="rounded-full px-2 py-1" :class="item.priority === 'critical' ? 'bg-rose-100 text-rose-700' : item.priority === 'important' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100'">{{ priorityLabel(item.priority) }}</span>
              </div>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ item.description }}</p>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
              <span>از {{ item.effective_from }}</span>
              <span v-if="item.effective_until">تا {{ item.effective_until }}</span>
              <span v-if="item.review_at">بازبینی: {{ item.review_at }}</span>
            </div>
            <div v-if="item.can_manage" class="mt-4 flex gap-2 border-t pt-3">
              <button type="button" @click="edit(item)" class="rounded-lg border px-3 py-2 text-xs font-bold">ویرایش</button>
              <button type="button" @click="archive(item)" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700">بایگانی</button>
            </div>
          </article>
          <p v-if="!responsibilities.length" class="rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 lg:col-span-2">هنوز شرح وظیفه‌ای برای نمایش ثبت نشده است.</p>
        </div>
      </section>
    </main>
  </PortalLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: Object,
  organization: Object,
  credentials: Object,
  staffOptions: Array,
  clinics: Array,
  credentialTypes: Array,
  canManage: Boolean,
  maxUploadKb: Number,
})

const form = useForm({
  organization_id: props.organization.id,
  clinic_id: '',
  staff_user_id: '',
  credential_type: 'medical_license',
  title: '',
  issuer: '',
  license_number: '',
  issued_at: '',
  expires_at: '',
  file: null,
})

const rows = computed(() => props.credentials?.data || [])
const statusLabel = status => ({
  pending: 'در انتظار بررسی',
  verified: 'تأییدشده',
  rejected: 'ردشده',
})[status] || status
const typeLabel = type => props.credentialTypes.find(item => item.value === type)?.label || type
const formatDate = value => value ? new Date(value).toLocaleDateString('fa-IR') : '—'

function submit() {
  form.post(route('portal.staff-credentials.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset('clinic_id', 'staff_user_id', 'title', 'issuer', 'license_number', 'issued_at', 'expires_at', 'file')
      form.credential_type = 'medical_license'
      form.clearErrors()
    },
  })
}

function review(item, decision) {
  let rejection_reason = null
  if (decision === 'rejected') {
    rejection_reason = window.prompt('علت رد مدرک را وارد کنید:')
    if (!rejection_reason || rejection_reason.trim().length < 5) return
  }
  router.patch(route('portal.staff-credentials.review', item.id), {
    decision,
    rejection_reason,
  }, { preserveScroll: true })
}

function archive(item) {
  if (!window.confirm('مدرک بایگانی شود؟ فایل اصلی حذف نخواهد شد.')) return
  router.delete(route('portal.staff-credentials.archive', item.id), { preserveScroll: true })
}
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto max-w-7xl space-y-5 p-3 sm:p-5">
      <header class="rounded-3xl bg-gradient-to-l from-slate-950 to-cyan-900 p-6 text-white">
        <p class="text-xs font-bold text-cyan-200">PRONEEX · HUMAN RESOURCES</p>
        <h1 class="mt-2 text-2xl font-black">مدارک و صلاحیت حرفه‌ای کارکنان</h1>
        <p class="mt-2 text-sm text-white/80">{{ organization.name }} · فایل‌ها در فضای خصوصی ذخیره می‌شوند و فقط فرد مربوط و مالک سازمان به آن‌ها دسترسی دارند.</p>
      </header>

      <section class="rounded-2xl border bg-white p-4 shadow-sm">
        <h2 class="font-black">ثبت مدرک جدید</h2>
        <form @submit.prevent="submit" class="mt-4 grid gap-3 md:grid-cols-2">
          <label v-if="canManage" class="text-sm font-semibold">کارمند / پزشک
            <select v-model="form.staff_user_id" required class="mt-1 w-full rounded-xl border-slate-300">
              <option value="">انتخاب فرد</option>
              <option v-for="person in staffOptions" :key="person.id" :value="person.id">{{ person.label }}</option>
            </select>
            <small v-if="form.errors.staff_user_id" class="text-rose-600">{{ form.errors.staff_user_id }}</small>
          </label>
          <label class="text-sm font-semibold">شعبه
            <select v-model="form.clinic_id" required class="mt-1 w-full rounded-xl border-slate-300">
              <option value="">انتخاب شعبه</option>
              <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
            </select>
            <small v-if="form.errors.clinic_id" class="text-rose-600">{{ form.errors.clinic_id }}</small>
          </label>
          <label class="text-sm font-semibold">نوع مدرک
            <select v-model="form.credential_type" required class="mt-1 w-full rounded-xl border-slate-300">
              <option v-for="type in credentialTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </label>
          <label class="text-sm font-semibold">عنوان مدرک
            <input v-model="form.title" required maxlength="180" class="mt-1 w-full rounded-xl border-slate-300" placeholder="مثلاً پروانه مسئول فنی">
            <small v-if="form.errors.title" class="text-rose-600">{{ form.errors.title }}</small>
          </label>
          <label class="text-sm font-semibold">صادرکننده
            <input v-model="form.issuer" maxlength="180" class="mt-1 w-full rounded-xl border-slate-300" placeholder="سازمان یا مرجع صادرکننده">
          </label>
          <label class="text-sm font-semibold">شماره پروانه / مدرک
            <input v-model="form.license_number" maxlength="100" class="mt-1 w-full rounded-xl border-slate-300">
          </label>
          <label class="text-sm font-semibold">تاریخ صدور
            <input v-model="form.issued_at" type="date" class="mt-1 w-full rounded-xl border-slate-300">
          </label>
          <label class="text-sm font-semibold">تاریخ انقضا
            <input v-model="form.expires_at" type="date" class="mt-1 w-full rounded-xl border-slate-300">
            <small v-if="form.errors.expires_at" class="text-rose-600">{{ form.errors.expires_at }}</small>
          </label>
          <label class="text-sm font-semibold md:col-span-2">فایل مدرک (PDF / JPG / PNG / WEBP · حداکثر {{ maxUploadKb / 1024 }} مگابایت)
            <input
              type="file"
              required
              accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
              class="mt-1 block w-full rounded-xl border border-slate-300 p-2"
              @input="form.file = $event.target.files[0]"
            >
            <small v-if="form.errors.file" class="text-rose-600">{{ form.errors.file }}</small>
          </label>
          <div class="md:col-span-2">
            <button :disabled="form.processing" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">
              {{ form.processing ? 'در حال بارگذاری...' : 'ثبت و ارسال برای بررسی' }}
            </button>
          </div>
        </form>
      </section>

      <section class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <h2 class="font-black">مدارک ثبت‌شده</h2>
          <span class="text-xs text-slate-500">{{ credentials.total || 0 }} مدرک</span>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
          <article v-for="item in rows" :key="item.id" class="rounded-2xl border p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="font-black">{{ item.title }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ item.staff?.name }} {{ item.staff?.family || '' }} · {{ item.clinic?.name }}</p>
              </div>
              <span class="rounded-full px-3 py-1 text-xs font-bold" :class="item.status === 'verified' ? 'bg-emerald-100 text-emerald-700' : item.status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'">{{ statusLabel(item.status) }}</span>
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
              <div><dt class="text-slate-500">نوع مدرک</dt><dd class="mt-1 font-bold">{{ typeLabel(item.credential_type) }}</dd></div>
              <div><dt class="text-slate-500">صادرکننده</dt><dd class="mt-1 font-bold">{{ item.issuer || '—' }}</dd></div>
              <div><dt class="text-slate-500">شماره مدرک</dt><dd class="mt-1 font-bold">{{ item.license_number || '—' }}</dd></div>
              <div><dt class="text-slate-500">تاریخ انقضا</dt><dd class="mt-1 font-bold" :class="item.is_expired ? 'text-rose-700' : ''">{{ formatDate(item.expires_at) }} {{ item.is_expired ? '· منقضی' : '' }}</dd></div>
            </dl>
            <p v-if="item.rejection_reason" class="mt-3 rounded-lg bg-rose-50 p-2 text-xs text-rose-700">علت رد: {{ item.rejection_reason }}</p>
            <div class="mt-4 flex flex-wrap gap-2 border-t pt-3">
              <a :href="route('portal.staff-credentials.download', item.id)" class="rounded-lg border px-3 py-2 text-xs font-bold">دریافت فایل خصوصی</a>
              <template v-if="canManage && item.status === 'pending'">
                <button type="button" @click="review(item, 'verified')" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white">تأیید مدرک</button>
                <button type="button" @click="review(item, 'rejected')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700">رد مدرک</button>
              </template>
              <button type="button" @click="archive(item)" class="rounded-lg border px-3 py-2 text-xs font-bold text-slate-600">بایگانی</button>
            </div>
          </article>
          <p v-if="!rows.length" class="rounded-xl bg-slate-50 p-8 text-center text-sm text-slate-500 lg:col-span-2">هنوز مدرکی ثبت نشده است.</p>
        </div>
        <div v-if="credentials.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
          <button v-for="link in credentials.links" :key="link.label" type="button" :disabled="!link.url" @click="router.visit(link.url)" class="rounded-lg border px-3 py-2 text-xs" :class="link.active ? 'bg-slate-900 text-white' : ''" v-html="link.label"></button>
        </div>
      </section>
    </main>
  </PortalLayout>
</template>

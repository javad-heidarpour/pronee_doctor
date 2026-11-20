<script setup>
import { computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
  doctor: { type: Object, required: true },
  patient: { type: Object, required: true },
  documents: { type: Object, required: true },
  clinics: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  categories: { type: Array, default: () => [] },
  maxUploadKb: { type: Number, default: 20480 },
})

const form = useForm({
  clinic_id: props.clinics[0]?.id ?? '',
  appointment_id: '',
  category: 'other',
  title: '',
  description: '',
  file: null,
})

const selectedFileName = computed(() => form.file?.name ?? 'فایلی انتخاب نشده است')

function submit() {
  form.post(route('portal.patients.documents.store', props.patient.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => form.reset('appointment_id', 'category', 'title', 'description', 'file'),
  })
}

function archive(document) {
  if (!window.confirm('این سند بایگانی شود؟ فایل اصلی حذف نخواهد شد.')) return

  router.patch(
    route('portal.patients.documents.archive', [props.patient.id, document.id]),
    {},
    { preserveScroll: true }
  )
}

function formatSize(bytes) {
  const value = Number(bytes || 0)
  if (value < 1024) return value.toLocaleString('fa-IR') + ' بایت'
  if (value < 1024 * 1024) return (value / 1024).toFixed(1).toLocaleString('fa-IR') + ' کیلوبایت'
  return (value / (1024 * 1024)).toFixed(1) + ' مگابایت'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('fa-IR')
}
</script>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto w-full max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div>
          <p class="text-xs font-bold text-teal-700">پرونده بالینی</p>
          <h1 class="mt-1 text-xl font-black text-slate-900">اسناد پزشکی {{ patient.name }}</h1>
          <p class="mt-1 text-sm text-slate-500">فایل‌ها در فضای خصوصی نگهداری می‌شوند و فقط کاربران مجاز همین پرونده به آن‌ها دسترسی دارند.</p>
        </div>
        <Link :href="route('portal.patients.directory')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
          بازگشت به بیماران
        </Link>
      </header>

      <section v-if="canManage" class="rounded-2xl border border-teal-100 bg-white p-5 shadow-sm">
        <h2 class="text-base font-black text-slate-900">بارگذاری سند جدید</h2>
        <form class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="space-y-1 text-sm font-bold text-slate-700">
            مطب
            <select v-model="form.clinic_id" required class="w-full rounded-xl border-slate-200 text-sm">
              <option value="" disabled>انتخاب مطب</option>
              <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">{{ clinic.name }}</option>
            </select>
            <span v-if="form.errors.clinic_id" class="block text-xs text-rose-600">{{ form.errors.clinic_id }}</span>
          </label>

          <label class="space-y-1 text-sm font-bold text-slate-700">
            دسته سند
            <select v-model="form.category" required class="w-full rounded-xl border-slate-200 text-sm">
              <option v-for="category in categories" :key="category.value" :value="category.value">{{ category.label }}</option>
            </select>
            <span v-if="form.errors.category" class="block text-xs text-rose-600">{{ form.errors.category }}</span>
          </label>

          <label class="space-y-1 text-sm font-bold text-slate-700 sm:col-span-2">
            عنوان سند
            <input v-model="form.title" required maxlength="180" class="w-full rounded-xl border-slate-200 text-sm" placeholder="مثلاً آزمایش خون آبان ۱۴۰۵">
            <span v-if="form.errors.title" class="block text-xs text-rose-600">{{ form.errors.title }}</span>
          </label>

          <label class="space-y-1 text-sm font-bold text-slate-700 sm:col-span-2">
            توضیحات (اختیاری)
            <textarea v-model="form.description" rows="2" maxlength="3000" class="w-full rounded-xl border-slate-200 text-sm" placeholder="توضیح کوتاه درباره سند"></textarea>
            <span v-if="form.errors.description" class="block text-xs text-rose-600">{{ form.errors.description }}</span>
          </label>

          <label class="space-y-1 text-sm font-bold text-slate-700 sm:col-span-2">
            فایل (PDF، تصویر یا DICOM؛ حداکثر ۲۰ مگابایت)
            <input type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.tif,.tiff,.dcm,application/pdf,image/jpeg,image/png,image/webp,image/tiff,application/dicom" class="block w-full rounded-xl border border-slate-200 p-2 text-sm" @input="form.file = $event.target.files[0]">
            <span class="block text-xs font-normal text-slate-500">{{ selectedFileName }}</span>
            <span v-if="form.errors.file" class="block text-xs text-rose-600">{{ form.errors.file }}</span>
          </label>

          <div class="flex items-center justify-between gap-3 sm:col-span-2">
            <p class="text-xs text-slate-500">نام فایل اصلی حفظ می‌شود؛ مسیر ذخیره‌سازی به کاربر نمایش داده نمی‌شود.</p>
            <button type="submit" :disabled="form.processing || !clinics.length" class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">
              {{ form.processing ? 'در حال بارگذاری…' : 'ذخیره خصوصی سند' }}
            </button>
          </div>
        </form>
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-5">
          <h2 class="font-black text-slate-900">اسناد ثبت‌شده</h2>
          <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ documents.total?.toLocaleString('fa-IR') ?? 0 }} سند</span>
        </div>

        <div v-if="documents.data?.length" class="divide-y divide-slate-100">
          <article v-for="document in documents.data" :key="document.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="break-words font-bold text-slate-900">{{ document.title }}</h3>
                <span class="rounded-md bg-teal-50 px-2 py-1 text-[11px] font-bold text-teal-800">{{ categories.find(item => item.value === document.category)?.label ?? document.category }}</span>
              </div>
              <p class="mt-1 break-all text-xs text-slate-500">{{ document.original_name }} · {{ formatSize(document.file_size) }}</p>
              <p v-if="document.description" class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ document.description }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ document.clinic?.name ?? 'مطب نامشخص' }} · {{ formatDate(document.uploaded_at) }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
              <a :href="route('portal.patients.documents.download', [patient.id, document.id])" class="rounded-lg border border-teal-200 px-3 py-2 text-xs font-bold text-teal-800 hover:bg-teal-50">دریافت فایل</a>
              <button v-if="canManage" type="button" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50" @click="archive(document)">بایگانی</button>
            </div>
          </article>
        </div>
        <div v-else class="p-10 text-center">
          <p class="font-bold text-slate-700">هنوز سندی برای این بیمار ثبت نشده است.</p>
          <p class="mt-1 text-xs text-slate-500">پس از بارگذاری، اسناد در همین بخش فهرست می‌شوند.</p>
        </div>
      </section>

      <nav v-if="documents.links?.length > 3" class="flex flex-wrap justify-center gap-2">
        <Link v-for="link in documents.links" :key="link.label" :href="link.url || '#'" :preserve-scroll="true" class="rounded-lg border px-3 py-2 text-xs font-bold" :class="link.active ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-200 text-slate-600'" v-html="link.label" />
      </nav>
    </main>
  </PortalLayout>
</template>

<template>
  <PortalLayout :doctor="doctor">
    <main dir="rtl" class="mx-auto w-full max-w-6xl space-y-5 p-4 sm:p-6">
      <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div>
          <p class="text-xs font-bold text-teal-700">پرونده بیمار</p>
          <h1 class="mt-1 text-xl font-black text-slate-900">{{ patient.patient_name || 'بیمار بدون نام' }}</h1>
          <p class="mt-2 text-sm text-slate-500">سوابق ثبت‌شده نزد همین پزشک در مطب‌های قابل دسترس</p>
        </div>
        <Link :href="route('portal.patients.directory')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
          بازگشت به دفترچه بیماران
        </Link>
      </header>

      <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
          <p class="text-xs font-bold text-slate-500">شماره تماس</p>
          <p class="mt-2 text-base font-extrabold text-slate-800" dir="ltr">{{ patient.patient_phone || '—' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
          <p class="text-xs font-bold text-slate-500">کد ملی</p>
          <p class="mt-2 text-base font-extrabold text-slate-800" dir="ltr">{{ patient.patient_national_code || '—' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
          <p class="text-xs font-bold text-slate-500">تعداد مراجعات</p>
          <p class="mt-2 text-base font-extrabold text-teal-700">{{ visits.length.toLocaleString('fa-IR') }} مراجعه</p>
        </div>
      </section>

      <section v-if="patient.id" class="space-y-4 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 class="font-black text-slate-900">اطلاعات پزشکی پرونده</h2>
            <p class="mt-1 text-xs text-slate-500">حساسیت‌ها، داروهای مصرفی و سوابق پزشکی ثبت‌شده</p>
          </div>
          <span class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">اطلاعات محرمانه</span>
        </div>

        <form @submit.prevent="saveClinicalRecord" class="space-y-3 rounded-xl border border-slate-100 bg-slate-50 p-4">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="space-y-1 text-xs font-bold text-slate-600">
              نوع اطلاعات
              <select v-model="clinicalForm.kind" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="allergy">حساسیت</option>
                <option value="medication">داروی مصرفی</option>
                <option value="medical_history">سابقه پزشکی</option>
              </select>
            </label>
            <label class="space-y-1 text-xs font-bold text-slate-600">
              عنوان
              <input v-model="clinicalForm.title" required maxlength="180" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="مثلاً حساسیت به پنی‌سیلین" />
            </label>
          </div>

          <div v-if="clinicalForm.kind === 'medication'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="space-y-1 text-xs font-bold text-slate-600">
              مقدار / دوز
              <input v-model="clinicalForm.dosage" maxlength="120" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="مثلاً ۵۰۰ میلی‌گرم" />
            </label>
            <label class="space-y-1 text-xs font-bold text-slate-600">
              دفعات مصرف
              <input v-model="clinicalForm.frequency" maxlength="120" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="مثلاً روزی دو بار" />
            </label>
          </div>

          <label class="block space-y-1 text-xs font-bold text-slate-600">
            توضیحات
            <textarea v-model="clinicalForm.description" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="توضیحات تکمیلی، واکنش حساسیتی یا جزئیات سابقه"></textarea>
          </label>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="space-y-1 text-xs font-bold text-slate-600">
              تاریخ شروع
              <input v-model="clinicalForm.start_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" />
            </label>
            <label class="space-y-1 text-xs font-bold text-slate-600">
              تاریخ پایان
              <input v-model="clinicalForm.end_date" type="date" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" />
            </label>
          </div>

          <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
            <input v-model="clinicalForm.is_active" type="checkbox" class="rounded border-slate-300 text-teal-600" />
            مورد فعال است
          </label>

          <div class="flex flex-wrap gap-2">
            <button type="submit" :disabled="savingClinicalRecord" class="rounded-lg bg-teal-700 px-4 py-2.5 text-xs font-bold text-white disabled:opacity-50">
              {{ savingClinicalRecord ? 'در حال ذخیره...' : (editingClinicalRecordId ? 'ذخیره ویرایش' : 'ثبت اطلاعات پزشکی') }}
            </button>
            <button v-if="editingClinicalRecordId" type="button" @click="resetClinicalForm" class="rounded-lg border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600">
              انصراف از ویرایش
            </button>
          </div>
        </form>

        <div v-if="patient.clinical_records?.length" class="space-y-3">
          <article v-for="record in patient.clinical_records" :key="record.id" class="rounded-xl border border-slate-100 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600">{{ clinicalKindLabel(record.kind) }}</span>
                  <h3 class="font-bold text-slate-800">{{ record.title }}</h3>
                  <span :class="record.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-md px-2 py-1 text-[10px] font-bold">{{ record.is_active ? 'فعال' : 'غیرفعال' }}</span>
                </div>
                <p v-if="record.dosage || record.frequency" class="mt-2 text-xs text-slate-600">
                  {{ record.dosage || '' }} <span v-if="record.dosage && record.frequency"> · </span>{{ record.frequency || '' }}
                </p>
                <p v-if="record.description" class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ record.description }}</p>
                <p v-if="record.start_date || record.end_date" class="mt-2 text-[11px] text-slate-400">
                  {{ record.start_date || 'تاریخ شروع ثبت نشده' }} تا {{ record.end_date || 'بدون تاریخ پایان' }}
                </p>
              </div>
              <button type="button" @click="editClinicalRecord(record)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">ویرایش</button>
            </div>
          </article>
        </div>
        <p v-else class="rounded-xl bg-slate-50 p-5 text-center text-xs text-slate-500">هنوز اطلاعات پزشکی مستقلی برای این بیمار ثبت نشده است.</p>
      </section>
      <section v-else class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-800">
        برای ثبت اطلاعات پزشکی مستقل، ابتدا نوبت‌های قدیمی را به پرونده‌های بیماران متصل کنید.
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
          <div>
            <h2 class="font-black text-slate-900">تاریخچه مراجعات</h2>
            <p class="mt-1 text-xs text-slate-500">از جدیدترین مراجعه به قدیمی‌ترین</p>
          </div>
          <span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700">{{ visits.length }} سابقه</span>
        </div>

        <div v-if="visits.length" class="divide-y divide-slate-100">
          <article v-for="visit in visits" :key="visit.id" class="p-5 transition hover:bg-slate-50/70">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-extrabold text-slate-800">{{ formatDate(visit.appointment_date) }}</h3>
                  <span class="text-xs text-slate-400">{{ formatTime(visit.appointment_time) }}</span>
                  <span :class="statusClass(visit.status)" class="rounded-lg px-2.5 py-1 text-[11px] font-bold">{{ statusLabel(visit.status) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">
                  {{ visit.clinic?.name || 'مطب ثبت نشده' }}
                  <span v-if="visit.visit_reason?.title"> · {{ visit.visit_reason.title }}</span>
                </p>
                <p v-if="visit.description" class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm leading-7 text-slate-600">{{ visit.description }}</p>
                <div v-if="visit.surgery_type?.title || visit.surgery_date" class="mt-2 text-xs text-slate-500">
                  <span v-if="visit.surgery_type?.title">نوع عمل: {{ visit.surgery_type.title }}</span>
                  <span v-if="visit.surgery_date"> · تاریخ عمل: {{ formatDate(visit.surgery_date) }}</span>
                </div>
              </div>
              <div class="flex shrink-0 flex-wrap items-center gap-2">
                <span :class="paymentClass(visit.latest_payment?.status)" class="rounded-lg px-3 py-2 text-xs font-bold">
                  {{ paymentLabel(visit.latest_payment?.status) }}
                </span>
                <Link :href="route('portal.patients.show', visit.id)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-white">
                  جزئیات نوبت
                </Link>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="p-12 text-center text-sm text-slate-500">سابقه‌ای برای این بیمار ثبت نشده است.</div>
      </section>
    </main>
  </PortalLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({
  doctor: { type: Object, required: true },
  visits: { type: Array, default: () => [] },
});

const patient = computed(() => {
  const latestVisit = props.visits[0] || {};
  const profile = latestVisit.patient || {};

  return {
    patient_name: profile.name || latestVisit.patient_name || '',
    patient_phone: profile.phone || latestVisit.patient_phone || '',
    patient_national_code: profile.national_code || latestVisit.patient_national_code || '',
    id: profile.id || null,
    clinical_records: profile.clinical_records || [],
  };
});

const editingClinicalRecordId = ref(null);
const savingClinicalRecord = ref(false);
const clinicalForm = reactive({
  kind: 'medical_history',
  title: '',
  description: '',
  dosage: '',
  frequency: '',
  start_date: '',
  end_date: '',
  is_active: true,
});

const resetClinicalForm = () => {
  editingClinicalRecordId.value = null;
  Object.assign(clinicalForm, {
    kind: 'medical_history',
    title: '',
    description: '',
    dosage: '',
    frequency: '',
    start_date: '',
    end_date: '',
    is_active: true,
  });
};

const editClinicalRecord = (record) => {
  editingClinicalRecordId.value = record.id;
  Object.assign(clinicalForm, {
    kind: record.kind,
    title: record.title || '',
    description: record.description || '',
    dosage: record.dosage || '',
    frequency: record.frequency || '',
    start_date: record.start_date || '',
    end_date: record.end_date || '',
    is_active: Boolean(record.is_active),
  });
};

const saveClinicalRecord = () => {
  savingClinicalRecord.value = true;
  const payload = { ...clinicalForm };
  const url = editingClinicalRecordId.value
    ? route('portal.patients.clinical-records.update', [patient.value.id, editingClinicalRecordId.value])
    : route('portal.patients.clinical-records.store', patient.value.id);
  const options = {
    preserveScroll: true,
    onFinish: () => { savingClinicalRecord.value = false; },
    onSuccess: () => resetClinicalForm(),
  };

  if (editingClinicalRecordId.value) {
    router.put(url, payload, options);
  } else {
    router.post(url, payload, options);
  }
};

const clinicalKindLabel = (kind) => ({
  allergy: 'حساسیت',
  medication: 'دارو',
  medical_history: 'سابقه پزشکی',
}[kind] || 'اطلاعات پزشکی');
const formatDate = (value) => {
  if (!value) return '—';
  const date = new Date(value + 'T00:00:00');
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium' }).format(date);
};
const formatTime = (value) => value ? String(value).slice(0, 5) : '—';
const statusLabel = (status) => ({
  pending: 'در انتظار',
  confirmed: 'تأیید شده',
  visited: 'ویزیت شده',
  cancelled: 'لغو شده',
}[status] || 'نامشخص');
const statusClass = (status) => ({
  pending: 'bg-amber-50 text-amber-700',
  confirmed: 'bg-emerald-50 text-emerald-700',
  visited: 'bg-violet-50 text-violet-700',
  cancelled: 'bg-rose-50 text-rose-700',
}[status] || 'bg-slate-100 text-slate-600');
const paymentLabel = (status) => ({
  paid: 'پرداخت‌شده',
  pending: 'در انتظار پرداخت',
  initiated: 'در حال پرداخت',
  failed: 'پرداخت ناموفق',
  refunded: 'مستردشده',
  cancelled: 'لغو پرداخت',
}[status] || 'بدون پرداخت');
const paymentClass = (status) => ({
  paid: 'bg-emerald-50 text-emerald-700',
  pending: 'bg-amber-50 text-amber-700',
  initiated: 'bg-amber-50 text-amber-700',
  failed: 'bg-rose-50 text-rose-700',
  refunded: 'bg-sky-50 text-sky-700',
  cancelled: 'bg-rose-50 text-rose-700',
}[status] || 'bg-slate-100 text-slate-600');
</script>

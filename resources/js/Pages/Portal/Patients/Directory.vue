<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="space-y-5">
      <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-black text-slate-800">دفترچه بیماران</h2>
            <p class="mt-1 text-sm text-slate-500">فهرست بیماران شناسایی‌شده از سوابق نوبت‌های همین پزشک و مطب‌های مجاز</p>
          </div>
          <Link :href="route('portal.patients.index')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
            بازگشت به نوبت‌ها
          </Link>
        </div>
      </section>

      <form @submit.prevent="searchPatients" class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row">
        <input
          v-model="search"
          type="search"
          maxlength="150"
          placeholder="جستجو بر اساس نام، شماره تماس یا کد ملی"
          class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
        />
        <button type="submit" class="rounded-xl bg-teal-600 px-5 py-3 text-sm font-bold text-white hover:bg-teal-700">جستجو</button>
        <button type="button" @click="clearSearch" class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-200">پاک کردن</button>
      </form>

      <section class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="font-bold text-slate-800">بیماران ثبت‌شده</h3>
          <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ patients.total }} بیمار</span>
        </div>

        <div v-if="patients.data.length" class="overflow-x-auto">
          <table class="w-full min-w-[760px] text-right">
            <thead class="bg-slate-50 text-xs text-slate-500">
              <tr>
                <th class="px-5 py-4 font-bold">نام بیمار</th>
                <th class="px-5 py-4 font-bold">شماره تماس</th>
                <th class="px-5 py-4 font-bold">کد ملی</th>
                <th class="px-5 py-4 font-bold">تعداد نوبت‌ها</th>
                <th class="px-5 py-4 font-bold">آخرین مراجعه</th>
                <th class="px-5 py-4 font-bold">اقدام</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="patient in patients.data" :key="patient.patient_key" class="hover:bg-slate-50">
                <td class="px-5 py-4 text-sm font-bold text-slate-800">{{ patient.patient_name || 'بدون نام' }}</td>
                <td class="px-5 py-4 text-sm text-slate-600" dir="ltr">{{ patient.patient_phone || '—' }}</td>
                <td class="px-5 py-4 text-sm text-slate-600" dir="ltr">{{ patient.patient_national_code || '—' }}</td>
                <td class="px-5 py-4 text-sm font-bold text-teal-700">{{ Number(patient.appointments_count).toLocaleString('fa-IR') }}</td>
                <td class="px-5 py-4 text-sm text-slate-600">{{ formatDate(patient.last_appointment_date) }}</td>
                <td class="px-5 py-4">
                  <Link :href="route('portal.patients.show', patient.latest_appointment_id)" class="inline-flex rounded-lg bg-teal-50 px-3 py-2 text-xs font-bold text-teal-700 hover:bg-teal-100">
                    پرونده و سوابق
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="px-5 py-16 text-center">
          <div class="text-4xl">🗂️</div>
          <p class="mt-3 font-bold text-slate-700">بیماری پیدا نشد</p>
          <p class="mt-1 text-sm text-slate-500">برای نمایش بیمار، باید حداقل یک نوبت با شماره تماس یا کد ملی ثبت شده باشد.</p>
        </div>

        <div v-if="patients.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
          <p class="text-xs text-slate-500">نمایش {{ patients.from }} تا {{ patients.to }} از {{ patients.total }}</p>
          <div class="flex flex-wrap gap-1">
            <Link
              v-for="link in patients.links"
              :key="link.label"
              :href="link.url || '#'"
              :preserve-scroll="true"
              :class="[
                'rounded-lg px-3 py-2 text-xs font-bold',
                link.active ? 'bg-teal-600 text-white' : link.url ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'pointer-events-none bg-slate-50 text-slate-300'
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </section>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({
  doctor: { type: Object, required: true },
  patients: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');

const searchPatients = () => {
  router.get(route('portal.patients.directory'), { search: search.value || undefined }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearSearch = () => {
  search.value = '';
  searchPatients();
};

const formatDate = (value) => {
  if (!value) return '—';
  const date = new Date(`${value}T00:00:00`);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium' }).format(date);
};
</script>

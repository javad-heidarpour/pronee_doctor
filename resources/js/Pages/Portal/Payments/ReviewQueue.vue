<script setup>
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  payments: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
  filters: { type: Object, default: () => ({}) },
});

const rows = computed(() => props.payments?.data ?? []);
const reconcilingId = ref(null);

const reconcile = (payment) => {
  if (!payment?.ref_num || reconcilingId.value !== null) return;

  const confirmed = window.confirm(
    "وضعیت این تراکنش مستقیماً از سامان‌کیش استعلام می‌شود. ادامه می‌دهید؟"
  );

  if (!confirmed) return;

  reconcilingId.value = payment.id;
  router.post(route("portal.payments.reconcile", payment.id), {}, {
    preserveScroll: true,
    onFinish: () => { reconcilingId.value = null; },
  });
};

const form = reactive({
  search: props.filters?.search ?? "",
  gateway: props.filters?.gateway ?? "",
  from: props.filters?.from ?? "",
  to: props.filters?.to ?? "",
});

const applyFilters = () => {
  router.get(route("portal.payments.review-queue"), form, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const clearFilters = () => {
  Object.assign(form, { search: "", gateway: "", from: "", to: "" });
  applyFilters();
};

const money = (value) =>
  new Intl.NumberFormat("fa-IR").format(Number(value || 0)) + " ریال";

const dateTime = (value) => {
  if (!value) return "—";
  return new Intl.DateTimeFormat("fa-IR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
};

const statusLabel = (status) => ({
  pending: "در انتظار",
  initiated: "ارسال‌شده به درگاه",
}[status] || status || "—");

const appointmentStatusLabel = (status) => ({
  pending: "در انتظار",
  confirmed: "تأییدشده",
  cancelled: "لغوشده",
}[status] || status || "—");
</script>

<template>
  <Head title="پرداخت‌های نیازمند بررسی" />
  <PortalLayout title="پرداخت‌های نیازمند بررسی">
    <main dir="rtl" class="mx-auto w-full max-w-7xl space-y-5 p-4 text-slate-800 sm:p-6">
      <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-black text-slate-900 sm:text-2xl">صف بررسی تراکنش‌ها</h1>
          <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">
            این فهرست پرداخت‌های نامشخص یا بیش از ۱۵ دقیقه تعیین‌تکلیف‌نشده را نمایش می‌دهد.
            تا زمان استعلام معتبر از درگاه، وضعیت پرداخت را دستی «موفق» نکنید.
          </p>
        </div>
        <span class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">
          {{ payments.total ?? 0 }} مورد نیازمند بررسی
        </span>
      </header>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
          <label class="text-xs font-bold text-slate-600">
            شناسه پرداخت، نوبت یا کد پیگیری
            <input v-model="form.search" type="search" maxlength="100" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="جستجو..." />
          </label>
          <label class="text-xs font-bold text-slate-600">
            درگاه
            <select v-model="form.gateway" class="mt-2 w-full rounded-xl border-slate-200 text-sm">
              <option value="">همه درگاه‌ها</option>
              <option value="sep">سامان (SEP)</option>
            </select>
          </label>
          <label class="text-xs font-bold text-slate-600">
            از تاریخ به‌روزرسانی
            <input v-model="form.from" type="date" class="mt-2 w-full rounded-xl border-slate-200 text-sm" />
          </label>
          <label class="text-xs font-bold text-slate-600">
            تا تاریخ به‌روزرسانی
            <input v-model="form.to" type="date" class="mt-2 w-full rounded-xl border-slate-200 text-sm" />
          </label>
          <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-800">اعمال فیلتر</button>
            <button type="button" @click="clearFilters" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200">پاک‌کردن</button>
          </div>
          <p v-if="form.from && form.to && form.to < form.from" class="text-xs text-rose-600 sm:col-span-2 xl:col-span-5">
            تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.
          </p>
        </form>
      </section>

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="!rows.length" class="p-10 text-center">
          <div class="text-3xl">✓</div>
          <p class="mt-3 font-bold text-slate-700">موردی برای بررسی پیدا نشد</p>
          <p class="mt-1 text-sm text-slate-500">در حال حاضر پرداخت نامشخصی در صف بررسی نیست.</p>
        </div>

        <div v-else-if="rows.length" class="space-y-3 p-3 md:hidden">
          <article v-for="payment in rows" :key="'mobile-'+payment.id" class="rounded-xl border border-slate-200 p-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div><p class="font-black text-slate-900">پرداخت #{{ payment.id }} · نوبت #{{ payment.appointment_id }}</p><p class="mt-1 text-xs text-slate-500">{{ payment.appointment?.appointment_date || "—" }} <span v-if="payment.appointment?.appointment_time"> · {{ payment.appointment.appointment_time }}</span></p></div>
              <span class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-800">{{ statusLabel(payment.status) }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
              <div><p class="text-slate-500">مبلغ</p><p class="mt-1 font-black tabular-nums">{{ money(payment.amount_rial) }}</p></div>
              <div><p class="text-slate-500">درگاه</p><p class="mt-1 font-semibold">{{ payment.gateway || "—" }}</p></div>
              <div class="min-w-0"><p class="text-slate-500">شناسه درگاه</p><p class="mt-1 break-all font-mono">{{ payment.ref_num || "—" }}</p></div>
              <div><p class="text-slate-500">آخرین تغییر</p><p class="mt-1">{{ dateTime(payment.updated_at) }}</p></div>
            </div>
            <p class="mt-3 break-words rounded-lg bg-slate-50 p-2 text-xs leading-6 text-slate-600">{{ payment.result_message || "پیام ثبت نشده است." }}</p>
            <button v-if="payment.ref_num" type="button" :disabled="reconcilingId !== null" @click="reconcile(payment)" class="mt-3 w-full rounded-lg bg-teal-700 px-3 py-2.5 text-xs font-bold text-white disabled:opacity-50">{{ reconcilingId === payment.id ? "در حال استعلام..." : "استعلام مجدد SEP" }}</button>
            <p v-else class="mt-3 text-xs text-amber-700">فاقد RefNum؛ پیگیری از گزارش پذیرنده</p>
          </article>
        </div>
        <div v-if="rows.length" class="hidden overflow-x-auto md:block">
          <table class="w-full min-w-[1180px] text-right text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
              <tr>
                <th class="px-4 py-4 font-bold">پرداخت / نوبت</th>
                <th class="px-4 py-4 font-bold">اطلاعات نوبت</th>
                <th class="px-4 py-4 font-bold">مبلغ</th>
                <th class="px-4 py-4 font-bold">وضعیت</th>
                <th class="px-4 py-4 font-bold">شناسه درگاه</th>
                <th class="px-4 py-4 font-bold">پیام درگاه</th>
                <th class="px-4 py-4 font-bold">آخرین تغییر</th>
                <th class="px-4 py-4 font-bold">اقدام</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="payment in rows" :key="payment.id" class="align-top hover:bg-slate-50/70">
                <td class="px-4 py-4">
                  <div class="font-bold text-slate-800">#{{ payment.id }}</div>
                  <div class="mt-1 text-xs text-slate-500">نوبت #{{ payment.appointment_id }}</div>
                  <div class="mt-1 max-w-48 truncate font-mono text-[10px] text-slate-400">{{ payment.payment_uuid }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold">نوبت #{{ payment.appointment_id }}</div>
                  <div class="mt-1 text-xs text-slate-500">
                    {{ payment.appointment?.appointment_date || "—" }}
                    <span v-if="payment.appointment?.appointment_time"> · {{ payment.appointment.appointment_time }}</span>
                  </div>
                  <div class="mt-1 text-xs text-slate-400">وضعیت نوبت: {{ appointmentStatusLabel(payment.appointment?.status) }}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-4 font-bold tabular-nums">{{ money(payment.amount_rial) }}</td>
                <td class="px-4 py-4">
                  <span class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-800">{{ statusLabel(payment.status) }}</span>
                  <div class="mt-2 text-[11px] text-slate-500">{{ payment.result_code || "بدون کد نتیجه" }}</div>
                </td>
                <td class="px-4 py-4 text-xs">
                  <div>درگاه: {{ payment.gateway || "—" }}</div>
                  <div class="mt-1 break-all">RefNum: {{ payment.ref_num || "—" }}</div>
                  <div class="mt-1">Terminal: {{ payment.terminal_id || "—" }}</div>
                </td>
                <td class="max-w-xs whitespace-normal px-4 py-4 text-xs leading-6 text-slate-600">{{ payment.result_message || "پیام ثبت نشده است." }}</td>
                <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500">{{ dateTime(payment.updated_at) }}</td>
                <td class="px-4 py-4">
                  <button
                    v-if="payment.ref_num"
                    type="button"
                    :disabled="reconcilingId !== null"
                    @click="reconcile(payment)"
                    class="whitespace-nowrap rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {{ reconcilingId === payment.id ? "در حال استعلام..." : "استعلام مجدد SEP" }}
                  </button>
                  <span v-else class="text-xs leading-6 text-amber-700">
                    فاقد RefNum؛ پیگیری از گزارش پذیرنده
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="payments.links?.length > 3" class="flex flex-wrap items-center justify-center gap-1 border-t border-slate-100 p-4">
          <template v-for="(link, index) in payments.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              preserve-scroll
              class="rounded-lg px-3 py-2 text-xs font-bold"
              :class="link.active ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              v-html="link.label"
            />
            <span v-else class="rounded-lg px-3 py-2 text-xs text-slate-300" v-html="link.label" />
          </template>
        </div>
      </section>
    </main>
  </PortalLayout>
</template>

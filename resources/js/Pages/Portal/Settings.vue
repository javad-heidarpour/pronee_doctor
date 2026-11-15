<script setup>
import { computed, watch } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import numberToPersianWords from "@/Utils/numberToPersianWords";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  activeFee: {
    type: Object,
    default: null,
  },
  feeHistory: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

const form = useForm({
  fee_rial: props.activeFee?.fee_rial ? String(props.activeFee.fee_rial) : "",
});
const feeToman = computed(() => {
  const rial = Number(form.fee_rial);

  if (!form.fee_rial || !Number.isFinite(rial) || rial <= 0) {
    return null;
  }

  return Math.floor(rial / 10);
});
const flashMessage = computed(() => {
  const flash = page.props.flash;

  if (flash?.info) {
    return {
      text: flash.info,
      type: "info",
    };
  }

  if (flash?.success) {
    return {
      text: flash.success,
      type: "success",
    };
  }

  if (flash?.error) {
    return {
      text: flash.error,
      type: "error",
    };
  }

  return null;
});
const formatMoney = (value) => {
  if (value === null || value === undefined || value === "") {
    return "—";
  }

  return new Intl.NumberFormat("fa-IR").format(Number(value));
};

const formatDate = (value) => {
  if (!value) return "—";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return "—";

  return new Intl.DateTimeFormat("fa-IR", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(date);
};

const saveFee = () => {
  form.put(route("portal.Fees.update"), {
    preserveScroll: true,
  });
};

// بعد از دریافت اطلاعات جدید از سرور، فرم با تعرفه فعال هماهنگ شود.
watch(
  () => props.activeFee?.fee_rial,
  (value) => {
    if (!form.isDirty) {
      form.fee_rial = value ? String(value) : "";
    }
  }
);
</script>

<template>
  <PortalLayout :doctor="doctor">
    <Head title="تنظیم تعرفه ویزیت" />

    <div dir="rtl" class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
      <!-- عنوان صفحه -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800">مدیریت تعرفه ویزیت</h1>

        <p class="mt-2 text-sm text-gray-500">
          برای تغییر تعرفه، مبلغ جدید را ثبت کنید. تعرفه‌های قبلی در تاریخچه باقی
          می‌مانند.
        </p>
      </div>

      <!-- پیام سیستم -->
      <div
        v-if="flashMessage"
        role="status"
        class="rounded-xl border p-4 text-sm"
        :class="{
          'border-green-200 bg-green-50 text-green-700': flashMessage.type === 'success',
          'border-yellow-200 bg-yellow-50 text-yellow-800': flashMessage.type === 'info',
          'border-red-200 bg-red-50 text-red-700': flashMessage.type === 'error',
        }"
      >
        {{ flashMessage.text }}
      </div>
      <!-- تعرفه فعال -->
      <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-3">
          <h2 class="font-bold text-gray-800">تعرفه فعال فعلی</h2>

          <span
            v-if="activeFee"
            class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700"
          >
            فعال
          </span>
        </div>

        <div class="mt-4">
          <p class="text-3xl font-bold text-gray-900">
            {{ formatMoney(activeFee?.fee_rial) }}
            <span class="text-sm font-normal text-gray-500"> ریال </span>
          </p>

          <p class="mt-2 text-sm text-gray-500">
            تاریخ ثبت:
            {{ formatDate(activeFee?.created_at) }}
          </p>
        </div>
      </section>

      <!-- ثبت تعرفه جدید -->
      <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="font-bold text-gray-800">ثبت تعرفه جدید</h2>

        <p class="mt-2 text-sm leading-6 text-gray-500">
          با ثبت مبلغ جدید، تعرفه فعال قبلی غیرفعال می‌شود و یک رکورد جدید به تاریخچه
          اضافه خواهد شد.
        </p>

        <form class="mt-5 space-y-4" @submit.prevent="saveFee">
          <div>
            <label for="fee_rial" class="mb-2 block text-sm font-medium text-gray-700">
              مبلغ تعرفه جدید (ریال)
            </label>
            <div class="flex items-center">
              <div>
                <input
                  id="fee_rial"
                  v-model="form.fee_rial"
                  type="number"
                  min="1"
                  step="1"
                  required
                  dir="ltr"
                  inputmode="numeric"
                  placeholder="مثلاً 12500000"
                  class="w-full rounded-xl border border-gray-300 px-4 py-3 text-left outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  :class="{
                    'border-red-500': form.errors.fee_rial,
                  }"
                />
              </div>

              <div class="px-5">
                <p v-if="feeToman !== null" class="mt-2 text-sm text-blue-700">
                  {{ numberToPersianWords(feeToman) }}

                  (
                  <span class="px-2 text-lg font-bold text-blue-500">
                    {{ new Intl.NumberFormat("fa-IR").format(feeToman) }}
                  </span>
                  ) تومان
                </p>
              </div>

              <p v-if="form.errors.fee_rial" class="mt-2 text-sm text-red-600">
                {{ form.errors.fee_rial }}
              </p>
            </div>
            <p class="mt-2 text-xs text-gray-500">مبلغ را به ریال وارد کنید، نه تومان.</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-xl bg-blue-600 px-5 py-3 font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
          >
            {{ form.processing ? "در حال ثبت..." : "ثبت تعرفه جدید" }}
          </button>
        </form>
      </section>

      <!-- تاریخچه تعرفه‌ها -->
      <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
      >
        <div class="border-b border-gray-100 p-5">
          <h2 class="font-bold text-gray-800">تاریخچه تعرفه‌ها</h2>

          <p class="mt-1 text-sm text-gray-500">
            همه تعرفه‌های ثبت‌شده، از جدیدترین تا قدیمی‌ترین.
          </p>
        </div>

        <div v-if="feeHistory.length" class="overflow-x-auto">
          <table class="w-full min-w-[650px] text-right text-sm">
            <thead class="bg-gray-50 text-gray-600">
              <tr>
                <th class="px-4 py-3 font-medium">مبلغ (ریال)</th>
                <th class="px-4 py-3 font-medium">وضعیت</th>
                <th class="px-4 py-3 font-medium">ثبت‌کننده</th>
                <th class="px-4 py-3 font-medium">تاریخ ثبت</th>
                <th class="px-4 py-3 font-medium">تاریخ غیرفعال‌شدن</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="fee in feeHistory"
                :key="fee.id"
                :class="[
                  'transition hover:bg-gray-50',
                  fee.id === activeFee?.id ? 'bg-green-50' : '',
                ]"
              >
                <td class="whitespace-nowrap px-4 py-4 font-semibold text-gray-800">
                  {{ formatMoney(fee.fee_rial) }}
                </td>

                <td class="px-4 py-4">
                  <span
                    v-if="fee.is_active && fee.id === activeFee?.id"
                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800"
                  >
                    <span class="h-2 w-2 rounded-full bg-green-600"></span>
                    تعرفه فعلی
                  </span>

                  <span
                    v-else-if="fee.is_active"
                    class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800"
                  >
                    فعال — استفاده نمی‌شود
                  </span>

                  <span
                    v-else
                    class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600"
                  >
                    غیرفعال
                  </span>
                </td>

                <td class="whitespace-nowrap px-4 py-4 text-gray-600">
                  <span v-if="fee.creator">
                    {{
                      fee.creator?.caption +
                        " " +
                        fee.creator?.name +
                        " " +
                        fee.creator?.family ||
                      fee.created_by ||
                      "—"
                    }}
                  </span>
                  <span v-else> - </span>
                </td>

                <td class="whitespace-nowrap px-4 py-4 text-gray-600">
                  {{ formatDate(fee.created_at) }}
                </td>

                <td class="whitespace-nowrap px-4 py-4 text-gray-600">
                  {{ formatDate(fee.deactivated_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-8 text-center text-sm text-gray-500">
          هنوز تعرفه‌ای ثبت نشده است.
        </div>
      </section>
    </div>
  </PortalLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";

const page = usePage();

const paymentError = computed(() => {
  return page.props.errors?.payment ?? "";
});
const props = defineProps({
  visit: {
    type: Object,
    default: null,
  },
});

const processing = ref(false);
const ErrTxt = ref(null);
const cancelForm = useForm({ patient_phone: "" });
const cancellationSuccess = computed(() => page.props.flash?.success ?? "");
const rescheduleForm = useForm({ patient_phone: "", new_time_slot_id: "" });

const paid = computed(() => props.visit?.payment_status === "paid");

const canPay = computed(() => {
  return (
    !!props.visit &&
    !paid.value &&
    ["pending", "confirmed"].includes(props.visit.appointment_status)
  );
});

const paymentStatus = computed(() => {
  if (!props.visit) return null;

  if (paid.value) {
    return {
      title: "پرداخت موفق",
      description: "پرداخت این نوبت تأیید شده است.",
      icon: "✓",
      classes: "bg-emerald-50 text-emerald-700 border-emerald-200",
    };
  }

  if (props.visit.payment_status === "failed") {
    return {
      title: "پرداخت ناموفق",
      description:
        "آخرین تلاش پرداخت ناموفق بوده است. در صورت مجاز بودن نوبت، دوباره تلاش کنید.",
      icon: "!",
      classes: "bg-red-50 text-red-700 border-red-200",
    };
  }

  if (props.visit.appointment_status === "cancelled") {
    return {
      title: "نوبت لغو شده",
      description: "امکان پرداخت این نوبت وجود ندارد.",
      icon: "!",
      classes: "bg-red-50 text-red-700 border-red-200",
    };
  }

  if (["visited", "completed"].includes(props.visit.appointment_status)) {
    return {
      title: "ویزیت انجام شده",
      description: "این نوبت در وضعیت ویزیت انجام‌شده قرار دارد.",
      icon: "✓",
      classes: "bg-slate-100 text-slate-700 border-slate-200",
    };
  }

  return {
    title: "در انتظار پرداخت",
    description: "برای تکمیل فرایند پرداخت، از دکمه زیر استفاده کنید.",
    icon: "…",
    classes: "bg-amber-50 text-amber-700 border-amber-200",
  };
});

const formatPrice = (value) => {
  if (value === null || value === undefined || value === "") {
    return "نامشخص";
  }

  const amount = Number(value);

  if (!Number.isFinite(amount) || amount < 0) {
    return "نامشخص";
  }

  return new Intl.NumberFormat("fa-IR", {
    maximumFractionDigits: 0,
  }).format(Math.round(amount / 10));
};

const getPersianFullDate = (dateStr) => {
  if (!dateStr) return "مشخص نشده";

  const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  const date = match
    ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12)
    : new Date(dateStr);

  if (Number.isNaN(date.getTime())) {
    return "مشخص نشده";
  }

  return new Intl.DateTimeFormat("fa-IR", {
    calendar: "persian",
    year: "numeric",
    month: "long",
    day: "numeric",
    weekday: "long",
  }).format(date);
};

const rescheduleAppointment = () => {
  if (
    !props.visit?.can_reschedule ||
    rescheduleForm.processing ||
    !props.visit?.reschedule_url ||
    !rescheduleForm.new_time_slot_id
  ) return;

  rescheduleForm.post(props.visit.reschedule_url, {
    preserveScroll: true,
    onSuccess: () => rescheduleForm.reset(),
  });
};

const cancelAppointment = () => {
  if (!props.visit?.can_cancel || cancelForm.processing || !props.visit?.cancel_url) return;

  cancelForm.post(props.visit.cancel_url, {
    preserveScroll: true,
    onSuccess: () => cancelForm.reset(),
  });
};

const pay = () => {
  if (!canPay.value || processing.value) return;
  if (props.visit?.fee_rial <= 0) {
    processing.value = false;

    ErrTxt.value = "مبلغ قابلیت پرداخت را ندارد!";

    return;
  }

  processing.value = true;
  ErrTxt.value = null;

  const paymentUrl = props.visit?.payment_url;

  if (!paymentUrl) {
    processing.value = false;
    ErrTxt.value = "پیوند امن پرداخت معتبر نیست؛ صفحه را دوباره بارگذاری کنید.";
    return;
  }

  let csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  if (!csrfToken) {
    const xsrfCookie = document.cookie
      .split("; ")
      .find((row) => row.startsWith("XSRF-TOKEN="));

    if (xsrfCookie) {
      csrfToken = decodeURIComponent(xsrfCookie.substring("XSRF-TOKEN=".length));
    }
  }

  if (!csrfToken) {
    processing.value = false;
    paymentError.value =
      "توکن امنیتی پیدا نشد. فایل Blade و تنظیمات نشست Laravel را بررسی کنید.";
    return;
  }

  const form = document.createElement("form");
  form.method = "POST";
  form.action = paymentUrl;
  form.style.display = "none";

  const tokenInput = document.createElement("input");
  tokenInput.type = "hidden";
  tokenInput.name = "_token";
  tokenInput.value = csrfToken;

  form.appendChild(tokenInput);
  document.body.appendChild(form);
  form.submit();
};
</script>

<template>
  <Head :title="visit ? `اطلاعات نوبت ${visit.number}` : 'نوبت موردنظر پیدا نشد'" />

  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-800">
    <!-- هدر -->
    <header class="border-b border-slate-200 bg-white">
      <div
        class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
      >
        <a href="/" class="flex min-w-0 items-center gap-3">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-700 text-xl font-bold text-white"
          >
            د
          </div>

          <div class="min-w-0">
            <p class="truncate font-bold text-slate-900">دکتر فرهاد وفایی</p>

            <p class="mt-1 text-xs text-slate-500">سامانه نوبت‌دهی آنلاین</p>
          </div>
        </a>

        <a
          href="/"
          class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
          صفحه اصلی
        </a>
      </div>
    </header>

    <!-- محتوای اصلی -->
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
      <!-- نوبت معتبر -->
      <section
        v-if="visit"
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
      >
        <!-- عنوان صفحه -->
        <div
          class="bg-gradient-to-l from-slate-900 to-blue-800 px-5 py-7 text-white sm:px-8 sm:py-9"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm text-blue-100">اطلاعات نوبت شما</p>

              <h1 class="mt-3 text-xl font-extrabold sm:text-2xl">
                نوبت شماره {{ visit.number }}
              </h1>

              <p class="mt-3 text-sm leading-7 text-blue-100">
                اطلاعات نوبت و وضعیت پرداخت را در این صفحه مشاهده کنید.
              </p>
            </div>

            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl"
              aria-hidden="true"
            >
              ♧
            </div>
          </div>
        </div>

        <div class="space-y-7 p-5 sm:p-8">
          <div
            v-if="cancellationSuccess"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800"
            role="status"
            aria-live="polite"
          >
            {{ cancellationSuccess }}
          </div>
          <!-- وضعیت پرداخت -->
          <div
            v-if="paymentStatus"
            class="flex items-start gap-3 rounded-2xl border p-4"
            :class="paymentStatus.classes"
            role="status"
            aria-live="polite"
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/70 text-lg font-bold"
              aria-hidden="true"
            >
              {{ paymentStatus.icon }}
            </span>

            <div>
              <p class="font-bold">
                {{ paymentStatus.title }}
              </p>

              <p class="mt-1 text-sm leading-7">
                {{ paymentStatus.description }}
              </p>
            </div>
          </div>

          <!-- وضعیت نوبت -->
          <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-xs text-slate-500">وضعیت نوبت</p>
                <p class="mt-2 font-bold text-slate-900">
                  {{ visit.appointment_status_label || "در حال بررسی" }}
                </p>
              </div>
              <span
                class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200"
              >
                {{ visit.appointment_status === "completed" ? "تکمیل‌شده" : visit.appointment_status === "visited" ? "ویزیت‌شده" : visit.appointment_status === "confirmed" ? "تأییدشده" : visit.appointment_status === "cancelled" || visit.appointment_status === "canceled" ? "لغوشده" : visit.appointment_status === "contacted" ? "تماس گرفته شده" : "در انتظار" }}
              </span>
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-600">
              این وضعیت آخرین مرحله ثبت‌شده برای نوبت شماست. برای اطلاعات پرداخت، وضعیت پرداخت را جداگانه بررسی کنید.
            </p>
          </section>

          <!-- مشخصات پزشک و بیمار -->
          <section>
            <h2 class="mb-4 text-base font-bold text-slate-900">مشخصات نوبت</h2>

            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">پزشک</p>

                <p class="mt-2 font-bold">
                  {{ visit.doctor_name || "دکتر فرهاد وفایی" }}
                </p>
              </div>

              <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs text-slate-500">نام بیمار</p>

                <p class="mt-2 font-bold">
                  {{ visit.patient_name || "ثبت نشده" }}
                </p>
              </div>
            </div>
          </section>

          <!-- اطلاعات محل ویزیت -->
          <section>
            <h2 class="mb-4 text-base font-bold text-slate-900">محل ویزیت</h2>

            <div class="rounded-2xl border border-slate-200 p-4 sm:p-5">
              <div class="flex items-start gap-3">
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg text-blue-700"
                  aria-hidden="true"
                >
                  ⌖
                </div>

                <div class="min-w-0 flex-1">
                  <p class="font-bold">
                    {{ visit.clinic_name || "محل ویزیت" }}
                  </p>

                  <p class="mt-2 text-sm leading-7 text-slate-500">
                    {{ visit.clinic_address || "آدرس ثبت نشده است." }}
                  </p>

                  <p v-if="visit.clinic_phone" class="mt-3 text-sm text-slate-600">
                    تلفن:
                    <a
                      :href="`tel:${visit.clinic_phone}`"
                      class="font-semibold text-blue-700"
                      dir="ltr"
                    >
                      {{ visit.clinic_phone }}
                    </a>
                  </p>
                </div>
              </div>
            </div>
          </section>

          <!-- تاریخ و ساعت -->
          <section>
            <h2 class="mb-4 text-base font-bold text-slate-900">زمان مراجعه</h2>

            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">تاریخ مراجعه</p>

                <p class="mt-2 font-bold leading-7">
                  {{ getPersianFullDate(visit.date) }}
                </p>
              </div>

              <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-xs text-slate-500">ساعت مراجعه</p>

                <p class="mt-2 font-bold" dir="ltr">
                  {{ visit.time || "مشخص نشده" }}
                </p>
              </div>
            </div>
          </section>

          <!-- لغو نوبت توسط بیمار -->
          <section
            v-if="visit.can_cancel"
            class="rounded-2xl border border-rose-100 bg-rose-50/60 p-5"
          >
            <h2 class="font-bold text-slate-900">درخواست لغو نوبت</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">
              برای اطمینان از هویت بیمار، شماره موبایلی را که هنگام رزرو ثبت کرده‌اید وارد کنید.
              نوبت‌های دارای پرداخت موفق از این بخش قابل لغو نیستند.
            </p>
            <form class="mt-4 space-y-3" @submit.prevent="cancelAppointment">
              <label for="cancel_patient_phone" class="block text-sm font-medium text-slate-700">
                شماره موبایل ثبت‌شده
              </label>
              <input
                id="cancel_patient_phone"
                v-model="cancelForm.patient_phone"
                type="tel"
                inputmode="numeric"
                autocomplete="tel"
                maxlength="11"
                minlength="11"
                pattern="09[0-9]{9}"
                dir="ltr"
                required
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                placeholder="09123456789"
              />
              <p v-if="cancelForm.errors.patient_phone" class="text-sm text-rose-700" role="alert">
                {{ cancelForm.errors.patient_phone }}
              </p>
              <button
                type="submit"
                :disabled="cancelForm.processing || cancelForm.patient_phone.length !== 11"
                class="flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-3 font-bold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ cancelForm.processing ? "در حال لغو نوبت..." : "تأیید و لغو نوبت" }}
              </button>
            </form>
          </section>

          <!-- جابه‌جایی نوبت -->
          <section
            v-if="visit.can_reschedule"
            class="rounded-2xl border border-blue-100 bg-blue-50/50 p-5"
          >
            <h2 class="font-bold text-slate-900">تغییر زمان نوبت</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">
              می‌توانید ساعت دیگری را برای همین پزشک و همین شعبه انتخاب کنید.
              وضعیت پرداخت و مبلغ پرداخت‌شده برای نوبت حفظ می‌شود.
            </p>

            <div v-if="visit.available_slots?.length" class="mt-4">
              <form class="space-y-3" @submit.prevent="rescheduleAppointment">
                <label for="new_time_slot_id" class="block text-sm font-medium text-slate-700">
                  زمان جدید مراجعه
                </label>
                <select
                  id="new_time_slot_id"
                  v-model="rescheduleForm.new_time_slot_id"
                  required
                  class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-right focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                  <option value="" disabled>انتخاب تاریخ و ساعت جدید</option>
                  <option
                    v-for="slot in visit.available_slots"
                    :key="slot.id"
                    :value="String(slot.id)"
                  >
                    {{ getPersianFullDate(slot.date) }} — ساعت {{ slot.start_time }}
                  </option>
                </select>
                <p v-if="rescheduleForm.errors.new_time_slot_id" class="text-sm text-rose-700" role="alert">
                  {{ rescheduleForm.errors.new_time_slot_id }}
                </p>

                <label for="reschedule_patient_phone" class="block text-sm font-medium text-slate-700">
                  شماره موبایل ثبت‌شده
                </label>
                <input
                  id="reschedule_patient_phone"
                  v-model="rescheduleForm.patient_phone"
                  type="tel"
                  inputmode="numeric"
                  autocomplete="tel"
                  maxlength="11"
                  minlength="11"
                  pattern="09[0-9]{9}"
                  dir="ltr"
                  required
                  class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                  placeholder="09123456789"
                />
                <p v-if="rescheduleForm.errors.patient_phone" class="text-sm text-rose-700" role="alert">
                  {{ rescheduleForm.errors.patient_phone }}
                </p>
                <button
                  type="submit"
                  :disabled="rescheduleForm.processing || rescheduleForm.patient_phone.length !== 11 || !rescheduleForm.new_time_slot_id"
                  class="flex w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 font-bold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {{ rescheduleForm.processing ? "در حال تغییر زمان..." : "تأیید زمان جدید" }}
                </button>
              </form>
            </div>
            <p v-else class="mt-4 rounded-xl bg-white p-4 text-sm text-slate-600">
              در حال حاضر ساعت آزاد دیگری برای این شعبه وجود ندارد.
            </p>
          </section>

          <!-- مبلغ و پرداخت -->
          <section class="overflow-hidden rounded-2xl border border-blue-100">
            <div class="bg-blue-50/70 p-5 sm:p-6">
              <p class="text-sm text-slate-600">مبلغ قابل پرداخت</p>

              <div class="mt-3 flex flex-wrap items-baseline gap-2">
                <span class="text-3xl font-extrabold tracking-tight text-slate-900">
                  {{ formatPrice(visit.fee_rial) }}
                </span>

                <span class="text-sm font-medium text-slate-600"> تومان </span>
              </div>

              <p class="mt-3 text-xs leading-6 text-slate-500">
                وضعیت نهایی پرداخت پس از تأیید پاسخ درگاه بانکی مشخص می‌شود.
              </p>
            </div>

            <div class="space-y-4 p-5 sm:p-6">
              <!-- خطای پرداخت -->

              <div
                v-if="paymentError"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-7 text-red-700"
                role="alert"
              >
                {{ paymentError }}
              </div>
              <div
                v-if="ErrTxt"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-7 text-red-700"
                role="alert"
              >
                {{ ErrTxt }}
              </div>
              <!-- دکمه پرداخت -->
              <button
                v-if="canPay"
                type="button"
                :disabled="processing"
                @click="pay"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 py-4 font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-60"
              >
                <svg
                  v-if="processing"
                  class="h-5 w-5 animate-spin"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />

                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"
                  />
                </svg>

                <span>
                  {{ processing ? "در حال انتقال به درگاه..." : "پرداخت هزینه ویزیت" }}
                </span>
              </button>

              <div
                v-else-if="paid"
                class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-4 font-bold text-white"
              >
                <span aria-hidden="true">✓</span>
                پرداخت تأیید شده است
              </div>

              <p v-if="canPay" class="text-center text-xs leading-6 text-slate-500">
                با انتخاب دکمه پرداخت، به درگاه بانکی منتقل خواهید شد.
              </p>
            </div>
          </section>

          <!-- راهنما -->
          <div class="rounded-2xl bg-slate-50 p-4 text-sm leading-7 text-slate-600">
            <p class="font-bold text-slate-800">راهنمای مراجعه</p>

            <p class="mt-2">
              لطفاً تاریخ و ساعت نوبت خود را بررسی کنید و هنگام مراجعه، اطلاعات نوبت را
              همراه داشته باشید.
            </p>
          </div>

          <!-- بازگشت -->
          <a
            href="/"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3.5 font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
          >
            <svg
              class="h-5 w-5"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 19l-7-7 7-7M8 12h13"
              />
            </svg>

            بازگشت به صفحه اصلی
          </a>
        </div>
      </section>

      <!-- نوبت پیدا نشد -->
      <section
        v-else
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white text-center shadow-sm"
      >
        <div class="px-6 py-12 sm:px-10 sm:py-16">
          <div
            class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-amber-50 text-amber-600"
          >
            <svg
              class="h-10 w-10"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.6"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 9v3.75m0 3h.008M10.3 4.9 2.7 18.1A1.5 1.5 0 0 0 4 20.35h16a1.5 1.5 0 0 0 1.3-2.25L13.7 4.9a1.96 1.96 0 0 0-3.4 0Z"
              />
            </svg>
          </div>

          <h1 class="mt-6 text-2xl font-extrabold text-slate-900">
            نوبت موردنظر پیدا نشد
          </h1>

          <p class="mx-auto mt-4 max-w-md text-sm leading-8 text-slate-500">
            اطلاعات این نوبت در دسترس نیست یا لینک واردشده معتبر نیست. لطفاً از طریق صفحه
            اصلی، نوبت خود را پیگیری کنید.
          </p>

          <a
            href="/"
            class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 py-4 font-bold text-white transition hover:bg-blue-800 sm:w-auto"
          >
            بازگشت به صفحه اصلی
          </a>
        </div>
      </section>
    </div>

    <!-- فوتر -->
    <footer class="border-t border-slate-200 bg-white px-4 py-6 text-center">
      <p class="text-xs leading-6 text-slate-500">
        سامانه معرفی دکتر فرهاد وفایی و نوبت‌دهی آنلاین
      </p>

      <p class="mt-1 text-xs text-slate-400">کلیه حقوق محفوظ است.</p>
    </footer>
  </main>
</template>

<template>
  <div
    v-if="selectedSlot || lastBookedSlot"
    class="mt-8 border-t border-gray-100 pt-6"
    dir="rtl"
  >
    <!-- نوبت انتخاب‌شده -->
    <div
      v-if="selectedSlot && !lastBookedSlot"
      class="mb-4 flex items-center justify-between gap-4"
    >
      <div>
        <p class="text-xs text-gray-500">نوبت انتخاب‌شده:</p>
        <p class="font-bold text-gray-800">
          {{ getPersianFullDate(currentDateData?.date) }}
          - ساعت {{ formatTime(selectedSlot.start_time) }}
        </p>
      </div>

      <button
        type="button"
        @click="openBookingModal"
        class="rounded-xl bg-gradient-to-l from-teal-500 to-cyan-600 px-6 py-3 font-bold text-white shadow-lg transition hover:shadow-xl"
      >
        ادامه و ثبت نوبت
      </button>
    </div>

    <!-- اطلاعات نوبت ثبت‌شده -->
    <Transition name="fade">
      <div
        v-if="lastBookedSlot"
        class="rounded-2xl border-2 border-green-200 bg-green-50 p-5"
      >
        <p class="mb-2 font-bold text-green-800">نوبت با موفقیت ثبت شد.</p>
        <p class="mb-3 text-sm text-green-700">{{ successMessage }}</p>

        <div class="rounded-xl bg-white/70 p-3">
          <p class="text-sm font-bold text-gray-800">
            تاریخ: {{ getPersianFullDate(lastBookedSlot.date) }}
          </p>
          <p class="mt-1 text-sm font-bold text-gray-800">
            ساعت: {{ formatTime(lastBookedSlot.start_time) }}
          </p>
          <p v-if="lastBookedSlot.id" class="mt-2 text-xs text-gray-500">
            شماره نوبت: {{ lastBookedSlot.id }}
          </p>
        </div>

        <div class="mt-4 space-y-3">
          <button
            type="button"
            @click="continueToPayment"
            class="flex w-full items-center justify-center rounded-xl bg-gradient-to-l from-teal-500 to-cyan-600 px-4 py-3 font-bold text-white transition hover:opacity-90"
          >
            ادامه و پرداخت هزینه نوبت
          </button>

          <button
            v-if="!smsSent"
            type="button"
            :disabled="smsSending || !lastBookedSlot.id"
            @click="sendAppointmentSms"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-3 font-bold text-white transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ smsSending ? "در حال ارسال پیامک..." : "ارسال پیامک اطلاعات نوبت" }}
          </button>

          <div
            v-if="smsMessage"
            class="mt-3 rounded-xl p-3 text-sm"
            :class="smsSuccess ? 'bg-green-100 text-green-800' : 'bg-red-50 text-red-700'"
            role="status"
            aria-live="polite"
          >
            {{ smsMessage }}
          </div>

          <p v-if="!lastBookedSlot.id" class="mt-3 text-sm text-red-700">
            شناسه نوبت از سرور دریافت نشده است؛ ارسال پیامک امکان‌پذیر نیست.
          </p>
        </div>
      </div>
    </Transition>

    <!-- فرم ثبت نوبت -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showBookingForm"
          class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
          @click.self="closeModal"
        >
          <div
            class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl md:p-8"
            dir="rtl"
          >
            <div class="mb-6 flex items-center justify-between">
              <h3 class="text-xl font-bold text-gray-900">اطلاعات بیمار</h3>
              <button
                type="button"
                @click="closeModal"
                class="rounded-full p-2 hover:bg-gray-100 disabled:opacity-50"
                aria-label="بستن"
                :disabled="isSubmitting"
              >
                ✕
              </button>
            </div>

            <form @submit.prevent="submitBooking" class="space-y-4">
              <!-- نام بیمار -->
              <div>
                <label
                  for="patient_name"
                  class="mb-2 block text-sm font-medium text-gray-700"
                >
                  نام و نام خانوادگی *
                </label>
                <input
                  id="patient_name"
                  v-model.trim="bookingForm.patient_name"
                  type="text"
                  required
                  maxlength="150"
                  autocomplete="name"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-teal-500"
                  placeholder="نام و نام خانوادگی بیمار"
                />
              </div>

              <!-- موبایل -->
              <div>
                <label
                  for="patient_phone"
                  class="mb-2 block text-sm font-medium text-gray-700"
                >
                  شماره موبایل *
                </label>
                <input
                  id="patient_phone"
                  v-model="bookingForm.patient_phone"
                  type="tel"
                  required
                  maxlength="11"
                  minlength="11"
                  inputmode="numeric"
                  pattern="09[0-9]{9}"
                  autocomplete="tel"
                  dir="ltr"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-left focus:outline-none focus:ring-2 focus:ring-teal-500"
                  placeholder="09123456789"
                  @input="sanitizePhone"
                />
              </div>

              <!-- کد ملی -->
              <div>
                <label
                  for="patient_national_code"
                  class="mb-2 block text-sm font-medium text-gray-700"
                >
                  کد ملی (اختیاری)
                </label>
                <input
                  id="patient_national_code"
                  v-model="bookingForm.patient_national_code"
                  type="text"
                  maxlength="10"
                  inputmode="numeric"
                  pattern="[0-9]{10}"
                  dir="ltr"
                  class="w-full rounded-xl border border-gray-200 px-4 py-3 text-left focus:outline-none focus:ring-2 focus:ring-teal-500"
                  placeholder="1234567890"
                  @input="sanitizeNationalCode"
                />
              </div>

              <!-- دلیل مراجعه -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">
                  دلیل مراجعه (اختیاری)
                </label>

                {{ selectedVisitReason }}
                <SelectInput
                  v-model="selectedVisitReason"
                  :options="visitReasonOptions"
                  searchPlaceholder="جست‌وجو یا تایپ مقدار جدید..."
                  placeholder="انتخاب دلیل مراجعه"
                  searchable
                  clearable
                      @keydown.enter.prevent

                />

                <div v-if="isNewVisitReason" class="mt-3">
                  <label
                    for="new_visit_reason"
                    class="mb-2 block text-sm font-medium text-gray-700"
                  >
                    عنوان دلیل جدید
                  </label>
                  <input
                    id="new_visit_reason"
                    v-model.trim="newVisitReason"
                    type="text"
                    maxlength="150"
                    autocomplete="off"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500"
                    placeholder="دلیل مراجعه جدید را وارد کنید..."
                  />
                </div>

                <p v-if="reasonsError" class="mt-1 text-sm text-red-600">
                  {{ reasonsError }}
                </p>
              </div>

              <!-- توضیحات -->
              <div>
                <label
                  for="description"
                  class="mb-2 block text-sm font-medium text-gray-700"
                >
                  توضیحات (اختیاری)
                </label>
                <textarea
                  id="description"
                  v-model.trim="bookingForm.description"
                  rows="3"
                  maxlength="2000"
                  class="w-full resize-none rounded-xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-teal-500"
                  placeholder="توضیحات تکمیلی..."
                ></textarea>
              </div>

              <!-- خلاصه -->
              <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                <p class="mb-2 font-medium text-teal-800">خلاصه نوبت</p>
                <p class="text-xs text-teal-700">
                  {{ getPersianFullDate(currentDateData?.date) }}
                  - ساعت {{ formatTime(selectedSlot?.start_time) }}
                </p>
                <p v-if="selectedReasonLabel" class="mt-2 text-xs text-teal-700">
                  دلیل مراجعه: {{ selectedReasonLabel }}
                </p>
              </div>

              <!-- خطا -->
              <div
                v-if="errorMessage"
                class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                role="alert"
              >
                {{ errorMessage }}
              </div>

              <!-- ثبت -->
              <div v-if="isRedirectingToPayment">
                <div
                  class="flex items-center justify-center gap-2 py-4 text-blue-700"
                  role="status"
                >
                  <span>
                    نوبت با موفقیت تا تکمیل پرداخت وجه قفل شده است
                    <br />
                    در صورت عدم پرداخت نوبت آزاد خواهد شد
                  </span>
                </div>
                <svg
                  class="h-5 w-5 animate-spin"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
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
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  />
                </svg>
                <span> در حال انتقال به پیش نمایش پرداخت... </span>
              </div>
              <button
                v-else
                type="submit"
                :disabled="isSubmitting || !selectedSlot"
                class="w-full rounded-xl bg-gradient-to-l from-teal-500 to-cyan-600 py-3.5 font-bold text-white shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ isSubmitting ? "در حال ثبت نوبت..." : "ثبت نهایی و ادامه به پرداخت" }}
              </button>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import SelectInput from "@/Components/Portal/SelectInput.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  selectedSlot: {
    type: Object,
    default: null,
  },
  currentDateData: {
    type: Object,
    required: true,
  },
  // آرایه دلایل قبلی؛ هر مورد می‌تواند { id, title } باشد.
  visitReasons: {
    type: Object,
    default: null,
  },
  redirectToPayment: {
    type: Boolean,
    default: true,
  },
});
// console.log("visitReasons from backend:", props.visitReasons);

const emit = defineEmits(["slot-booked", "slot-cleared"]);

const isRedirectingToPayment = ref(false);
const showBookingForm = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref("");
const reasonsError = ref("");
const successMessage = ref("");
const lastBookedSlot = ref(null);
const paymentAppointmentId = ref(null);

const smsSending = ref(false);
const smsSent = ref(false);
const smsMessage = ref("");
const smsSuccess = ref(false);

const bookingForm = ref({
  patient_name: "",
  patient_phone: "",
  patient_national_code: "",
  description: "",
});

const normalizedVisitReasons = computed(() =>
  (Array.isArray(props.visitReasons) ? props.visitReasons : [])
    .map((reason) => ({
      id: reason?.id ?? reason?.value,
      name: String(reason?.name ?? reason?.title ?? reason?.label ?? "").trim(),
    }))
    .filter((reason) => reason.id !== null && reason.id !== undefined && reason.name)
);

const NEW_REASON_VALUE = "__new_visit_reason__";
const selectedVisitReason = ref(null);
const newVisitReason = ref("");

const visitReasonOptions = computed(() => [
  ...normalizedVisitReasons.value.map((reason) => ({
    value: String(reason.id),
    label: reason.name,
  })),
]);

const isNewVisitReason = computed(
  () => String(selectedVisitReason.value ?? "") === NEW_REASON_VALUE
);

const selectedReasonLabel = computed(() => {
  if (isNewVisitReason.value) return newVisitReason.value.trim();
  const selected = normalizedVisitReasons.value.find(
    (reason) => String(reason.id) === String(selectedVisitReason.value ?? "")
  );
  return selected?.name ?? "";
});

function getPersianFullDate(dateStr) {
  if (!dateStr) return "";

  // تاریخ YYYY-MM-DD را به‌صورت محلی می‌سازیم تا از جابه‌جایی روز به‌علت timezone جلوگیری شود.
  const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  const date = match
    ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12)
    : new Date(dateStr);

  if (Number.isNaN(date.getTime())) return String(dateStr);

  return new Intl.DateTimeFormat("fa-IR", {
    year: "numeric",
    month: "long",
    day: "numeric",
    weekday: "long",
  }).format(date);
}

function formatTime(timeStr) {
  if (!timeStr) return "";
  return String(timeStr)
    .substring(0, 5)
    .replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[Number(digit)]);
}

function toEnglishDigits(value) {
  return String(value ?? "").replace(/[۰-۹٠-٩]/g, (digit) => {
    const fa = "۰۱۲۳۴۵۶۷۸۹";
    const ar = "٠١٢٣٤٥٦٧٨٩";
    if (fa.includes(digit)) return String(fa.indexOf(digit));
    return String(ar.indexOf(digit));
  });
}

function sanitizePhone() {
  bookingForm.value.patient_phone = toEnglishDigits(bookingForm.value.patient_phone)
    .replace(/\D/g, "")
    .slice(0, 11);
}

function sanitizeNationalCode() {
  bookingForm.value.patient_national_code = toEnglishDigits(
    bookingForm.value.patient_national_code
  )
    .replace(/\D/g, "")
    .slice(0, 10);
}

function openBookingModal() {
  if (!props.selectedSlot || lastBookedSlot.value) return;
  errorMessage.value = "";
  reasonsError.value = "";
  showBookingForm.value = true;
}

function closeModal() {
  if (isSubmitting.value) return;
  showBookingForm.value = false;
  errorMessage.value = "";
  reasonsError.value = "";
}

function resetSmsState() {
  smsSending.value = false;
  smsSent.value = false;
  smsMessage.value = "";
  smsSuccess.value = false;
}

function redirectToPayment(appointmentId) {
  const paymentUrl = route("payment.pay", {
    visit_number: appointmentId,
  });

  const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  if (!csrfToken) {
    errorMessage.value = "توکن امنیتی صفحه پیدا نشد.";
    return;
  }

  // فعال‌کردن وضعیت انتظار پیش از انتقال
  isRedirectingToPayment.value = true;

  const form = document.createElement("form");
  form.method = "POST";
  form.action = paymentUrl;

  const csrfInput = document.createElement("input");
  csrfInput.type = "hidden";
  csrfInput.name = "_token";
  csrfInput.value = csrfToken;

  form.appendChild(csrfInput);
  document.body.appendChild(form);
  form.submit();
}

function submitBooking() {
  if (!props.selectedSlot || isSubmitting.value) return;

  errorMessage.value = "";
  reasonsError.value = "";

  const phone = toEnglishDigits(bookingForm.value.patient_phone).trim();
  const nationalCode = toEnglishDigits(bookingForm.value.patient_national_code).trim();

  const selectedValue = selectedVisitReason.value;

  // پیدا کردن دلیل انتخاب‌شده از میان دلایل موجود
  const matchedReason = normalizedVisitReasons.value.find(
    (reason) => String(reason.id) === String(selectedValue ?? "")
  );

  // اگر دلیل انتخاب نشده باشد، هیچ دلیلی ارسال نمی‌شود
  const hasSelectedReason =
    selectedValue !== null &&
    selectedValue !== undefined &&
    String(selectedValue).trim() !== "";

  const isExistingReason = hasSelectedReason && Boolean(matchedReason);

  // فقط در صورتی متن دلیل جدید ارسال می‌شود که کاربر مقداری وارد کرده باشد
  const newReasonText =
    hasSelectedReason && !isExistingReason ? String(selectedValue).trim() : "";

  if (!bookingForm.value.patient_name.trim()) {
    errorMessage.value = "نام و نام خانوادگی بیمار را وارد کنید.";
    return;
  }

  if (!/^09\d{9}$/.test(phone)) {
    errorMessage.value = "شماره موبایل باید ۱۱ رقم و با 09 شروع شود.";
    return;
  }

  if (nationalCode && !/^\d{10}$/.test(nationalCode)) {
    errorMessage.value = "کد ملی باید دقیقاً ۱۰ رقم باشد.";
    return;
  }

  if (newReasonText && newReasonText.length > 150) {
    reasonsError.value = "دلیل مراجعه نباید بیشتر از ۱۵۰ کاراکتر باشد.";
    return;
  }

  const slotId = props.selectedSlot.id;
  const selectedDate = props.currentDateData?.date;
  const selectedTime = props.selectedSlot.start_time;

  const payload = {
    time_slot_id: slotId,
    patient_name: bookingForm.value.patient_name.trim(),
    patient_phone: phone,
    patient_national_code: nationalCode || null,

    // دلیل مراجعه اختیاری است
    visit_reason_id: isExistingReason ? matchedReason.id : null,
    new_visit_reason: newReasonText || null,

    description: bookingForm.value.description.trim() || null,
  };

  console.log("Booking payload:", payload);
  // return;
  isSubmitting.value = true;

  router.post(route("appointments.book"), payload, {
    preserveScroll: true,

    onError(errors) {
      const firstError = Object.values(errors ?? {}).find(
        (value) => typeof value === "string" && value.trim()
      );

      errorMessage.value = firstError || "اطلاعات واردشده معتبر نیست.";

      if (errors?.visit_reason_id || errors?.new_visit_reason) {
        reasonsError.value = errors.visit_reason_id || errors.new_visit_reason;
      }
    },

    onSuccess(page) {
      const flash = page?.props?.flash ?? {};

      const appointment = flash.appointment ?? page?.props?.appointment ?? null;

      const appointmentId = flash.appointment_id ?? appointment?.id ?? null;

      if (!appointmentId) {
        errorMessage.value = "نوبت ثبت شد، اما شناسه نوبت از سرور دریافت نشد.";
        return;
      }

      // صفحه اصلی سایت: انتقال به پرداخت
      if (props.redirectToPayment) {
        redirectToPayment(appointmentId);
        return;
      }

      // پنل منشی: ثبت نوبت بدون انتقال به پرداخت
      successMessage.value = "نوبت با موفقیت ثبت شد.";

      lastBookedSlot.value = {
        id: appointmentId,
        date: props.currentDateData?.date,
        start_time: props.selectedSlot?.start_time,
      };

      paymentAppointmentId.value = appointmentId;
      showBookingForm.value = false;
      resetSmsState();

      emit("slot-booked", lastBookedSlot.value);
    },

    onFinish() {
      isSubmitting.value = false;
    },
  });
}

function continueToPayment() {
  if (!paymentAppointmentId.value) {
    errorMessage.value = "شناسه نوبت برای ادامه پرداخت در دسترس نیست.";
    return;
  }
  redirectToPayment(paymentAppointmentId.value);
}

async function sendAppointmentSms() {
  const appointmentId = lastBookedSlot.value?.id;
  if (!appointmentId || smsSending.value || smsSent.value) return;

  smsSending.value = true;
  smsMessage.value = "";
  smsSuccess.value = false;

  try {
    const response = await axios.post(
      route("sms.visit", { visit_number: appointmentId }),
      {},
      { headers: { Accept: "application/json" } }
    );

    if (response.data?.success !== true) {
      throw new Error(response.data?.message || "ارسال پیامک انجام نشد.");
    }

    smsSuccess.value = true;
    smsSent.value = true;
    smsMessage.value = response.data.message || "پیامک با موفقیت ارسال شد.";
  } catch (error) {
    smsSuccess.value = false;
    smsMessage.value =
      error.response?.data?.message ||
      error.message ||
      "ارسال پیامک انجام نشد. لطفاً مجدداً تلاش کنید.";
  } finally {
    smsSending.value = false;
  }
}

watch(
  () => props.selectedSlot,
  () => {
    errorMessage.value = "";
    reasonsError.value = "";
  }
);

watch(selectedVisitReason, () => {
  reasonsError.value = "";
  if (!isNewVisitReason.value) newVisitReason.value = "";
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>

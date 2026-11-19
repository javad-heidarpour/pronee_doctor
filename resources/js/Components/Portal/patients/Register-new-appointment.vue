<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6"
        dir="rtl"
        @click.self="closeModal"
      >
        <div
          class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:max-h-[92vh]"
          role="dialog"
          aria-modal="true"
          aria-labelledby="appointment-modal-title"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between bg-gradient-to-l from-rose-800 to-red-500 px-5 py-4 sm:px-7"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15"
              >
                <svg
                  class="h-6 w-6 text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm4 10h2m2 0h2"
                  />
                </svg>
              </div>

              <div>
                <h2
                  id="appointment-modal-title"
                  class="text-lg font-bold text-white sm:text-xl"
                >
                  ثبت نوبت جدید
                </h2>
                <p class="mt-1 text-xs text-rose-100">ثبت نوبت بیمار توسط منشی مطب</p>
              </div>
            </div>

            <button
              type="button"
              @click="closeModal"
              class="rounded-xl p-2 text-white transition hover:bg-white/15"
              aria-label="بستن"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <form class="min-h-0 overflow-y-auto overscroll-contain" @submit.prevent="submitForm">
            <div class="space-y-7 p-5 sm:p-7">
              <!-- Appointment selection -->
              <section>
                <div class="mb-4 flex items-center gap-2">
                  <span
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-sm font-bold text-rose-700"
                  >
                    ۱
                  </span>
                  <h3 class="font-bold text-gray-800">انتخاب مطب و زمان نوبت</h3>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-bold text-gray-700">
                    مطب <span class="text-red-500">*</span>
                  </label>

                  <SelectInput
                    v-model="form.clinic_id"
                    :options="clinicOptions"
                    placeholder="انتخاب مطب"
                    :clearable="true"
                    searchable

                  />

                  <p v-if="errors.clinic_id" class="mt-1 text-xs text-red-600">
                    {{ errors.clinic_id }}
                  </p>
                </div>

                <!-- Available times -->
                <div class="mt-5">
                  <label class="mb-3 block text-sm font-bold text-gray-700">
                    ساعت‌های آزاد <span class="text-red-500">*</span>
                  </label>

                  <div
                    v-if="!form.clinic_id"
                    class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center"
                  >
                    <div class="mb-2 text-2xl">📅</div>
                    <p class="text-sm text-gray-500">ابتدا مطب را انتخاب کنید.</p>
                  </div>

                  <div
                    v-else-if="loadingSlots"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-6 text-center"
                  >
                    <div
                      class="mx-auto mb-2 h-6 w-6 animate-spin rounded-full border-2 border-rose-600 border-t-transparent"
                    ></div>
                    <p class="text-sm text-gray-500">در حال دریافت ساعت‌های آزاد...</p>
                  </div>

                  <div
                    v-else-if="availableSlots.length === 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-5 text-center"
                  >
                    <p class="font-bold text-amber-800">
                      زمان آزادی برای این مطب وجود ندارد.
                    </p>
                    <p class="mt-1 text-xs text-amber-700">
                      لطفاً بعداً دوباره بررسی کنید.
                    </p>
                  </div>

                  <div v-else>
                    <SelectInput
                      v-model="form.time_slot_id"
                      :options="slotOptions"
                      placeholder="انتخاب ساعت آزاد"
                      :clearable="true"
                      searchable

                    />

                    <p v-if="errors.time_slot_id" class="mt-1 text-xs text-red-600">
                      {{ errors.time_slot_id }}
                    </p>

                    <p class="mt-2 text-xs text-gray-500">
                      تاریخ و ساعت نوبت بر اساس زمان انتخاب‌شده در سامانه ثبت می‌شود.
                    </p>
                  </div>
                </div>

                <!-- Selected appointment summary -->
                <div
                  v-if="selectedSlot"
                  class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm"
                >
                  <div class="flex items-center gap-2 text-teal-800">
                    <span>📍</span>
                    <span>{{ selectedClinicName }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-teal-800">
                    <span>📅</span>
                    <span>{{ formatAppointmentDate(selectedDateLabel) }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-teal-800">
                    <span>🕒</span>
                    <span>{{ selectedSlotLabel }}</span>
                  </div>
                </div>
              </section>

              <div class="border-t border-gray-100"></div>

              <!-- Patient information -->
              <section v-if="selectedSlot">
                <div class="mb-4 flex items-center gap-2">
                  <span
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-sm font-bold text-rose-700"
                  >
                    ۲
                  </span>
                  <h3 class="font-bold text-gray-800">اطلاعات بیمار</h3>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <!-- Patient name -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      نام و نام خانوادگی
                      <span class="text-red-500">*</span>
                    </label>

                    <input
                      v-model.trim="form.patient_name"
                      type="text"
                      autocomplete="name"
                      placeholder="نام کامل بیمار"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                    />

                    <p v-if="errors.patient_name" class="mt-1 text-xs text-red-600">
                      {{ errors.patient_name }}
                    </p>
                  </div>

                  <!-- Patient phone -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      شماره تلفن همراه
                      <span class="text-red-500">*</span>
                    </label>

                    <input
                      v-model.trim="form.patient_phone"
                      type="tel"
                      inputmode="numeric"
                      maxlength="11"
                      placeholder="09123456789"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                    />

                    <p v-if="errors.patient_phone" class="mt-1 text-xs text-red-600">
                      {{ errors.patient_phone }}
                    </p>
                  </div>

                  <!-- National code -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      کد ملی
                    </label>

                    <input
                      v-model.trim="form.patient_national_code"
                      type="text"
                      inputmode="numeric"
                      maxlength="10"
                      placeholder="کد ملی بیمار"
                      class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                    />

                    <p
                      v-if="errors.patient_national_code"
                      class="mt-1 text-xs text-red-600"
                    >
                      {{ errors.patient_national_code }}
                    </p>
                  </div>
                  <!-- Visit reason -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      دلیل مراجعه
                    </label>

                    <SelectInput
                      v-model="selectedVisitReason"
                      :options="visitReasonOptions"
                      placeholder="انتخاب دلیل مراجعه"
                      searchPlaceholder="جست‌وجو یا تایپ مقدار جدید..."
                      :clearable="true"
                      searchable

                    />

                    <p v-if="errors.visit_reason_id" class="mt-1 text-xs text-red-600">
                      {{ errors.visit_reason_id }}
                    </p>

                    <p v-if="errors.new_visit_reason" class="mt-1 text-xs text-red-600">
                      {{ errors.new_visit_reason }}
                    </p>
                  </div>

                  <!-- Description -->
                  <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      توضیحات نوبت
                    </label>

                    <textarea
                      v-model.trim="form.description"
                      rows="3"
                      maxlength="1000"
                      placeholder="توضیحات تکمیلی درباره مراجعه بیمار..."
                      class="w-full resize-y rounded-xl border border-gray-200 px-4 py-3 text-sm leading-6 outline-none transition focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
                    ></textarea>

                    <div class="mt-1 text-left text-xs text-gray-400">
                      {{ form.description.length }} / ۱۰۰۰
                    </div>

                    <p v-if="errors.description" class="mt-1 text-xs text-red-600">
                      {{ errors.description }}
                    </p>
                  </div>
                </div>
              </section>

              <!-- General error -->
              <div
                v-if="errorMessage"
                class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                role="alert"
                aria-live="polite"
              >
                {{ errorMessage }}
              </div>
            </div>

            <!-- Footer -->
            <div
              class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7"
            >
              <button
                type="button"
                @click="closeModal"
                :disabled="submitting"
                class="rounded-xl border border-gray-200 bg-white px-6 py-3 font-bold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
              >
                انصراف
              </button>

              <button
                v-if="selectedSlot"
                type="submit"
                :disabled="!canSubmit || submitting"
                class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-rose-800 to-red-500 px-7 py-3 font-bold text-white shadow-md transition hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
              >
                <svg
                  v-if="submitting"
                  class="h-5 w-5 animate-spin"
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
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                  />
                </svg>

                {{ submitting ? "در حال ثبت نوبت..." : "ثبت نوبت بیمار" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import axios from "axios";
import SelectInput from "@/Components/Portal/SelectInput.vue";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  clinics: {
    type: Array,
    default: () => [],
  },
  availableSlots: {
    type: Array,
    default: () => [],
  },
  visitReasons: {
    type: Array,
    default: () => [],
  },
  loadingSlots: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "load-slots", "success"]);

const submitting = ref(false);
const errorMessage = ref("");
const errors = reactive({});
const formatAppointmentDate = (date) => {
  if (!date) return "";

  const parsedDate = new Date(`${String(date).slice(0, 10)}T12:00:00`);

  if (Number.isNaN(parsedDate.getTime())) return date;

  const weekday = new Intl.DateTimeFormat("fa-IR", {
    weekday: "long",
  }).format(parsedDate);

  const jalaliDate = new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(parsedDate);

  return `${weekday}، ${jalaliDate}`;
};
const form = reactive({
  clinic_id: "",
  time_slot_id: "",
  patient_name: "",
  patient_phone: "",
  patient_national_code: "",
  visit_reason_id: "",
  description: "",
});
const selectedVisitReason = ref("");

const matchedReason = computed(() => {
  const selected = String(selectedVisitReason.value ?? "").trim();

  return props.visitReasons.find((reason) => String(reason.id) === selected) ?? null;
});

const isExistingReason = computed(() => Boolean(matchedReason.value));

const newReasonText = computed(() => {
  const selected = String(selectedVisitReason.value ?? "").trim();

  return selected && !matchedReason.value ? selected : "";
});
const clinicOptions = computed(() =>
  props.clinics.map((clinic) => ({
    value: clinic.id,
    label: clinic.name,
  }))
);

const slotOptions = computed(() =>
  props.availableSlots.map((slot) => {
    const start = String(slot.start_time ?? slot.start ?? "").slice(0, 5);
    const end = String(slot.end_time ?? slot.end ?? "").slice(0, 5);
    const date = slot.date ? ` — ${formatAppointmentDate(slot.date)}` : "";

    return {
      value: slot.id,
      label: `${date} ${start}${end ? ` تا ${end}` : ""}`.trim(),
    };
  })
);
const visitReasonOptions = computed(() =>
  props.visitReasons.map((reason) => ({
    value: String(reason.id),
    label: reason.title ?? reason.name ?? reason.label ?? "",
  }))
);

const selectedClinicName = computed(() => {
  const clinic = props.clinics.find((item) => String(item.id) === String(form.clinic_id));

  return clinic?.name ?? "";
});

const selectedSlot = computed(() =>
  props.availableSlots.find((slot) => String(slot.id) === String(form.time_slot_id))
);

const selectedSlotLabel = computed(() => {
  const slot = selectedSlot.value;

  if (!slot) return "";

  const start = String(slot.start_time ?? slot.start ?? "").slice(0, 5);
  const end = String(slot.end_time ?? slot.end ?? "").slice(0, 5);

  return end ? `${start} تا ${end}` : start;
});

const selectedDateLabel = computed(() => {
  const date = selectedSlot.value?.date;

  if (!date) return "";

  return String(date).slice(0, 10);
});

const canSubmit = computed(() =>
  Boolean(
    form.clinic_id &&
      form.time_slot_id &&
      form.patient_name.trim() &&
      form.patient_phone.trim()
  )
);

function clearErrors() {
  Object.keys(errors).forEach((key) => {
    delete errors[key];
  });

  errorMessage.value = "";
}

watch(
  () => form.clinic_id,
  (clinicId) => {
    form.time_slot_id = "";
    clearErrors();

    if (clinicId) {
      emit("load-slots", {
        clinic_id: clinicId,
      });
    }
  }
);

watch(
  () => form.time_slot_id,
  () => {
    if (errors.time_slot_id) {
      delete errors.time_slot_id;
    }

    errorMessage.value = "";
  }
);

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) return;

    if (!submitting.value) {
      resetForm();
    }
  }
);

function closeModal() {
  if (submitting.value) return;

  emit("update:modelValue", false);
}

function resetForm() {
  form.clinic_id = "";
  form.time_slot_id = "";
  form.patient_name = "";
  form.patient_phone = "";
  form.patient_national_code = "";
  form.description = "";
  selectedVisitReason.value = "";
  clearErrors();
}

async function submitForm() {
  if (!canSubmit.value || submitting.value) return;

  submitting.value = true;
  clearErrors();

  try {
    const response = await axios.post(
      route("portal.patients.store"),
      {
        time_slot_id: form.time_slot_id,
        patient_name: form.patient_name.trim(),
        patient_phone: form.patient_phone.trim(),
        patient_national_code: form.patient_national_code.trim() || null,

        // دلیل موجود
        visit_reason_id: matchedReason.value?.id ?? null,

        // دلیل جدید تایپ‌شده
        new_visit_reason: newReasonText.value || null,

        description: form.description.trim() || null,
      },
      {
        headers: {
          Accept: "application/json",
        },
      }
    );

    if (response.data?.success) {
      emit("success", response.data);
      emit("update:modelValue", false);
      resetForm();
      return;
    }

    errorMessage.value = response.data?.message || "ثبت نوبت انجام نشد.";
  } catch (error) {
    console.log("422 details:", JSON.stringify(error.response?.data, null, 2));

    console.error("Appointment registration failed:", error);

    errorMessage.value =
      error.response?.data?.message || error.message || "خطایی هنگام ثبت نوبت رخ داد.";
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
  transform: translateY(12px) scale(0.98);
}
</style>

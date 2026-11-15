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
          class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
          role="dialog"
          aria-modal="true"
          aria-labelledby="appointment-description-modal-title"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between bg-gradient-to-l from-teal-600 to-cyan-600 px-5 py-4 sm:px-7"
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
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-5-7l2-2a2.1 2.1 0 013 3l-8 8-4 1 1-4 2-2"
                  />
                </svg>
              </div>

              <div>
                <h2
                  id="appointment-description-modal-title"
                  class="text-lg font-bold text-white sm:text-xl"
                >
                  اطلاعات تکمیلی ویزیت
                </h2>

                <p class="mt-1 text-xs text-teal-100">
                  ثبت اطلاعات عمل جراحی و نسخه دارویی
                </p>
              </div>
            </div>

            <button
              type="button"
              @click="closeModal"
              :disabled="processing"
              class="rounded-xl p-2 text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50"
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
          <form class="overflow-y-auto" @submit.prevent="submitForm">
            <div class="space-y-7 p-5 sm:p-7">
              <!-- Appointment information -->
              <section>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                  <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">بیمار:</span>

                      <span class="font-bold text-gray-800">
                        {{ appointment?.patient_name || "-" }}
                      </span>
                    </div>

                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">تاریخ نوبت:</span>

                      <span class="text-gray-700">
                        {{ toPersianDate(appointment?.appointment_date) }}
                      </span>
                    </div>

                    <div class="flex items-center gap-2">
                      <span class="text-gray-500">ساعت:</span>

                      <span class="text-gray-700">
                        {{ appointment?.appointment_time?.substring(0, 5) || "-" }}
                      </span>
                    </div>
                  </div>
                </div>
              </section>

              <div class="border-t border-gray-100"></div>

              <!-- Surgery information -->
              <section>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <!-- Surgery type -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      نوع عمل جراحی
                    </label>

                    <SelectInput
                      v-model="selectedSurgeryType"
                      :options="surgeryTypeOptions"
                      placeholder="انتخاب نوع عمل جراحی"
                      searchPlaceholder="جست‌وجو یا تایپ مقدار جدید..."
                      :clearable="true"
                      searchable
                      @keydown.enter.prevent
                    />
                    <p v-if="errors.surgery_type_id" class="mt-1 text-xs text-red-600">
                      {{ errors.surgery_type_id }}
                    </p>

                    <p v-if="errors.new_surgery_type" class="mt-1 text-xs text-red-600">
                      {{ errors.new_surgery_type }}
                    </p>

                    <p class="mt-2 text-xs text-gray-400">
                      در صورت نبودن عنوان موردنظر، نام عمل را تایپ کنید.
                    </p>
                  </div>

                  <!-- Surgery date -->
                  <div>
                    <label class="mb-2 block text-sm font-bold text-gray-700">
                      تاریخ عمل جراحی
                    </label>

                    <JalaliDatePicker
                      v-model="surgeryDate"
                      placeholder="انتخاب تاریخ عمل"
                    />

                    <p v-if="errors.surgery_date" class="mt-1 text-xs text-red-600">
                      {{ errors.surgery_date }}
                    </p>
                  </div>
                </div>
              </section>

              <div class="border-t border-gray-100"></div>

              <!-- Description / prescription -->
              <section>
                <div>
                  <label class="mb-2 block text-sm font-bold text-gray-700">
                    نسخه دارویی / توضیحات تکمیلی
                  </label>

                  <textarea
                    ref="descriptionTextarea"
                    v-model.trim="form.description"
                    rows="4"
                    maxlength="1000"
                    placeholder="نسخه دارویی یا توضیحات تکمیلی مربوط به این نوبت را وارد کنید..."
                    class="w-full resize-y rounded-xl border border-gray-200 px-4 py-3 text-sm leading-6 outline-none transition focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                  ></textarea>

                  <div class="mt-1 flex items-center justify-between">
                    <p class="text-xs text-gray-400">حداکثر ۱۰۰۰ کاراکتر</p>
                    <p class="text-xs text-gray-400">
                      بعد از ذخیره این صفحه وضعیت بیمار به ویزیت شده تغییر می کند
                    </p>

                    <p
                      class="text-xs"
                      :class="
                        form.description.length > 900 ? 'text-red-500' : 'text-gray-400'
                      "
                    >
                      {{ form.description.length }} / ۱۰۰۰
                    </p>
                  </div>

                  <p v-if="errors.description" class="mt-1 text-xs text-red-600">
                    {{ errors.description }}
                  </p>
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

              <!-- Success message -->
              <div
                v-if="successMessage"
                class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700"
                role="status"
                aria-live="polite"
              >
                {{ successMessage }}
              </div>
            </div>

            <!-- Footer -->
            <div
              class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-7"
            >
              <button
                type="button"
                @click="closeModal"
                :disabled="processing"
                class="rounded-xl border border-gray-200 bg-white px-6 py-3 font-bold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
              >
                انصراف
              </button>

              <button
                type="submit"
                :disabled="processing || !appointment?.id"
                class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-teal-600 to-cyan-600 px-7 py-3 font-bold text-white shadow-md transition hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
              >
                <svg
                  v-if="processing"
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

                {{ processing ? "در حال ذخیره اطلاعات..." : "ذخیره اطلاعات ویزیت" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from "vue";
import { router ,usePage} from "@inertiajs/vue3";

import SelectInput from "@/Components/Portal/SelectInput.vue";
import JalaliDatePicker from "@/Components/Portal/JalaliDatePicker.vue";

import { toPersianDate } from "@/utils/dateUtils";

const page = usePage();

const userRole = computed(() => page.props.auth?.user?.role);
/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },

  appointment: {
    type: Object,
    default: null,
  },

  surgeryTypes: {
    type: Array,
    default: () => [],
  },
});

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits(["update:modelValue", "saved"]);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const processing = ref(false);

const errorMessage = ref("");
const successMessage = ref("");

const descriptionTextarea = ref(null);

const errors = reactive({});

const form = reactive({
  description: "",
});

const selectedSurgeryType = ref("");

const surgeryDate = ref("");

/*
|--------------------------------------------------------------------------
| Surgery type options
|--------------------------------------------------------------------------
*/

const surgeryTypeOptions = computed(() =>
  (props.surgeryTypes || []).map((item) => ({
    value: String(item.id),
    label: item.title ?? item.name ?? item.label ?? "",
  }))
);

/*
|--------------------------------------------------------------------------
| Match selected surgery type
|--------------------------------------------------------------------------
|
| اگر مقدار انتخاب‌شده با ID یکی از انواع عمل موجود مطابقت داشته باشد،
| آن رکورد موجود است.
|
*/

const matchedSurgeryType = computed(() => {
  const selected = String(selectedSurgeryType.value ?? "").trim();

  return (props.surgeryTypes || []).find((item) => String(item.id) === selected) ?? null;
});

/*
|--------------------------------------------------------------------------
| New surgery type text
|--------------------------------------------------------------------------
|
| اگر مقدار انتخاب‌شده ID نباشد، آن را عنوان جدید در نظر می‌گیریم.
|
*/

const newSurgeryTypeText = computed(() => {
  const selected = String(selectedSurgeryType.value ?? "").trim();

  return selected && !matchedSurgeryType.value ? selected : "";
});

/*
|--------------------------------------------------------------------------
| Error handling
|--------------------------------------------------------------------------
*/

function clearErrors() {
  Object.keys(errors).forEach((key) => {
    delete errors[key];
  });

  errorMessage.value = "";
}

function setErrors(serverErrors = {}) {
  clearErrors();

  Object.entries(serverErrors).forEach(([key, value]) => {
    errors[key] = Array.isArray(value) ? value[0] : value;
  });
}

/*
|--------------------------------------------------------------------------
| Clear related validation errors when fields change
|--------------------------------------------------------------------------
*/

watch(selectedSurgeryType, () => {
  delete errors.surgery_type_id;
  delete errors.new_surgery_type;

  errorMessage.value = "";
});

watch(surgeryDate, () => {
  delete errors.surgery_date;
});

watch(
  () => form.description,
  () => {
    delete errors.description;
  }
);

/*
|--------------------------------------------------------------------------
| Reset form
|--------------------------------------------------------------------------
*/

function resetForm() {
  form.description = "";

  selectedSurgeryType.value = "";

  surgeryDate.value = "";

  successMessage.value = "";

  clearErrors();
}

/*
|--------------------------------------------------------------------------
| Initialize form
|--------------------------------------------------------------------------
*/

function initializeForm(appointment) {
  resetForm();

  if (!appointment) return;

  form.description = appointment.description || "";

  /*
   * مقداردهی نوع عمل:
   * ابتدا ID رابطه را بررسی می‌کنیم.
   * سپس surgery_type_id را بررسی می‌کنیم.
   * در نهایت عنوان را برای حالت متنی در نظر می‌گیریم.
   */

  selectedSurgeryType.value = appointment.surgery_type?.id
    ? String(appointment.surgery_type.id)
    : appointment.surgery_type_id
    ? String(appointment.surgery_type_id)
    : appointment.surgery_type?.title || "";

  surgeryDate.value = appointment.surgery_date || "";

  nextTick(() => {
    descriptionTextarea.value?.focus();
  });
}

/*
|--------------------------------------------------------------------------
| Watch modal visibility
|--------------------------------------------------------------------------
*/

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      initializeForm(props.appointment);
    } else if (!processing.value) {
      resetForm();
    }
  }
);

/*
|--------------------------------------------------------------------------
| Watch appointment changes
|--------------------------------------------------------------------------
*/

watch(
  () => props.appointment,
  (appointment) => {
    if (props.modelValue && appointment) {
      initializeForm(appointment);
    }
  }
);

/*
|--------------------------------------------------------------------------
| Close modal
|--------------------------------------------------------------------------
*/

function closeModal() {
  if (processing.value) return;

  emit("update:modelValue", false);
}

/*
|--------------------------------------------------------------------------
| Submit appointment information
|--------------------------------------------------------------------------
*/

function submitForm() {
  console.log(userRole.value);

  
  if (userRole.value != 'doctor') {
    return;
  }
  if (!props.appointment?.id || processing.value) {
    return;
  }

  processing.value = true;

  successMessage.value = "";

  clearErrors();

  /*
   * Payload
   *
   * نوع عمل موجود:
   * surgery_type_id = ID
   * new_surgery_type = null
   *
   * نوع عمل جدید:
   * surgery_type_id = null
   * new_surgery_type = عنوان متنی
   */

  const payload = {
    description: form.description.trim() || null,

    surgery_date: surgeryDate.value || null,

    surgery_type_id: matchedSurgeryType.value?.id ?? null,

    new_surgery_type: newSurgeryTypeText.value || null,
  };

  router.put(route("portal.appointments.description", props.appointment.id), payload, {
    preserveScroll: true,
    preserveState: true,

    onSuccess: () => {
      successMessage.value = "اطلاعات نوبت با موفقیت ذخیره شد.";

      emit("saved", props.appointment.id);

      setTimeout(() => {
        emit("update:modelValue", false);
      }, 1200);
    },

    onError: (serverErrors) => {
      setErrors(serverErrors);

      errorMessage.value =
        "ذخیره اطلاعات با خطا مواجه شد. لطفاً موارد مشخص‌شده را بررسی کنید.";

      console.error("Save appointment information error:", serverErrors);
    },

    onFinish: () => {
      processing.value = false;
    },
  });
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

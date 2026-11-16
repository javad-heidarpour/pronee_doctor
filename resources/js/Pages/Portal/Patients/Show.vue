<template>
  <PortalLayout :doctor="doctor">
    <div class="max-w-5xl mx-auto p-6">
      <!-- هدر -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <Link
            :href="route('portal.patients.index')"
            class="p-2 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <svg
              class="w-5 h-5 text-gray-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
          <h1 class="text-2xl font-bold text-gray-900">جزئیات نوبت</h1>
        </div>

        <!-- Badge وضعیت -->
        <div
          :class="getStatusBadgeClasses(appointment.status)"
          class="px-4 py-2 rounded-xl text-sm font-bold"
        >
          {{ getStatusLabel(appointment.status) }}
        </div>
      </div>

      <!-- کارت اطلاعات بیمار -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-blue-50 rounded-xl">
            <svg
              class="w-6 h-6 text-blue-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
          </div>
          <h2 class="text-lg font-bold text-gray-900">اطلاعات بیمار</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- نام بیمار -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">نام بیمار</span>
            </div>
            <p class="text-gray-900 font-bold text-lg">{{ appointment.patient_name }}</p>
          </div>

          <!-- شماره تلفن -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">شماره تلفن</span>
            </div>
            <p class="text-gray-900 font-bold text-lg" dir="ltr">
              {{ appointment.patient_phone }}
            </p>
          </div>

          <!-- کد ملی -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">کد ملی</span>
            </div>
            <p class="text-gray-900 font-bold text-lg" dir="ltr">
              {{ appointment.patient_national_code || "-" }}
            </p>
          </div>
        </div>
      </div>

      <!-- کارت اطلاعات نوبت -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-2 bg-green-50 rounded-xl">
            <svg
              class="w-6 h-6 text-green-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
          </div>
          <h2 class="text-lg font-bold text-gray-900">اطلاعات نوبت</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- تاریخ -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">تاریخ نوبت</span>
            </div>
            <p class="text-gray-900 font-bold">
              {{ toPersianDate(appointment.appointment_date) }}
            </p>
          </div>

          <!-- ساعت -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">ساعت نوبت</span>
            </div>
            <p class="text-gray-900 font-bold">
              {{ appointment.appointment_time.substr(0, 5) }}
            </p>
          </div>

          <!-- مطب -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">مطب</span>
            </div>
            <p class="text-gray-900 font-bold">{{ appointment.clinic?.name || "-" }}</p>
          </div>

          <!-- تغییر وضعیت -->
          <div class="bg-gray-50 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg
                class="w-4 h-4 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <span class="text-xs font-bold text-gray-500">وضعیت نوبت</span>
            </div>
            <select
              :value="appointment.status"
              @change="updateStatus($event.target.value)"
              :class="getStatusClasses(appointment.status)"
              class="w-full px-3 py-2 rounded-lg text-sm font-bold border-0 cursor-pointer"
            >
              <option value="pending">در انتظار</option>
              <option value="confirmed">پرداخت شده</option>
              <option value="cancelled">لغو شده</option>
              <option value="visited">ویزیت شده</option>
            </select>
          </div>
        </div>
      </div>
      <!-- سابقه مراجعات همین بیمار نزد این پزشک -->
      <section
        v-if="appointment.patient_history?.length"
        class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
      >
        <div class="mb-5 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-xl">🗂️</div>
            <div>
              <h2 class="text-base font-extrabold text-slate-900">سابقه مراجعات بیمار</h2>
              <p class="mt-1 text-xs text-slate-500">آخرین مراجعات ثبت‌شده نزد همین پزشک</p>
            </div>
          </div>
          <span class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700">
            {{ appointment.patient_history.length }} سابقه
          </span>
        </div>

        <div class="relative space-y-3">
          <div
            v-for="historyItem in appointment.patient_history"
            :key="historyItem.id"
            class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex items-start gap-3">
              <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
              <div>
                <p class="text-sm font-extrabold text-slate-800">
                  {{ toPersianDate(historyItem.appointment_date) }}
                  <span class="mx-1 text-slate-300">·</span>
                  {{ formatTime(historyItem.appointment_time) }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                  {{ historyItem.clinic?.name || 'مطب ثبت نشده' }}
                  <span v-if="historyItem.visit_reason?.title"> · {{ historyItem.visit_reason.title }}</span>
                </p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span :class="getStatusBadgeClasses(historyItem.status)" class="rounded-lg border px-2.5 py-1.5 text-[11px] font-bold">
                {{ getStatusLabel(historyItem.status) }}
              </span>
              <span class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600">
                {{ paymentStatusLabel(historyItem.latest_payment?.status) }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- دلیل مراجعه -->
      <div class="bg-white rounded-2xl shadow-sm border border-violet-100 p-6 mb-6">
        <div class="flex items-center gap-3 mb-5">
          <div class="p-3 bg-violet-50 rounded-xl">
            <svg
              class="w-6 h-6 text-violet-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M8 10h8m-8 4h5m-9 6 1.8-4.2A8 8 0 1 1 20 12a8 8 0 0 1-8 8H4Z"
              />
            </svg>
          </div>

          <div>
            <h2 class="text-lg font-bold text-gray-900">دلیل مراجعه</h2>
            <p class="text-xs text-gray-500 mt-1">موضوع مراجعه بیمار به پزشک</p>
          </div>
        </div>

        <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-4">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 shrink-0">
              <svg
                class="w-5 h-5 text-violet-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 6.586V19a2 2 0 0 1-2 2Z"
                />
              </svg>
            </div>

            <p
              v-if="appointment?.visit_reason?.title"
              class="text-sm sm:text-base font-semibold text-violet-950 leading-7 break-words"
            >
              {{ appointment.visit_reason.title }}
            </p>

            <p v-else class="text-sm text-gray-500 leading-7">
              دلیلی برای این نوبت ثبت نشده است.
            </p>
          </div>
        </div>
      </div>
      <!-- وضعیت مالی نوبت -->
      <section class="mb-6 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-4 flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-xl">💳</div>
          <div>
            <h2 class="text-base font-extrabold text-gray-900">وضعیت مالی نوبت</h2>
            <p class="mt-1 text-xs text-gray-500">مبلغ ثبت‌شده و آخرین وضعیت پرداخت</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-xs text-slate-500">مبلغ نوبت</p>
            <p class="mt-2 text-lg font-black text-slate-900">
              {{ formatRial(appointment.fee_rial) }}
              <span class="text-xs font-medium text-slate-500">ریال</span>
            </p>
          </div>
          <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-xs text-slate-500">وضعیت پرداخت</p>
            <div class="mt-2 flex items-center gap-2">
              <span :class="paymentBadgeClass(appointment.latest_payment?.status)" class="rounded-lg px-3 py-1.5 text-xs font-bold">
                {{ paymentStatusLabel(appointment.latest_payment?.status) }}
              </span>
              <span v-if="appointment.latest_payment?.gateway_name" class="text-xs text-slate-500">
                {{ appointment.latest_payment.gateway_name }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- کارت توضیحات -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-50 rounded-xl">
              <svg
                class="w-6 h-6 text-purple-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">توضیحات نوبت</h2>
          </div>

          <!-- دکمه ویرایش/ذخیره -->
          <button
            v-if="!isEditingDescription"
            @click="startEditingDescription"
            class="flex items-center gap-2 px-4 py-2 bg-teal-50 text-teal-700 rounded-xl text-sm font-bold hover:bg-teal-100 transition-all"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            <span>{{
              appointment.description ? "ویرایش توضیحات" : "افزودن توضیحات"
            }}</span>
          </button>
        </div>

        <!-- حالت نمایش -->
        <div v-if="!isEditingDescription">
          <div
            v-if="appointment.description"
            class="bg-gray-50 rounded-xl p-4 text-gray-700 leading-relaxed whitespace-pre-wrap"
          >
            {{ appointment.description }}
          </div>
          <div v-else class="bg-gray-50 rounded-xl p-8 text-center">
            <svg
              class="w-12 h-12 text-gray-300 mx-auto mb-3"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            <p class="text-gray-500 text-sm">هنوز توضیحاتی ثبت نشده است</p>
            <p class="text-gray-400 text-xs mt-1">
              برای افزودن توضیحات، روی دکمه "افزودن توضیحات" کلیک کنید
            </p>
          </div>
        </div>

        <!-- حالت ویرایش -->
        <div v-else>
          <form @submit.prevent="saveDescription">
            <textarea
              ref="descriptionTextarea"
              v-model="descriptionText"
              rows="6"
              maxlength="1000"
              placeholder="توضیحات خود را در مورد این نوبت وارد کنید..."
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none text-sm leading-relaxed"
            ></textarea>

            <div class="flex items-center justify-between mt-3">
              <div class="flex items-center gap-4">
                <p class="text-xs text-gray-500">حداکثر ۱۰۰۰ کاراکتر</p>
                <p
                  class="text-xs"
                  :class="descriptionText.length > 900 ? 'text-red-500' : 'text-gray-400'"
                >
                  {{ descriptionText.length }} / ۱۰۰۰
                </p>
              </div>

              <div class="flex gap-2">
                <button
                  type="button"
                  @click="cancelEditing"
                  class="px-5 py-2 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all"
                >
                  انصراف
                </button>
                <button
                  type="submit"
                  :disabled="savingDescription"
                  class="px-5 py-2 bg-gradient-to-l from-teal-500 to-cyan-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  <svg
                    v-if="savingDescription"
                    class="w-4 h-4 animate-spin"
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
                    ></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  <span v-if="!savingDescription">ذخیره توضیحات</span>
                  <span v-else>در حال ذخیره...</span>
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- پیام موفقیت -->
        <Transition name="fade">
          <div
            v-if="successMessage"
            class="mt-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            {{ successMessage }}
          </div>
        </Transition>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, nextTick } from "vue";
import { router, Link } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { toPersianDate, formatTime } from "@/utils/dateUtils";

const props = defineProps({
   doctor: {
    type: Object,
    required: true,
  },
  appointment: Object,
});

// State توضیحات
const isEditingDescription = ref(false);
const descriptionText = ref("");
const savingDescription = ref(false);
const successMessage = ref("");
const descriptionTextarea = ref(null);

const startEditingDescription = () => {
  descriptionText.value = props.appointment.description || "";
  isEditingDescription.value = true;
  successMessage.value = "";

  nextTick(() => {
    if (descriptionTextarea.value) {
      descriptionTextarea.value.focus();
    }
  });
};

const cancelEditing = () => {
  isEditingDescription.value = false;
  descriptionText.value = "";
};

const saveDescription = () => {
  savingDescription.value = true;
  successMessage.value = "";

  router.put(
    route("portal.appointments.description", props.appointment.id),
    { description: descriptionText.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        successMessage.value = "توضیحات با موفقیت ذخیره شد.";
        savingDescription.value = false;
        isEditingDescription.value = false;

        setTimeout(() => {
          successMessage.value = "";
        }, 3000);
      },
      onError: () => {
        savingDescription.value = false;
      },
    }
  );
};

const updateStatus = (status) => {
  router.put(
    route("portal.appointments.status", props.appointment.id),
    { status },
    { preserveScroll: true }
  );
};

const formatRial = (amount) => {
  return new Intl.NumberFormat("fa-IR").format(Number(amount || 0));
};

const paymentStatusLabel = (status) => ({
  paid: "پرداخت شده",
  pending: "در انتظار پرداخت",
  initiated: "در حال پرداخت",
  failed: "پرداخت ناموفق",
  cancelled: "لغو پرداخت",
  refunded: "مسترد شده",
}[status] || "پرداخت ثبت نشده");

const paymentBadgeClass = (status) => ({
  paid: "bg-emerald-100 text-emerald-700",
  pending: "bg-amber-100 text-amber-700",
  initiated: "bg-amber-100 text-amber-700",
  failed: "bg-rose-100 text-rose-700",
  cancelled: "bg-rose-100 text-rose-700",
  refunded: "bg-sky-100 text-sky-700",
}[status] || "bg-slate-100 text-slate-600");

const getStatusClasses = (status) => {
  const classes = {
    pending: "bg-amber-100 text-amber-700",
    confirmed: "bg-green-100 text-green-700",
    cancelled: "bg-red-100 text-red-700",
    visited: "bg-purple-100 text-purple-700",
  };
  return classes[status] || "bg-gray-100 text-gray-700";
};

const getStatusBadgeClasses = (status) => {
  const classes = {
    pending: "bg-amber-100 text-amber-700 border-amber-200",
    confirmed: "bg-green-100 text-green-700 border-green-200",
    cancelled: "bg-red-100 text-red-700 border-red-200",
    visited: "bg-purple-100 text-purple-700 border-purple-200",
  };
  return classes[status] || classes.pending;
};

const getStatusLabel = (status) => {
  const labels = {
    pending: "⏳ در انتظار",
    confirmed: "✅ پرداخت شده",
    cancelled: "❌ لغو شده",
    visited: "✔️ ویزیت شده",
  };
  return labels[status] || "نامشخص";
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <Head title="ثبت‌نام پزشک | پرونیکس" />

  <div
    dir="rtl"
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 py-10 px-4"
  >
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-9">
        <div
          class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white text-3xl font-black shadow-lg shadow-blue-200 mb-4"
        >
          P
        </div>

        <h1 class="text-3xl md:text-4xl font-black text-gray-800 mb-3">
          به خانواده پرونیکس بپیوندید
        </h1>

        <p class="text-gray-500 leading-7">
          سایت اختصاصی پزشکی خود را در چند دقیقه راه‌اندازی کنید.
        </p>
      </div>

      <!-- Benefits -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-8">
        <div
          class="bg-white rounded-xl p-4 border border-gray-100 flex items-center gap-3"
        >
          <div
            class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl"
          >
            🌐
          </div>
          <div>
            <p class="font-bold text-sm text-gray-800">سایت اختصاصی</p>
            <p class="text-xs text-gray-500 mt-1">با آدرس دلخواه</p>
          </div>
        </div>

        <div
          class="bg-white rounded-xl p-4 border border-gray-100 flex items-center gap-3"
        >
          <div
            class="w-11 h-11 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-xl"
          >
            📅
          </div>
          <div>
            <p class="font-bold text-sm text-gray-800">نوبت‌دهی آنلاین</p>
            <p class="text-xs text-gray-500 mt-1">مدیریت بیماران</p>
          </div>
        </div>

        <div
          class="bg-white rounded-xl p-4 border border-gray-100 flex items-center gap-3"
        >
          <div
            class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl"
          >
            🎁
          </div>
          <div>
            <p class="font-bold text-sm text-gray-800">۷ روز رایگان</p>
            <p class="text-xs text-gray-500 mt-1">بدون پرداخت اولیه</p>
          </div>
        </div>
      </div>

      <!-- Main Card -->
      <div
        class="bg-white rounded-3xl shadow-xl shadow-gray-200/60 border border-gray-100 overflow-hidden"
      >
        <!-- Progress -->
        <div class="h-1.5 bg-gray-100">
          <div
            class="h-full bg-blue-600 transition-all duration-500"
            :style="{ width: verified ? '100%' : otpSent ? '70%' : '35%' }"
          ></div>
        </div>

        <div class="p-6 md:p-10">
          <!-- Step Header -->
          <div class="flex items-center gap-3 mb-8">
            <div
              class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold"
            >
              {{ verified ? "✓" : otpSent ? "۲" : "۱" }}
            </div>

            <div>
              <h2 class="text-xl font-bold text-gray-800">
                {{
                  verified
                    ? "تأیید شماره موبایل انجام شد"
                    : otpSent
                    ? "تأیید شماره موبایل"
                    : "اطلاعات پزشک"
                }}
              </h2>

              <p class="text-sm text-gray-500 mt-1">
                {{
                  verified
                    ? "اکنون می‌توانید ثبت‌نام را نهایی کنید."
                    : otpSent
                    ? "کد ارسال شده را وارد کنید."
                    : "اطلاعات خود را با دقت وارد کنید."
                }}
              </p>
            </div>

            <div class="mr-auto">
              <span
                v-if="verified"
                class="px-3 py-1.5 rounded-full text-xs font-bold bg-green-100 text-green-700"
              >
                تأیید شده
              </span>
            </div>
          </div>

          <form @submit.prevent="submitRegistration" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Name -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  نام <span class="text-red-500">*</span>
                </label>

                <input
                  v-model.trim="form.name"
                  type="text"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm disabled:bg-gray-100"
                  placeholder="نام پزشک"
                  :disabled="verified"
                />

                <p v-if="errors.name" class="text-red-500 text-xs mt-2">
                  {{ errors.name }}
                </p>
              </div>

              <!-- Family -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  نام خانوادگی <span class="text-red-500">*</span>
                </label>

                <input
                  v-model.trim="form.family"
                  type="text"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm disabled:bg-gray-100"
                  placeholder="نام خانوادگی"
                  :disabled="verified"
                />

                <p v-if="errors.family" class="text-red-500 text-xs mt-2">
                  {{ errors.family }}
                </p>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  شماره موبایل <span class="text-red-500">*</span>
                </label>

                <input
                  v-model="form.phone"
                  type="tel"
                  inputmode="numeric"
                  maxlength="11"
                  dir="ltr"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm text-left disabled:bg-gray-100"
                  placeholder="09123456789"
                  :disabled="otpSent"
                  @input="onPhoneInput"
                />

                <p v-if="errors.phone" class="text-red-500 text-xs mt-2">
                  {{ errors.phone }}
                </p>

                <p class="text-xs text-gray-400 mt-2">
                  شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.
                </p>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"
                  >ایمیل</label
                >

                <input
                  v-model.trim="form.email"
                  type="email"
                  dir="ltr"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm text-left disabled:bg-gray-100"
                  placeholder="doctor@example.com"
                  :disabled="verified"
                />

                <p v-if="errors.email" class="text-red-500 text-xs mt-2">
                  {{ errors.email }}
                </p>
              </div>

              <!-- Medical Number -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  شماره نظام پزشکی
                </label>

                <input
                  v-model="form.medical_system_number"
                  type="text"
                  inputmode="numeric"
                  dir="ltr"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm text-left disabled:bg-gray-100"
                  placeholder="شماره نظام پزشکی"
                  :disabled="verified"
                  @input="onMedicalNumberInput"
                />

                <p v-if="errors.medical_system_number" class="text-red-500 text-xs mt-2">
                  {{ errors.medical_system_number }}
                </p>
              </div>

              <!-- Specialty -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  تخصص <span class="text-red-500">*</span>
                </label>

                <select
                  v-model="form.specialty_id"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm disabled:bg-gray-100"
                  :disabled="verified"
                >
                  <option value="">انتخاب تخصص</option>
                  <option
                    v-for="item in specialties"
                    :key="item.id"
                    :value="String(item.id)"
                  >
                    {{ item.name }}
                  </option>
                </select>

                <p v-if="errors.specialty_id" class="text-red-500 text-xs mt-2">
                  {{ errors.specialty_id }}
                </p>
              </div>

              <!-- City -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  شهر محل فعالیت <span class="text-red-500">*</span>
                </label>

                <select
                  v-model="form.city_id"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-50 text-sm disabled:bg-gray-100"
                  :disabled="verified"
                >
                  <option value="">انتخاب شهر محل فعالیت</option>

                  <option v-for="item in cities" :key="item.id" :value="String(item.id)">
                    {{ item.name }}{{ item.province ? ` - ${item.province}` : "" }}
                  </option>
                </select>

                <p v-if="errors.city_id" class="text-red-500 text-xs mt-2">
                  {{ errors.city_id }}
                </p>
              </div>

              <!-- Slug -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  آدرس اختصاصی سایت <span class="text-red-500">*</span>
                </label>

                <div
                  class="flex items-center border border-gray-200 rounded-xl overflow-hidden focus-within:border-blue-500"
                >
                  <input
                    v-model.trim="form.slug"
                    dir="ltr"
                    class="w-full min-w-0 px-3 py-3 outline-none text-left text-sm disabled:bg-gray-100"
                    placeholder="dr-example"
                    :disabled="verified"
                    @input="slugTouched = true"
                  />

                  <span
                    class="bg-gray-50 border-r border-gray-200 px-3 py-3 text-xs text-gray-500 whitespace-nowrap"
                  >
                    proneex.ir
                  </span>
                </div>

                <p class="text-xs text-gray-400 mt-2" dir="ltr">
                  proneex.ir/{{ form.slug || "your-name" }}
                </p>

                <p v-if="errors.slug" class="text-red-500 text-xs mt-2">
                  {{ errors.slug }}
                </p>
              </div>
            </div>

            <!-- OTP -->
            <div
              v-if="otpSent && !verified"
              class="mt-8 p-5 rounded-2xl bg-blue-50 border border-blue-100"
            >
              <h3 class="font-bold text-gray-800 mb-2">کد تأیید پیامکی</h3>

              <p class="text-sm text-gray-500 mb-5">
                کد تأیید به شماره
                <span dir="ltr" class="font-bold">{{ form.phone }}</span>
                ارسال شد.
              </p>

              <div class="max-w-xs">
                <input
                  v-model="otp"
                  type="text"
                  inputmode="numeric"
                  maxlength="6"
                  dir="ltr"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-center text-2xl tracking-[10px] font-bold outline-none focus:border-blue-500"
                  placeholder="------"
                  @input="otp = otp.replace(/\D/g, '').slice(0, 6)"
                />
              </div>

              <p v-if="errors.code" class="text-red-500 text-xs mt-2">
                {{ errors.code }}
              </p>

              <div class="flex flex-wrap items-center gap-3 mt-4">
                <button
                  type="button"
                  @click="verifyOtp"
                  :disabled="loading || otp.length !== 6"
                  class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50"
                >
                  {{ loading ? "در حال بررسی..." : "تأیید کد" }}
                </button>

                <button
                  type="button"
                  @click="sendOtp"
                  :disabled="loading || countdown > 0"
                  class="text-sm text-blue-600 font-semibold disabled:text-gray-400"
                >
                  {{
                    countdown > 0 ? `ارسال مجدد (${countdown} ثانیه)` : "ارسال مجدد کد"
                  }}
                </button>
              </div>
            </div>

            <!-- Verified -->
            <div
              v-if="verified"
              class="mt-8 p-5 rounded-2xl bg-green-50 border border-green-100 flex items-center gap-3"
            >
              <div
                class="w-11 h-11 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl"
              >
                ✓
              </div>

              <div>
                <p class="font-bold text-green-800">شماره موبایل تأیید شد</p>
                <p class="text-sm text-green-700 mt-1">
                  اطلاعات شما آماده ثبت نهایی است.
                </p>
              </div>
            </div>

            <!-- Messages -->
            <div
              v-if="errorMessage"
              class="mt-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm"
            >
              {{ errorMessage }}
            </div>

            <div
              v-if="successMessage"
              class="mt-6 p-4 rounded-xl bg-green-50 border border-green-100 text-green-700 text-sm"
            >
              {{ successMessage }}
            </div>

            <!-- Actions -->
            <div class="mt-8 pt-6 border-t border-gray-100">
              <button
                v-if="!otpSent"
                type="button"
                @click="sendOtp"
                :disabled="loading"
                class="w-full py-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-base shadow-lg shadow-blue-200 transition disabled:opacity-50"
              >
                {{ loading ? "در حال ارسال کد..." : "ارسال کد تأیید موبایل" }}
              </button>

              <button
                v-else-if="verified"
                type="submit"
                :disabled="loading"
                class="w-full py-4 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-base shadow-lg shadow-green-200 transition disabled:opacity-50"
              >
                {{ loading ? "در حال ایجاد سایت..." : "ایجاد سایت و شروع ۷ روز رایگان" }}
              </button>
            </div>
          </form>

          <p class="text-center text-xs text-gray-400 leading-7 mt-6">
            با ثبت‌نام در پرونیکس، قوانین و شرایط استفاده از سامانه را می‌پذیرید.
            <br />
            پس از تأیید موبایل، سایت شما به‌صورت خودکار فعال خواهد شد.
          </p>
        </div>
      </div>

      <footer class="text-center mt-8 text-sm text-gray-400">
        هوش مصنوعی پرونیکس © ۲۰۲۶
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onBeforeUnmount } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
  specialties: {
    type: Array,
    default: () => [],
  },
  cities: {
    type: Array,
    default: () => [],
  },
});

const specialties = props.specialties;
const cities = props.cities;

const form = reactive({
  name: "",
  family: "",
  phone: "",
  email: "",
  medical_system_number: "",
  specialty_id: "",
  city_id: "",
  slug: "",
});

const errors = reactive({
  name: "",
  family: "",
  phone: "",
  email: "",
  medical_system_number: "",
  specialty_id: "",
  city_id: "",
  slug: "",
  code: "",
});

const otp = ref("");
const otpSent = ref(false);
const verified = ref(false);
const loading = ref(false);
const countdown = ref(0);
const errorMessage = ref("");
const successMessage = ref("");
const registrationToken = ref("");
const slugTouched = ref(false);

let countdownTimer = null;

const clearErrors = () => {
  Object.keys(errors).forEach((key) => {
    errors[key] = "";
  });
};

const clearMessages = () => {
  errorMessage.value = "";
  successMessage.value = "";
};

const normalizeDigits = (value) => {
  return String(value)
    .replace(/[۰-۹]/g, (digit) => "۰۱۲۳۴۵۶۷۸۹".indexOf(digit))
    .replace(/[٠-٩]/g, (digit) => "٠١٢٣٤٥٦٧٨٩".indexOf(digit));
};

const onPhoneInput = () => {
  form.phone = normalizeDigits(form.phone).replace(/\D/g, "").slice(0, 11);
  clearMessages();
  errors.phone = "";
};

const onMedicalNumberInput = () => {
  form.medical_system_number = normalizeDigits(form.medical_system_number).replace(
    /\D/g,
    ""
  );
  errors.medical_system_number = "";
};

const makeSlug = (value) => {
  const map = {
    ا: "a",
    آ: "a",
    ب: "b",
    پ: "p",
    ت: "t",
    ث: "s",
    ج: "j",
    چ: "ch",
    ح: "h",
    خ: "kh",
    د: "d",
    ذ: "z",
    ر: "r",
    ز: "z",
    ژ: "zh",
    س: "s",
    ش: "sh",
    ص: "s",
    ض: "z",
    ط: "t",
    ظ: "z",
    ع: "a",
    غ: "gh",
    ف: "f",
    ق: "gh",
    ک: "k",
    گ: "g",
    ل: "l",
    م: "m",
    ن: "n",
    و: "v",
    ه: "h",
    ی: "y",
    ئ: "y",
    ء: "",
  };

  return value
    .toLowerCase()
    .replace(/[آ-یء]/g, (char) => map[char] ?? "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");
};

watch(
  () => [form.name, form.family],
  ([name, family]) => {
    if (!slugTouched.value && !verified.value) {
      form.slug = makeSlug(`${name} ${family}`);
    }
  }
);

watch(
  () => form.phone,
  () => {
    if (!otpSent.value) return;

    otpSent.value = false;
    verified.value = false;
    registrationToken.value = "";
    otp.value = "";
    clearInterval(countdownTimer);
    countdown.value = 0;
  }
);

const startCountdown = (seconds = 120) => {
  clearInterval(countdownTimer);
  countdown.value = seconds;

  countdownTimer = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    } else {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
  }, 1000);
};

const validateForm = () => {
  clearErrors();

  let valid = true;

  if (!form.name.trim()) {
    errors.name = "نام پزشک را وارد کنید.";
    valid = false;
  }

  if (!form.family.trim()) {
    errors.family = "نام خانوادگی را وارد کنید.";
    valid = false;
  }

  if (!/^09\d{9}$/.test(form.phone)) {
    errors.phone = "شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.";
    valid = false;
  }

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = "ایمیل معتبر نیست.";
    valid = false;
  }

  if (!form.specialty_id) {
    errors.specialty_id = "لطفاً تخصص خود را انتخاب کنید.";
    valid = false;
  }

  if (!form.city_id) {
    errors.city_id = "لطفاً شهر محل فعالیت را انتخاب کنید.";
    valid = false;
  }

  if (!form.slug || !/^[آ-یa-zA-Z0-9]+(?:-[آ-یa-zA-Z0-9]+)*$/.test(form.slug)) {
    errors.slug =
      "آدرس سایت فقط می‌تواند شامل حروف فارسی، انگلیسی، اعداد و خط تیره باشد؛ فاصله مجاز نیست.";
    valid = false;
  }

  if (form.medical_system_number && !/^\d+$/.test(form.medical_system_number)) {
    errors.medical_system_number = "شماره نظام پزشکی فقط باید شامل عدد باشد.";
    valid = false;
  }

  return valid;
};

const applyServerErrors = (serverErrors) => {
  if (!serverErrors) return;

  Object.keys(serverErrors).forEach((key) => {
    if (key in errors) {
      errors[key] = serverErrors[key][0] || "";
    }
  });
};

const sendOtp = async () => {
  clearMessages();

  if (!validateForm()) {
    errorMessage.value = "لطفاً خطاهای فرم را برطرف کنید.";
    return;
  }

  loading.value = true;

  try {
    await axios.post("/register/doctor/send-otp", {
      phone: form.phone,
    });

    otpSent.value = true;
    verified.value = false;
    registrationToken.value = "";
    otp.value = "";

    startCountdown(120);

    successMessage.value = "کد تأیید با موفقیت ارسال شد.";
  } catch (error) {
    applyServerErrors(error.response?.data?.errors);

    errorMessage.value = error.response?.data?.message || "ارسال کد تأیید ناموفق بود.";
  } finally {
    loading.value = false;
  }
};

const verifyOtp = async () => {
  clearMessages();
  errors.code = "";

  if (!/^\d{6}$/.test(otp.value)) {
    errors.code = "کد تأیید باید ۶ رقم باشد.";
    return;
  }

  loading.value = true;

  try {
    const response = await axios.post("/register/doctor/verify-otp", {
      phone: form.phone,
      code: otp.value,
    });

    registrationToken.value = response.data.registration_token;
    verified.value = true;

    clearInterval(countdownTimer);
    countdownTimer = null;
    countdown.value = 0;

    successMessage.value = response.data.message || "شماره موبایل تأیید شد.";
  } catch (error) {
    applyServerErrors(error.response?.data?.errors);

    errorMessage.value =
      error.response?.data?.message || "کد تأیید صحیح نیست یا منقضی شده است.";
  } finally {
    loading.value = false;
  }
};

const submitRegistration = async () => {
  clearMessages();

  if (!validateForm()) {
    errorMessage.value = "لطفاً اطلاعات فرم را بررسی کنید.";
    return;
  }

  if (!verified.value || !registrationToken.value) {
    errorMessage.value = "ابتدا شماره موبایل خود را تأیید کنید.";
    return;
  }

  loading.value = true;

  try {
    const response = await axios.post("/register/doctor", {
      ...form,
      registration_token: registrationToken.value,
    });

    successMessage.value = response.data.message || "ثبت‌نام با موفقیت انجام شد.";

    if (response.data.doctor_url) {
      window.location.href = response.data.doctor_url;
    } else {
      window.location.href = response.data.redirect_url || "/portal/login";
    }
  } catch (error) {
    applyServerErrors(error.response?.data?.errors);

    errorMessage.value =
      error.response?.data?.message || "ثبت‌نام انجام نشد. دوباره تلاش کنید.";
  } finally {
    loading.value = false;
  }
};

onBeforeUnmount(() => {
  clearInterval(countdownTimer);
});
</script>

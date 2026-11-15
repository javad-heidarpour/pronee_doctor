<!-- resources/js/Pages/Doctor/Profile.vue -->
<template>
  <PortalLayout :doctor="doctor">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- اطلاعات شخصی -->
      <div
        class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-gray-100 p-6"
      >
        <h3 class="text-lg font-bold text-gray-900 mb-6">اطلاعات شخصی</h3>

        <form @submit.prevent="submitProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField v-model="form.name" label="نام" required />
            <FormField v-model="form.family" label="نام خانوادگی" required />
            <FormField v-model="form.email" label="ایمیل" type="email" required />
            <FormField v-model="form.phone" label="شماره تماس" />
            <FormField v-model="form.specialty_title" label="تخصص" required />
            <FormField
              v-model="form.years_of_experience"
              label="سال‌های تجربه"
              type="number"
              required
            />
            <FormField
              v-model="form.Medical_system_number"
              label="شماره نظام پزشکی"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">کپشن (شعار)</label>
            <input
              v-model="form.caption"
              type="text"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">بیوگرافی</label>
            <textarea
              v-model="form.description"
              rows="5"
              class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
            ></textarea>
          </div>

          <!-- آپلود عکس -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">عکس پروفایل</label>
            <div class="flex items-center gap-4">
              <img
                v-if="previewImage || doctor.photo"
                :src="previewImage || doctor.photo"
                class="w-20 h-20 rounded-full object-cover border-2 border-teal-200"
              />
              <input type="file" @change="handlePhoto" accept="image/*" class="text-sm" />
            </div>
          </div>

          <button
            type="submit"
            :disabled="processing"
            class="bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all"
          >
            {{ processing ? "در حال ذخیره..." : "ذخیره تغییرات" }}
          </button>
        </form>
      </div>

      <!-- تغییر رمز عبور -->
      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-6">تغییر رمز عبور</h3>

        <!-- پیام موفقیت -->
        <div
          v-if="passwordSuccess"
          role="status"
          class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-700"
        >
          {{ passwordSuccess }}
        </div>

        <!-- خطای عمومی -->
        <div
          v-if="passwordGeneralError"
          role="alert"
          class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
        >
          {{ passwordGeneralError }}
        </div>

        <form @submit.prevent="submitPassword" class="space-y-4">
          <div>
            <FormField
              v-model="passwordForm.current_password"
              label="رمز فعلی"
              type="password"
              required
            />
            <p v-if="errors.current_password" class="mt-1 text-sm text-red-600">
              {{ errors.current_password }}
            </p>
          </div>

          <div>
            <FormField
              v-model="passwordForm.password"
              label="رمز جدید"
              type="password"
              required
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <div>
            <FormField
              v-model="passwordForm.password_confirmation"
              label="تکرار رمز جدید"
              type="password"
              required
            />
          </div>

          <button
            type="submit"
            :disabled="processingPassword"
            class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-gray-800 disabled:opacity-50 transition-all"
          >
            {{ processingPassword ? "در حال تغییر..." : "تغییر رمز" }}
          </button>
        </form>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import PortalLayout from "@/Layouts/PortalLayout.vue";
import FormField from "@/Components/Portal/FormField.vue";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const page = usePage();

const errors = computed(() => page.props.errors || {});

const passwordSuccess = ref("");
const passwordGeneralError = ref("");

const props = defineProps({ doctor: Object });

const form = ref({
  id: props.doctor.id,
  name: props.doctor.name,
  family: props.doctor.family,
  email: props.doctor.email,
  phone: props.doctor.phone,
  caption: props.doctor.caption,
  specialty_title: props.doctor.specialty_title,
  years_of_experience: props.doctor.years_of_experience,
  Medical_system_number: props.doctor.Medical_system_number,
  description: props.doctor.description,
  photo: props.doctor.photo,
});

const passwordForm = ref({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const previewImage = ref(null);
const processing = ref(false);
const processingPassword = ref(false);

const handlePhoto = (e) => {
  const file = e.target.files?.[0];

  if (!file) return;

  if (!file.type.startsWith("image/")) {
    alert("لطفاً یک فایل تصویری انتخاب کنید.");
    e.target.value = "";
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    alert("حجم تصویر نباید بیشتر از ۲ مگابایت باشد.");
    e.target.value = "";
    return;
  }

  form.value.photo = file;
  previewImage.value = URL.createObjectURL(file);
};

const submitProfile = () => {
  processing.value = true;

  const data = { ...form.value };
  delete data.photo;

  if (form.value.photo instanceof File) {
    data.photo = form.value.photo;
  }

  router.post(
    route("portal.profile.update"),
    {
      ...data,
      _method: "PUT",
    },
    {
      forceFormData: true,
      preserveScroll: true,

      onSuccess: () => {
        previewImage.value = null;
        form.value.photo = props.doctor.photo;
      },

      onError: (errors) => {
        console.error("خطای ذخیره پروفایل:", errors);
      },

      onFinish: () => {
        processing.value = false;
      },
    }
  );
};

const submitPassword = () => {
  processingPassword.value = true;
  passwordSuccess.value = "";
  passwordGeneralError.value = "";

  router.put(route("portal.profile.password"), passwordForm.value, {
    preserveScroll: true,

    onSuccess: (page) => {
      const returnedErrors = page.props.errors || {};

      if (Object.keys(returnedErrors).length === 0) {
        passwordSuccess.value =
          page.props.flash?.success || "رمز عبور با موفقیت تغییر کرد.";

        passwordForm.value = {
          current_password: "",
          password: "",
          password_confirmation: "",
        };
      }
    },

    onError: (errors) => {
      if (errors.current_password) {
        passwordGeneralError.value = "";
      } else if (errors.password || errors.password_confirmation) {
        passwordGeneralError.value = "";
      } else {
        passwordGeneralError.value = "تغییر رمز عبور انجام نشد. لطفاً دوباره تلاش کنید.";
      }
    },

    onFinish: () => {
      processingPassword.value = false;
    },
  });
};
</script>

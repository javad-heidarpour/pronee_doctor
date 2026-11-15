<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: props.user.name ?? "",
  family: props.user.family ?? "",

  phone: props.user.phone ?? "",
  email: props.user.email ?? "",
  role: props.user.role ?? "user",
  is_active: Boolean(props.user.is_active),
  password: "",
  password_confirmation: "",
});

const roleLabels = {
  doctor: "پزشک",
  secretary: "منشی",
  admin: "مدیر",
  user: "کاربر",
};

const roleLabel = computed(() => roleLabels[form.role] ?? form.role);

const submit = () => {
  form.patch(route("portal.users.update", props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset("password", "password_confirmation");
    },
  });
};
</script>

<template>
  <PortalLayout
    :doctor="doctor"
    :title="'ویرایش اطلاعات  ' + user.name + ' ' + user.family"
  >
    <div dir="rtl" class="min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8">
      <div class="mx-auto max-w-4xl">
        <!-- Header -->

        <!-- User Identity -->
        <div
          class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <div class="bg-gradient-to-l from-teal-600 to-cyan-600 p-6 text-white">
            <div class="flex items-center gap-4">
              <div
                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-2xl font-black"
              >
                {{ form.name?.charAt(0) || "ک" }}
              </div>

              <div>
                <h2 class="text-xl font-bold">
                  {{ form.name + " " + form.family || "کاربر جدید" }}
                </h2>

                <p class="mt-1 text-sm text-white/80">شناسه کاربری: #{{ user.id }}</p>

                <span class="mt-2 inline-flex rounded-full bg-white/20 px-3 py-1 text-xs">
                  {{ roleLabel }}
                </span>
              </div>
              <Link
                :href="route('portal.users.index')"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-100"
              >
                <span>→</span>
                بازگشت به فهرست
              </Link>
            </div>
          </div>

          <form @submit.prevent="submit" class="space-y-7 p-5 sm:p-8">
            <!-- Personal Information -->
            <section>
              <div class="mb-5 flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700"
                >
                  <span>👤</span>
                </div>

                <div>
                  <h3 class="font-bold text-slate-800">اطلاعات کاربری</h3>
                  <p class="text-xs text-slate-400">مشخصات اصلی حساب</p>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700"> نام </label>

                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="نام کاربر"
                  />

                  <p v-if="form.errors.name" class="mt-2 text-xs text-red-500">
                    {{ form.errors.name }}
                  </p>
                </div>
                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    نام خانوادگی
                  </label>

                  <input
                    v-model="form.family"
                    type="text"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="نام خانوادگی کاربر"
                  />

                  <p v-if="form.errors.family" class="mt-2 text-xs text-red-500">
                    {{ form.errors.family }}
                  </p>
                </div>
                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    شماره تلفن
                  </label>

                  <input
                    v-model="form.phone"
                    type="tel"
                    dir="ltr"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="09123456789"
                  />

                  <p v-if="form.errors.phone" class="mt-2 text-xs text-red-500">
                    {{ form.errors.phone }}
                  </p>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    ایمیل
                  </label>

                  <input
                    v-model="form.email"
                    type="email"
                    dir="ltr"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="example@email.com"
                  />

                  <p v-if="form.errors.email" class="mt-2 text-xs text-red-500">
                    {{ form.errors.email }}
                  </p>
                </div>
              </div>
            </section>

            <div class="border-t border-slate-100"></div>

            <!-- Password Reset -->
            <section>
              <div class="mb-5 flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700"
                >
                  <span>🔑</span>
                </div>

                <div>
                  <h3 class="font-bold text-slate-800">تغییر رمز عبور</h3>
                  <p class="text-xs text-slate-400">
                    تعیین رمز جدید بدون نیاز به رمز قبلی
                  </p>
                </div>
              </div>

              <div
                class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-800"
              >
                برای تغییر رمز عبور، رمز جدید را در هر دو کادر وارد کنید. اگر این قسمت‌ها
                را خالی بگذارید، رمز فعلی کاربر تغییر نمی‌کند.
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    رمز عبور جدید
                  </label>

                  <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="حداقل ۸ کاراکتر"
                  />

                  <p v-if="form.errors.password" class="mt-2 text-xs text-red-500">
                    {{ form.errors.password }}
                  </p>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    تکرار رمز عبور جدید
                  </label>

                  <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left outline-none transition focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10"
                    placeholder="رمز جدید را تکرار کنید"
                  />
                </div>
              </div>
            </section>

            <div class="border-t border-slate-100"></div>

            <!-- Access Information -->
            <section>
              <div class="mb-5 flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"
                >
                  <span>🔐</span>
                </div>

                <div>
                  <h3 class="font-bold text-slate-800">سطح دسترسی</h3>
                  <p class="text-xs text-slate-400">نقش و وضعیت حساب</p>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-bold text-slate-700">
                    نقش کاربر
                  </label>

                  <select
                    v-model="form.role"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-teal-500 focus:bg-white"
                  >
                    <option value="user">کاربر</option>
                    <option value="secretary">منشی</option>
                    <option value="doctor">پزشک</option>
                    <option value="admin">مدیر</option>
                  </select>

                  <p v-if="form.errors.role" class="mt-2 text-xs text-red-500">
                    {{ form.errors.role }}
                  </p>
                </div>

                <div>
                  <label class="mb-3 block text-sm font-bold text-slate-700">
                    وضعیت حساب
                  </label>

                  <label
                    class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4"
                  >
                    <div>
                      <div class="font-bold text-slate-700">
                        {{ form.is_active ? "حساب فعال" : "حساب غیرفعال" }}
                      </div>

                      <div class="mt-1 text-xs text-slate-400">
                        {{
                          form.is_active
                            ? "کاربر امکان ورود دارد"
                            : "ورود کاربر غیرفعال است"
                        }}
                      </div>
                    </div>

                    <input
                      v-model="form.is_active"
                      type="checkbox"
                      class="h-5 w-5 accent-teal-600"
                    />
                  </label>

                  <p v-if="form.errors.is_active" class="mt-2 text-xs text-red-500">
                    {{ form.errors.is_active }}
                  </p>
                </div>
              </div>
            </section>

            <!-- Actions -->
            <div
              class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end"
            >
              <Link
                :href="route('portal.users.index')"
                class="rounded-xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-600 transition hover:bg-slate-50"
              >
                انصراف
              </Link>

              <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-teal-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-teal-600/20 transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {{ form.processing ? "در حال ذخیره..." : "ذخیره تغییرات" }}
              </button>
            </div>
          </form>
        </div>

        <p class="text-center text-xs text-slate-400">مدیریت کاربران</p>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  roles: {
    type: Array,
    default: () => [
      { value: "doctor", label: "پزشک", icon: "⚕️", description: "دسترسی به پنل پزشک" },
      {
        value: "secretary",
        label: "منشی",
        icon: "🗂️",
        description: "مدیریت نوبت‌ها و مراجعان",
      },
      {
        value: "admin",
        label: "مدیر",
        icon: "🛡️",
        description: "مدیریت و نظارت بر سیستم",
      },
      {
        value: "user",
        label: "کاربر عادی",
        icon: "👤",
        description: "دسترسی عمومی به سامانه",
      },
    ],
  },
});

const form = useForm({
  name: "",
  family: "",
  phone: "",
  email: "",
  username: "",
  password: "",
  password_confirmation: "",
  role: "doctor",
  is_active: true,
});

const selectedRole = computed(() => props.roles.find((role) => role.value === form.role));

const fullName = computed(() => `${form.name} ${form.family}`.trim() || "کاربر جدید");

const submit = () => {
  form.post(route("portal.users.store"), {
    preserveScroll: true,
  });
};
</script>

<template>
  <PortalLayout :doctor="doctor" :title="'تعریف کاربر'">
    <div dir="rtl" class="min-h-screen text-slate-800">
      <!-- Header -->
      <div
        class="relative overflow-hidden bg-gradient-to-l from-slate-950 via-slate-900 to-teal-950"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-5 text-xs text-slate-400 sm:px-6">
          <div class="flex items-center gap-2">
            <h3 class="m-0 text-base font-bold text-white sm:text-lg">ایجاد کاربر جدید</h3>
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-white/5 px-2 py-1.5">
            <span class="text-teal-400">۰۱</span>
            اطلاعات کاربر
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-white/5 px-2 py-1.5">
            <span class="text-teal-400">۰۲</span>
            سطح دسترسی
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-white/5 px-2 py-1.5">
            <span class="text-teal-400">۰۳</span>
            ایجاد حساب
          </div>
          <div>
            <Link
              :href="route('portal.users.index')"
              class="hidden sm:flex items-center gap-2 px-5 py-3 rounded-xl border border-white/15 text-white hover:bg-white/10 transition"
            >
              <span>→</span>
              بازگشت به کاربران
            </Link>
          </div>
        </div>
      </div>

      <!-- Content -->
      <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-7">
            <!-- Main Form -->
            <div class="lg:col-span-8 space-y-6">
              <!-- Personal Information -->
              <section
                class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden"
              >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                  <div
                    class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-xl"
                  >
                    ♙
                  </div>
                  <div>
                    <h2 class="font-bold text-slate-800">اطلاعات هویتی</h2>
                    <p class="text-xs text-slate-400 mt-1">
                      مشخصات اولیه کاربر را وارد کنید
                    </p>
                  </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label class="form-label">نام <span>*</span></label>
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="نام کاربر"
                      class="form-input"
                    />
                    <p v-if="form.errors.name" class="error">{{ form.errors.name }}</p>
                  </div>

                  <div>
                    <label class="form-label">نام خانوادگی <span>*</span></label>
                    <input
                      v-model="form.family"
                      type="text"
                      placeholder="نام خانوادگی"
                      class="form-input"
                    />
                    <p v-if="form.errors.family" class="error">
                      {{ form.errors.family }}
                    </p>
                  </div>

                  <div>
                    <label class="form-label">شماره موبایل <span>*</span></label>
                    <input
                      v-model="form.phone"
                      type="tel"
                      dir="ltr"
                      placeholder="09123456789"
                      class="form-input text-left"
                    />
                    <p v-if="form.errors.phone" class="error">
                      {{ form.errors.phone }}
                    </p>
                  </div>

                  <div>
                    <label class="form-label">پست الکترونیک</label>
                    <input
                      v-model="form.email"
                      type="email"
                      dir="ltr"
                      placeholder="example@email.com"
                      class="form-input text-left"
                    />
                    <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
                  </div>
                </div>
              </section>

              <!-- Account Information -->
              <section
                class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden"
              >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                  <div
                    class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl"
                  >
                    🔐
                  </div>
                  <div>
                    <h2 class="font-bold">اطلاعات حساب کاربری</h2>
                    <p class="text-xs text-slate-400 mt-1">اطلاعات ورود به سامانه</p>
                  </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label class="form-label">نام کاربری</label>
                    <input
                      v-model="form.username"
                      dir="ltr"
                      placeholder="username"
                      class="form-input text-left"
                    />
                    <p v-if="form.errors.username" class="error">
                      {{ form.errors.username }}
                    </p>
                  </div>

                  <div>
                    <label class="form-label">رمز عبور <span>*</span></label>
                    <input
                      v-model="form.password"
                      type="password"
                      placeholder="رمز عبور"
                      class="form-input"
                    />
                    <p v-if="form.errors.password" class="error">
                      {{ form.errors.password }}
                    </p>
                  </div>

                  <div class="md:col-span-2">
                    <label class="form-label">تکرار رمز عبور <span>*</span></label>
                    <input
                      v-model="form.password_confirmation"
                      type="password"
                      placeholder="رمز عبور را مجدداً وارد کنید"
                      class="form-input"
                    />
                  </div>
                </div>
              </section>

              <!-- Roles -->
              <section
                class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden"
              >
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                  <div
                    class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl"
                  >
                    ◈
                  </div>
                  <div>
                    <h2 class="font-bold">نقش و سطح دسترسی</h2>
                    <p class="text-xs text-slate-400 mt-1">
                      نقش موردنظر کاربر را انتخاب کنید
                    </p>
                  </div>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <button
                    v-for="role in roles"
                    :key="role.value"
                    type="button"
                    @click="form.role = role.value"
                    class="relative text-right p-5 rounded-2xl border-2 transition-all duration-200"
                    :class="
                      form.role === role.value
                        ? 'border-teal-500 bg-teal-50/70 shadow-sm'
                        : 'border-slate-100 hover:border-slate-300 bg-white'
                    "
                  >
                    <div class="flex items-center justify-between">
                      <span class="text-2xl">{{ role.icon }}</span>

                      <span
                        class="w-5 h-5 rounded-full border flex items-center justify-center"
                        :class="
                          form.role === role.value
                            ? 'border-teal-600 bg-teal-600'
                            : 'border-slate-300'
                        "
                      >
                        <span
                          v-if="form.role === role.value"
                          class="w-2 h-2 bg-white rounded-full"
                        ></span>
                      </span>
                    </div>

                    <h3 class="font-bold mt-4">{{ role.label }}</h3>
                    <p class="text-xs text-slate-400 mt-2">{{ role.description }}</p>

                    <span
                      v-if="form.role === role.value"
                      class="absolute top-3 left-3 text-[10px] text-teal-700 font-bold"
                    >
                      انتخاب شده
                    </span>
                  </button>
                </div>
              </section>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-4 space-y-6">
              <!-- Preview -->
              <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                  <h3 class="font-bold">پیش‌نمایش کاربر</h3>
                  <span class="text-xs text-slate-400">نمای زنده</span>
                </div>

                <div
                  class="rounded-2xl bg-gradient-to-br from-slate-900 to-teal-900 p-6 text-white relative overflow-hidden"
                >
                  <div
                    class="absolute -left-10 -top-10 w-36 h-36 rounded-full border border-white/10"
                  ></div>
                  <div
                    class="absolute -left-5 -top-5 w-24 h-24 rounded-full border border-white/10"
                  ></div>

                  <div class="relative">
                    <div
                      class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-3xl mb-5"
                    >
                      {{ selectedRole?.icon || "👤" }}
                    </div>

                    <h3 class="font-black text-xl">{{ fullName }}</h3>
                    <p class="text-teal-200 text-sm mt-2">
                      {{ selectedRole?.label }}
                    </p>

                    <div
                      class="border-t border-white/15 mt-6 pt-5 flex items-center justify-between"
                    >
                      <span class="text-xs text-slate-300">وضعیت حساب</span>
                      <span class="flex items-center gap-2 text-xs">
                        <span
                          class="w-2 h-2 rounded-full"
                          :class="form.is_active ? 'bg-emerald-400' : 'bg-red-400'"
                        ></span>
                        {{ form.is_active ? "فعال" : "غیرفعال" }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="space-y-4 mt-6 text-sm">
                  <div class="flex justify-between gap-3">
                    <span class="text-slate-400">شماره موبایل</span>
                    <span dir="ltr" class="font-medium">{{ form.phone || "---" }}</span>
                  </div>

                  <div class="flex justify-between gap-3">
                    <span class="text-slate-400">ایمیل</span>
                    <span class="font-medium truncate max-w-40">{{
                      form.email || "---"
                    }}</span>
                  </div>

                  <div class="flex justify-between gap-3">
                    <span class="text-slate-400">نام کاربری</span>
                    <span class="font-medium">{{ form.username || "---" }}</span>
                  </div>
                </div>
              </section>

              <!-- Status -->
              <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                <h3 class="font-bold mb-5">وضعیت حساب کاربری</h3>

                <button
                  type="button"
                  @click="form.is_active = !form.is_active"
                  class="w-full flex items-center justify-between p-4 rounded-2xl transition"
                  :class="form.is_active ? 'bg-emerald-50' : 'bg-red-50'"
                >
                  <div class="text-right">
                    <div
                      class="font-bold text-sm"
                      :class="form.is_active ? 'text-emerald-700' : 'text-red-700'"
                    >
                      {{ form.is_active ? "حساب فعال است" : "حساب غیرفعال است" }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                      {{
                        form.is_active ? "کاربر امکان ورود دارد" : "ورود کاربر مسدود است"
                      }}
                    </div>
                  </div>

                  <div
                    class="w-12 h-6 rounded-full relative transition"
                    :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'"
                  >
                    <span
                      class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all"
                      :class="form.is_active ? 'right-1' : 'right-7'"
                    ></span>
                  </div>
                </button>
              </section>

              <!-- Help -->
              <section class="rounded-3xl bg-teal-50 border border-teal-100 p-5">
                <div class="flex gap-3">
                  <span class="text-xl">💡</span>
                  <div>
                    <h4 class="font-bold text-teal-900 text-sm">نکته امنیتی</h4>
                    <p class="text-xs leading-6 text-teal-800 mt-2">
                      برای هر کاربر نقش مناسب تعیین کنید. دسترسی‌های مدیریتی باید فقط به
                      افراد مجاز اختصاص داده شوند.
                    </p>
                  </div>
                </div>
              </section>
            </aside>
          </div>

          <!-- Actions -->
          <div
            class="mt-8 bg-white border border-slate-100 rounded-3xl shadow-sm p-5 flex flex-col sm:flex-row items-center justify-between gap-4"
          >
            <p class="text-xs text-slate-400">
              <span class="text-red-500">*</span>
              تکمیل فیلدهای ستاره‌دار الزامی است.
            </p>

            <div class="flex gap-3 w-full sm:w-auto">
              <Link
                :href="route('portal.users.index')"
                class="flex-1 sm:flex-none text-center px-6 py-3 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition"
              >
                انصراف
              </Link>

              <button
                type="submit"
                :disabled="form.processing"
                class="flex-1 sm:flex-none px-8 py-3 rounded-xl bg-gradient-to-l from-teal-600 to-cyan-600 text-white font-bold shadow-lg shadow-teal-600/20 hover:shadow-xl hover:-translate-y-0.5 transition disabled:opacity-50"
              >
                {{ form.processing ? "در حال ثبت..." : "✓ ایجاد کاربر جدید" }}
              </button>
            </div>
          </div>
        </form>
      </main>
    </div>
  </PortalLayout>
</template>

<style scoped>
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #475569;
  margin-bottom: 10px;
}

.form-label span {
  color: #ef4444;
}

.form-input {
  width: 100%;
  height: 48px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  outline: none;
  font-size: 13px;
  background: #fff;
  transition: all 0.2s;
}

.form-input:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.08);
}

.form-input::placeholder {
  color: #cbd5e1;
}

.error {
  color: #ef4444;
  font-size: 11px;
  margin-top: 6px;
}
</style>

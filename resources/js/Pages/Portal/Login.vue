<!-- resources/js/Pages/Doctor/Login.vue -->
<template>
  <div
    dir="rtl"
    class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-cyan-50 flex items-center justify-center p-4"
  >
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-3xl shadow-xl mb-4"
        >
          <svg
            class="w-10 h-10 text-white"
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
        <h1 class="text-3xl font-black text-gray-900 mb-2">
          پنل مدیریت پزشکان
        </h1>
        <p class="text-gray-500">برای ورود، اطلاعات خود را وارد کنید</p>
      </div>

      <!-- Login Form -->
      <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2"
              >ایمیل</label
            >
            <div class="relative">
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-3 pr-11 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                placeholder="doctor@example.com"
              />
              <svg
                class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
            </div>
            <p
              v-if="$page.props.errors.email"
              class="text-red-500 text-xs mt-1"
            >
              {{ $page.props.errors.email }}
            </p>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2"
              >رمز عبور</label
            >
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full px-4 py-3 pr-11 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                placeholder="••••••••"
              />
              <svg
                class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                />
              </svg>
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
              />
              <span class="text-sm text-gray-600">مرا به خاطر بسپار</span>
            </label>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="processing"
            class="w-full bg-gradient-to-l from-teal-500 to-cyan-600 text-white py-3.5 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all hover:scale-[1.02] disabled:opacity-50"
          >
            <span v-if="!processing">ورود به پنل</span>
            <span v-else>در حال ورود...</span>
          </button>
        </form>
      </div>

      <PorneexWidget />
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import PorneexWidget from "@/Components/PorneexWidget.vue";

const form = ref({
  email: "",
  password: "",
  remember: false,
});

const showPassword = ref(false);
const processing = ref(false);

const submit = () => {
  processing.value = true;
  router.post(route("portal.login"), form.value, {
    onFinish: () => {
      processing.value = false;
    },
  });
};
</script>
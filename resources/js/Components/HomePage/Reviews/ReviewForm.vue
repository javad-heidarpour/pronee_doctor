<template>
  <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
    <div class="mb-8">
      <h2 class="text-2xl font-black text-gray-900">تجربه خود را ثبت کنید</h2>

      <p class="text-gray-500 mt-2">نظر شما پس از بررسی نمایش داده خواهد شد.</p>
    </div>

    <!-- پیام موفقیت -->
    <div
      v-if="successMessage"
      class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-700 p-4"
    >
      {{ successMessage }}
    </div>

    <!-- پیام خطای عمومی -->
    <div
      v-if="serverError"
      class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 p-4"
    >
      {{ serverError }}
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <div class="grid md:grid-cols-2 gap-5">
        <!-- نام -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2"> نام </label>

          <input
            v-model="form.patient_name"
            type="text"
            class="w-full rounded-xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"
            placeholder="نام شما"
          />

          <p v-if="errors.patient_name" class="text-red-500 text-sm mt-2">
            {{ errors.patient_name[0] }}
          </p>
        </div>

        <!-- ناشناس -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2"> ارسال ناشناس </label>

          <label class="flex items-center gap-3 mt-3">
            <input v-model="form.is_anonymous" type="checkbox" class="rounded" />

            <span>نام من نمایش داده نشود</span>
          </label>
        </div>
      </div>

      <!-- امتیاز -->
      <RatingInput v-model="form.rating" />

      <p v-if="errors.rating" class="text-red-500 text-sm">
        {{ errors.rating[0] }}
      </p>

      <!-- متن نظر -->
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-2"> نظر شما </label>

        <textarea
          v-model="form.comment"
          rows="6"
          class="w-full rounded-xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"
          placeholder="تجربه خود را بنویسید..."
        />

        <p v-if="errors.comment" class="text-red-500 text-sm mt-2">
          {{ errors.comment[0] }}
        </p>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white px-8 py-4 rounded-2xl font-black transition"
      >
        <span v-if="!loading"> ثبت نظر </span>

        <span v-else> در حال ارسال... </span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

import RatingInput from "./RatingInput.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  patient_name: "",
  rating: 5,
  comment: "",
  is_anonymous: false,
});

const loading = ref(false);

const errors = ref({});

const successMessage = ref("");

const serverError = ref("");

const submit = async () => {
  loading.value = true;

  errors.value = {};

  successMessage.value = "";

  serverError.value = "";

  try {
    const response = await axios.post(
      route("review.store", props.doctor.slug),

      form.data()
    );


    successMessage.value = response.data.message ?? "نظر شما با موفقیت ثبت شد.";

    form.reset();

    form.rating = 5;
  } catch (error) {

    if (error.response) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors;
      } else {
        serverError.value =
          error.response.data.message ?? "خطایی در ثبت اطلاعات رخ داده است.";
      }
    } else {
      serverError.value = "ارتباط با سرور برقرار نشد.";
    }
  } finally {
    loading.value = false;
  }
};
</script>

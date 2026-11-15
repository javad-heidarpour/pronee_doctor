<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
      <!-- Header -->

      <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-xl font-black text-gray-900">پاسخ پزشک</h2>

        <button
          @click="$emit('close')"
          class="w-10 h-10 rounded-xl hover:bg-gray-100 transition"
        >
          ✕
        </button>
      </div>

      <!-- Body -->

      <div class="p-8 space-y-6">
        <div>
          <div class="font-bold text-gray-800 mb-2">نظر بیمار</div>

          <div
            class="rounded-2xl bg-gray-50 border border-gray-100 p-5 leading-8 text-gray-700"
          >
            {{ review.comment }}
          </div>
        </div>

        <div>
          <label class="block font-bold text-gray-700 mb-3"> پاسخ شما </label>

          <textarea
            v-model="reply"
            rows="8"
            class="w-full rounded-2xl border-gray-200 focus:border-teal-500 focus:ring-teal-500"
            placeholder="پاسخ پزشک..."
          />
        </div>
      </div>

      <!-- Footer -->

      <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
        <button
          @click="$emit('close')"
          class="px-6 py-3 rounded-xl bg-gray-200 hover:bg-gray-300 font-bold"
        >
          انصراف
        </button>

        <button
          @click="submit"
          class="px-8 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold"
        >
          ذخیره پاسخ
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  review: Object,
});

const emit = defineEmits(["close", "submit"]);

const reply = ref(props.review.doctor_reply ?? "");

const submit = () => {
  emit(
    "submit",

    reply.value
  );
};
</script>

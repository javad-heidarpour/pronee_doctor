<template>
  <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
    <!-- بیمار -->

    <td class="px-6 py-5 align-top">
      <div class="font-bold text-gray-900">
        {{ review.patient_name }}
      </div>

      <div v-if="review.is_anonymous" class="text-xs text-gray-500 mt-1">ناشناس</div>
    </td>

    <!-- امتیاز -->

    <td class="px-6 py-5 text-center">
      <div class="flex justify-center gap-1">
        <svg
          v-for="i in 5"
          :key="i"
          class="w-5 h-5"
          :class="i <= review.rating ? 'text-amber-400' : 'text-gray-300'"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
          />
        </svg>
      </div>
    </td>

    <!-- متن نظر -->

    <td class="px-6 py-5">
      <p class="text-gray-700 leading-7">
        {{ review.comment }}
      </p>

      <div
        v-if="review.doctor_reply"
        class="mt-4 rounded-xl bg-teal-50 border border-teal-100 p-4"
      >
        <div class="font-bold text-teal-700 mb-2">پاسخ پزشک</div>

        <div class="text-gray-700">
          {{ review.doctor_reply }}
        </div>

        <div class="text-xs text-gray-500 mt-2">
          {{$formatDate(review.doctor_reply_at) }}
        </div>
      </div>
    </td>

    <!-- وضعیت -->

    <td class="px-6 py-5 text-center">
      <span
        v-if="review.is_approved"
        class="inline-flex px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-bold"
      >
        تایید شده
      </span>

      <span
        v-else
        class="inline-flex px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm font-bold"
      >
        در انتظار تایید
      </span>
    </td>

    <!-- تاریخ -->

    <td class="px-6 py-5 text-center text-sm text-gray-500">
      {{$formatDate( review.created_at) }}
    </td>

    <!-- عملیات -->

    <td class="px-6 py-5">
      <div class="flex flex-col gap-2">
        <button
          @click="$emit('approve', review)"
          class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold"
        >
          {{ review.is_approved ? "لغو تایید" : "تایید" }}
        </button>

        <button
          @click="$emit('reply', review)"
          class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold"
        >
          {{ review.doctor_reply ? "ویرایش پاسخ" : "پاسخ" }}
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup>
defineProps({
  review: Object,
});

defineEmits(["approve", "reply"]);

</script>

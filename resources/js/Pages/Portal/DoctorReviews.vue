<template>
  <PortalLayout :doctor="doctor">
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-black text-gray-900">نظرات بیماران</h1>

        <p class="text-gray-500 mt-2">مدیریت نظرات ثبت شده توسط بیماران</p>
      </div>

      <ReviewStats :stats="stats" />

      <ReviewFilters
        v-model:search="filters.search"
        v-model:status="filters.status"
        v-model:rating="filters.rating"
        @filter="applyFilter"
      />

      <!-- Desktop Table -->
      <div
        class="hidden md:block bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden"
      >
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px]">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-right">بیمار</th>

                <th class="px-6 py-4 text-center">امتیاز</th>

                <th class="px-6 py-4 text-right">نظر</th>

                <th class="px-6 py-4 text-center">وضعیت</th>

                <th class="px-6 py-4 text-center">تاریخ</th>

                <th class="px-6 py-4 text-center">عملیات</th>
              </tr>
            </thead>

            <tbody>
              <ReviewCard
                v-for="review in reviews.data"
                :key="review.id"
                :review="review"
                @approve="toggleApprove"
                @reply="openReply"
              />
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-4">
        <div
          v-for="review in reviews.data"
          :key="review.id"
          class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5"
        >
          <div class="flex items-center justify-between mb-4">
            <div class="font-bold text-gray-900">
              {{ review.patient_display_name }}
            </div>

            <div class="text-amber-500">
              {{ review.stars }}
            </div>
          </div>

          <p class="text-gray-700 leading-7 text-justify mb-4">
            {{ review.comment }}
          </p>

          <div class="flex justify-between text-sm text-gray-500 mb-4">
            <span>
              {{ review.persian_date }}
            </span>

            <span :class="review.is_approved ? 'text-green-600' : 'text-orange-500'">
              {{ review.is_approved ? "تایید شده" : "در انتظار" }}
            </span>
          </div>

          <div class="flex gap-2">
            <button
              @click="toggleApprove(review)"
              class="flex-1 bg-gray-100 hover:bg-gray-200 rounded-xl py-2 text-sm font-bold"
            >
              {{ review.is_approved ? "رد تایید" : "تایید" }}
            </button>

            <button
              @click="openReply(review)"
              class="flex-1 bg-teal-600 hover:bg-teal-700 text-white rounded-xl py-2 text-sm font-bold"
            >
              پاسخ
            </button>
          </div>
        </div>
      </div>

      <ReviewPagination :links="reviews.links" />
    </div>

    <Teleport to="body">
      <ReviewReplyModal
        v-if="replyModal"
        :review="selectedReview"
        @close="replyModal = false"
        @submit="submitReply"
      />
    </Teleport>
  </PortalLayout>
</template>

<script setup>
import { reactive, ref } from "vue";

import { router } from "@inertiajs/vue3";

import PortalLayout from "@/Layouts/PortalLayout.vue";

import ReviewStats from "@/Components/Portal/Reviews/ReviewStats.vue";
import ReviewFilters from "@/Components/Portal/Reviews/ReviewFilters.vue";
import ReviewCard from "@/Components/Portal/Reviews/ReviewCard.vue";
import ReviewReplyModal from "@/Components/Portal/Reviews/ReviewReplyModal.vue";
import ReviewPagination from "@/Components/Portal/Reviews/ReviewPagination.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  reviews: Object,

  stats: Object,

  filters: Object,
});

const replyModal = ref(false);

const selectedReview = ref(null);

const filters = reactive({
  search: props.filters.search ?? "",

  status: props.filters.status ?? "",

  rating: props.filters.rating ?? "",
});

const applyFilter = () => {
  router.get(
    route("portal.doctor-reviews.index"),

    filters,

    {
      preserveState: true,

      preserveScroll: true,

      replace: true,
    }
  );
};

const toggleApprove = (review) => {
  router.put(
    route("portal.doctor-reviews.toggle", review.id),

    {},

    {
      preserveScroll: true,
    }
  );
};

const openReply = (review) => {
  selectedReview.value = review;

  replyModal.value = true;
};

const submitReply = (reply) => {
  router.put(
    route("portal.doctor-reviews.reply", selectedReview.value.id),

    {
      doctor_reply: reply,
    },

    {
      preserveScroll: true,

      preserveState: true,

      onSuccess() {
        replyModal.value = false;

        selectedReview.value = null;
      },
    }
  );
};
</script>

<style scoped>
table {
  border-collapse: collapse;
}

th {
  font-weight: 700;

  color: #374151;

  font-size: 14px;
}

td {
  vertical-align: top;
}
</style>

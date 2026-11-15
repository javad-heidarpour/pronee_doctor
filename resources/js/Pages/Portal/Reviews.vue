<!-- resources/js/Pages/Doctor/Reviews.vue -->
<template>
  <PortalLayout :doctor="doctor">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-900">نظرات بیماران</h2>
      <div class="flex gap-2">
        <button
          v-for="f in filters"
          :key="f.value"
          @click="applyFilter(f.value)"
          :class="
            filter === f.value ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700'
          "
          class="px-4 py-2 rounded-xl text-sm font-medium"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <div class="space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
      >
        <div class="flex justify-between items-start mb-3">
          <div>
            <p class="font-bold text-gray-900">{{ review.patient_name }}</p>
            <p class="text-xs text-gray-500">{{ review.persian_date }}</p>
          </div>
          <div class="flex gap-1">
            <svg
              v-for="i in 5"
              :key="i"
              class="w-4 h-4"
              :class="i <= review.rating ? 'text-amber-400' : 'text-gray-300'"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
              />
            </svg>
          </div>
        </div>
        <p class="text-gray-700 mb-4">{{ review.comment }}</p>
        <div class="flex gap-2">
          <button
            @click="toggle(review.id)"
            :class="
              review.is_approved
                ? 'bg-amber-100 text-amber-700'
                : 'bg-green-100 text-green-700'
            "
            class="px-4 py-2 rounded-lg text-sm font-medium"
          >
            {{ review.is_approved ? "عدم تایید" : "تایید" }}
          </button>
          <button
            @click="deleteReview(review.id)"
            class="px-4 py-2 bg-red-50 text-red-600 rounded-lg text-sm font-medium"
          >
            حذف
          </button>
        </div>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref ,computed} from "vue";

// ✅ دریافت اطلاعات پزشک از auth
const page = usePage();



const props = defineProps({ doctor: Object, reviews: Array, filter: String });

const filters = [
  { value: "all", label: "همه" },
  { value: "approved", label: "تایید شده" },
  { value: "pending", label: "در انتظار" },
];

const applyFilter = (value) => {
  router.get(route("portal.reviews.index"), { filter: value }, { preserveState: true });
};

const toggle = (id) => {
  router.put(route("portal.reviews.toggle", id));
};

const deleteReview = (id) => {
  if (confirm("حذف شود؟")) router.delete(route("portal.reviews.destroy", id));
};
</script>

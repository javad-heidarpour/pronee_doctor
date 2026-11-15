<template>
  <section id="reviews" class="space-y-8">
    <ReviewStats :stats="stats" />

    <ReviewForm :doctor="doctor" />

    <div v-if="reviews.length" class="space-y-6">
      <ReviewCard v-for="review in filteredReviews" :key="review.id" :review="review" />
    </div>

    <EmptyReviews v-else />
  </section>
</template>

<script setup>
import { computed } from "vue";

import ReviewStats from "./ReviewStats.vue";
import ReviewForm from "./ReviewForm.vue";
import ReviewCard from "./ReviewCard.vue";
import EmptyReviews from "./EmptyReviews.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },

  reviews: {
    type: Array,
    default: () => [],
  },

  stats: {
    type: Object,
    required: true,
  },
});

const filteredReviews = computed(() => {
  return [...props.reviews].sort((a, b) => {
    return new Date(b.created_at) - new Date(a.created_at);
  });
});
</script>

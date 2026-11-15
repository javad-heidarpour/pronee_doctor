<template>
  <div
    class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-3xl p-8 border border-amber-100"
  >
    <div class="grid lg:grid-cols-3 gap-8 items-center">
      <!-- میانگین -->

      <div class="text-center">
        <div class="text-6xl font-black text-amber-600">
          {{ average }}
        </div>

        <div class="flex justify-center mt-3">
          <RatingStars :rating="Math.round(average)" />
        </div>

        <div class="mt-3 text-gray-600">میانگین رضایت بیماران</div>
      </div>

      <!-- تعداد -->

      <div
        class="text-center border-y lg:border-y-0 lg:border-x border-amber-200 py-6 lg:py-0"
      >
        <div class="text-6xl font-black text-gray-800">
          {{ stats.count }}
        </div>

        <div class="mt-3 text-gray-600">نظر ثبت شده</div>
      </div>

      <!-- نمودار -->

      <RatingDistribution :stats="stats" />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

import RatingStars from "./RatingStars.vue";
import RatingDistribution from "./RatingDistribution.vue";

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
});

const average = computed(() => {
  return Number(props.stats.average ?? 0).toFixed(1);
});
</script>

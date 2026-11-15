<template>
  <div class="space-y-3">
    <div v-for="item in items" :key="item.star" class="flex items-center gap-3">
      <div class="w-14 text-sm font-bold text-gray-600">{{ item.star }} ستاره</div>

      <div class="flex-1 h-3 bg-gray-200 rounded-full overflow-hidden">
        <div
          class="h-full bg-amber-400 rounded-full transition-all duration-500"
          :style="{
            width: item.percent + '%',
          }"
        />
      </div>

      <div class="w-10 text-left text-sm text-gray-500">
        {{ item.count }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
});

const items = computed(() => {
  const total = props.stats.count || 1;

  return [5, 4, 3, 2, 1].map((star) => ({
    star,

    count: props.stats.distribution?.[star] || 0,

    percent: (((props.stats.distribution?.[star] || 0) / total) * 100).toFixed(0),
  }));
});
</script>

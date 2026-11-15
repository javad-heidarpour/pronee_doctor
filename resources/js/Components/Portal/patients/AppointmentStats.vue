<template>
  <div
    class="mb-4 grid grid-cols-5 gap-1"
    dir="rtl"
  >
    <div
      v-for="item in items"
      :key="item.key"
      class="min-w-0 rounded-md px-1 py-1.5 text-center"
      :class="item.bg"
    >
      <div
        class="truncate text-[9px] font-medium sm:text-xs"
        :class="item.labelColor"
      >
        {{ item.label }}
      </div>

      <div
        class="mt-0.5 text-xs font-extrabold leading-tight sm:text-base"
        :class="item.valueColor"
      >
        {{ stats[item.key] ?? 0 }}
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

const items = computed(() => [
  {
    key: "total",
    label: "کل",
    bg: "bg-blue-50",
    labelColor: "text-blue-600",
    valueColor: "text-blue-800",
  },
  {
    key: "pending",
    label: "انتظار",
    bg: "bg-amber-50",
    labelColor: "text-amber-600",
    valueColor: "text-amber-800",
  },
  {
    key: "confirmed",
    label: "پرداخت شده",
    bg: "bg-green-50",
    labelColor: "text-green-600",
    valueColor: "text-green-800",
  },
  {
    key: "cancelled",
    label: "لغو",
    bg: "bg-red-50",
    labelColor: "text-red-600",
    valueColor: "text-red-800",
  },
  {
    key: "visited",
    label: "ویزیت شده",
    bg: "bg-purple-50",
    labelColor: "text-purple-600",
    valueColor: "text-purple-800",
  },
]);
</script>
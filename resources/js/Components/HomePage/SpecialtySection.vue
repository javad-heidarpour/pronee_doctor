<template>
  <section
    v-if="specialties.length"
    class="rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6"
    dir="rtl"
  >
    <div class="mb-4">
      <h3 class="text-base font-black text-slate-900">حوزه‌های تخصصی</h3>
      <p class="mt-1 text-xs text-slate-500">زمینه‌های فعالیت و تخصص پزشک</p>
    </div>

    <div class="flex flex-wrap gap-2">
      <span
        v-for="(specialty, index) in specialties"
        :key="specialty.id || specialty.name || index"
        class="inline-flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2 text-sm font-bold text-emerald-800"
      >
        <span>{{ specialty.icon || "✚" }}</span>
        {{ specialty.name || specialty.title }}
      </span>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

const specialties = computed(() => {
  const value = props.doctor?.specialty;
  if (Array.isArray(value)) return value;
  return value && typeof value === "object" ? [value] : [];
});
</script>

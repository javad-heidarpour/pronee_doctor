<script setup>
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
const page = usePage();
const locale = computed(() => page.props.locale || "fa");
const options = [
  { value: "fa", label: "فارسی" },
  { value: "en", label: "English" },
  { value: "ar", label: "العربية" },
  { value: "tr", label: "Türkmençe" },
];
function changeLocale(value) {
  if (value !== locale.value) router.post(route("locale.update"), { locale: value }, { preserveScroll: true });
}
</script>
<template>
  <label class="fixed left-3 top-4 z-[120] rounded-full border border-white/80 bg-white/85 px-2 py-1 shadow backdrop-blur">
    <span class="sr-only">زبان</span>
    <select :value="locale" aria-label="انتخاب زبان" class="bg-transparent text-xs outline-none" @change="changeLocale($event.target.value)">
      <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
  </label>
</template>

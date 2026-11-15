<template>
  <div v-if="links && links.length > 3" class="flex items-center justify-center mt-8">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="(link, index) in links"
        :key="index"
        :disabled="!link.url"
        @click="visit(link.url)"
        v-html="link.label"
        class="min-w-[42px] px-4 py-2 rounded-xl border transition-all"
        :class="[
          link.active
            ? 'bg-teal-600 text-white border-teal-600'
            : 'bg-white hover:bg-gray-100 border-gray-200 text-gray-700',

          !link.url ? 'opacity-40 cursor-not-allowed' : '',
        ]"
      />
    </div>
  </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
  links: {
    type: Array,

    default: () => [],
  },
});

const visit = (url) => {
  if (!url) return;

  router.visit(
    url,

    {
      preserveScroll: true,

      preserveState: true,
    }
  );
};
</script>

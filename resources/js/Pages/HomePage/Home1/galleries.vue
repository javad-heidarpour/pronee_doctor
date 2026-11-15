<script setup>
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
import GallerySection from "@/Components/HomePage/GallerySection.vue";
const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  galleryCategories: {
    type: Object,
    required: false,
  },
  galleryImages: {
    type: Object,
    required: false,
  },
});
</script>

<template>
  <PublicPageLayout
    :doctor="doctor"
    title="گالری عکس و نمونه کار‌ها"
    active-item="services"
  >
    <GallerySection
      v-if="galleryImages && galleryCategories.length > 0"
      :images="galleryImages"
      :categories="[
        {
          value: 'all',
          label: 'همه تصاویر',
          count: galleryCategories.length,
        },
        ...(galleryCategories || []),
      ]"
    />
  </PublicPageLayout>
</template>

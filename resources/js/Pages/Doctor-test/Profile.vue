<template>
  <div dir="rtl" class="min-h-screen bg-gray-50/50 font-sans text-gray-800">
    <!-- Hero Section -->
    <HeadSite :doctor="doctor" />

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative z-10 pb-20">
      <div class="grid grid-cols-1 lg:grid-cols-1 sm:grid-cols-1 gap-8">
        <AboutDoctor :doctor="doctor" />

        <BookingSidebar :doctor="doctor" />

        <ClinicLocation :doctor="doctor" />

        <!-- ✅ بخش گالری -->
        <GallerySection
          v-if="doctor.gallery_images && doctor.gallery_images.length > 0"
          :images="doctor.gallery_images"
          :categories="[
            { value: 'all', label: 'همه تصاویر', count: doctor.gallery_images.length },
            ...(doctor.gallery_categories || []),
          ]"
        />
        <!-- ✅ بخش نظرات -->
        <ReviewsSection
          v-if="doctor.reviews && doctor.reviews.length > 0"
          :reviews="doctor.reviews"
          :stats="doctor.reviews_stats"
        />
        <BlogSection :doctor="doctor" />
      </div>
    </div>
  </div>
</template>

<script setup>
// ✅ Import Components
import AboutDoctor from "@/Components/HomePage/AboutDoctor.vue";
import ClinicLocation from "@/Components/HomePage/ClinicLocation.vue";
import BookingSidebar from "@/Components/HomePage/Booking/BookingSidebar.vue";
import BlogSection from "@/Components/HomePage/BlogSection.vue";
import HeadSite from "@/Components/HomePage/HeadSite.vue";
import GallerySection from "@/Components/HomePage/GallerySection.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
    default: () => ({}),
  },
});
</script>

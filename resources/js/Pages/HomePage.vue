<template>
  <div dir="rtl" class="min-h-screen bg-gray-50/50 font-sans text-gray-800">
    <!-- هدر پزشک؛ منو روی تصویر بنر قرار می‌گیرد -->
    <HeadSite :ImgPath="ImgPath" :doctor="doctor">
      <div class="fixed top-4 right-4 z-[100]">
        <HomeMenu
          :active-section="activeSection"
          @select-section="selectSection"
          :doctor="doctor"
        />
      </div>
    </HeadSite>

    <!-- محتوای اصلی -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative z-10 pb-20">
      <div class="grid grid-cols-1 gap-8">
        <!-- معرفی پزشک: همیشه قابل مشاهده -->
        <AboutDoctor v-if="activeSection === 'about'" :doctor="doctor"> </AboutDoctor>

        <!-- بخش نمایش ساعات کاری -->
        <template v-if="activeSection === 'WorkingHours'">
          <div
            class="grid grid-cols-1 gap-5 bg-white rounded-3xl shadow-sm border border-gray-100 p-4 sm:grid-cols-2 sm:gap-8 sm:p-6 md:p-8 hover:shadow-lg transition-shadow duration-300"
          >
            <!-- <BookingSidebar :doctor="doctor" /> -->

            <WorkingHoursPanel :doctor="doctor" />
            <SpecialtySection :doctor="doctor" />
          </div>
        </template>
        <!-- بخش رزور نوبت آنلاین -->
        <BookingSection v-if="activeSection === 'booking'" :doctor="doctor" />
        <!-- آدرس و موقعیت مطب -->
        <ClinicLocation v-if="activeSection === 'location'" :doctor="doctor" />

        <!-- گالری تصاویر -->
        <GallerySection
          v-if="
            activeSection === 'gallery' &&
            doctor.gallery_images &&
            doctor.gallery_images.length > 0
          "
          :images="doctor.gallery_images"
          :categories="[
            {
              value: 'all',
              label: 'همه تصاویر',
              count: doctor.gallery_images.length,
            },
            ...(doctor.gallery_categories || []),
          ]"
        />

        <!-- نظرات بیماران -->
        <ReviewsSection
          v-if="activeSection === 'reviews'"
          :doctor="doctor"
          :reviews="doctor.reviews"
          :stats="doctor.reviews_stats"
        />

        <!-- مقالات -->
        <BlogSection v-if="activeSection === 'blog'" :doctor="doctor" />
      </div>

      <!-- محتوای اسلات -->
      <slot />
    </main>

    <!-- ویجت پرنکس -->
    <PorneexWidget />
  </div>

  <AppointmentTypeFloating
    :ImgPath="ImgPath"
    :active-section="activeSection"
    @select-section="selectSection"
    :doctor="doctor"
  />
</template>

<script setup>
import HomeMenu from "@/Components/HomePage/HomeMenu.vue";

import AboutDoctor from "@/Components/HomePage/AboutDoctor.vue";
import HeadSite from "@/Components/HomePage/HeadSite.vue";
import PorneexWidget from "@/Components/PorneexWidget.vue";
import AppointmentTypeFloating from "@/Components/Appointment/AppointmentTypeFloating.vue";
import SpecialtySection from "@/Components/HomePage/SpecialtySection.vue";
import ClinicLocation from "@/Components/HomePage/ClinicLocation.vue";
import BookingSection from "@/Components/HomePage/Booking/BookingSection.vue";
import BookingSidebar from "@/Components/HomePage/Booking/BookingSidebar.vue";
import WorkingHoursPanel from "@/Components/HomePage/Booking/WorkingHoursPanel.vue";
import BlogSection from "@/Components/HomePage/BlogSection.vue";
import ReviewsSection from "@/Components/HomePage/Reviews/ReviewsSection.vue";
import GallerySection from "@/Components/HomePage/GallerySection.vue";
import { ref } from "vue";

const ImgPath = "\\storage\\doctors\\";

const activeSection = ref("about");

function selectSection(section) {
  activeSection.value = section;

  // ابتدا منتظر نمایش کامپوننت انتخاب‌شده می‌مانیم
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const target = document.getElementById(`section-${section}`);

      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });
}

defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});
</script>

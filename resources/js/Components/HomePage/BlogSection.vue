<template>
  <section
    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-lg transition-shadow duration-300"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <BlogHeader />
      <!-- Featured Posts -->
      <FeaturedPosts
        :doctor="doctor"
        :featuredPosts="featuredPosts"
        :defaultImage="defaultImage"
      />

      <!-- Latest Posts -->

      <LatestPosts
        :doctor="doctor"
        :latestPosts="latestPosts"
        :defaultImage="defaultImage"
      />

      <!-- View All Button -->
      <div class="text-center mt-12">
        <!-- :href="route('portal.blog.index')" -->

        <a
          :href="`/${doctor.slug}/blog`"
          class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-l from-teal-500 to-cyan-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all hover:scale-105"
        >
          مشاهده همه مقالات
          <svg
            class="w-5 h-5 rotate-90"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import BlogHeader from "../Blogs/BlogHeader.vue";
import FeaturedPosts from "../Blogs/FeaturedPosts.vue";
import LatestPosts from "../Blogs/LatestPosts.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: false,
  },
  blog: {
    type: Object,
    required: false,
  },
});
const featuredPosts = props.blog.featuredPosts;
const latestPosts = props.blog.latestPosts;

const defaultImage = "/storage/doctor-gallery/defaultImage.jpg";
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

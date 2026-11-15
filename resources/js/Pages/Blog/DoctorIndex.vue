<template>
  <PublicPageLayout :doctor="doctor" title="لیست مقالات" active-item="services">
    <!-- Posts -->
    <section class="max-w-7xl mx-auto px-5 py-12">
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article
          v-for="post in posts.data"
          :key="post.id"
          class="bg-white rounded-3xl overflow-hidden shadow hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
        >
          <!-- Post Image -->
          <img
            :src="getPostImage(post)"
            :alt="post.title"
            class="h-56 w-full object-cover"
          />

          <div class="p-6">
            <div class="flex justify-between text-sm text-gray-400 mb-4">
              <span> 👁 {{ post.views_count }} </span>

              <span> ⏱ {{ post.reading_time }} دقیقه </span>
            </div>

            <h3 class="text-xl font-black leading-8 text-gray-800">
              {{ post.title }}
            </h3>

            <p class="text-gray-500 mt-3 line-clamp-3">
              {{ post.excerpt }}
            </p>

            <a
              :href="`/${doctor.slug}/blog/${post.slug}`"
              class="mt-6 flex items-center justify-center gap-2 bg-teal-600 text-white py-3 rounded-xl font-bold hover:bg-teal-700 transition"
            >
              📖 مطالعه مقاله
            </a>
          </div>
        </article>
      </div>
    </section>
  </PublicPageLayout>
</template>

<script setup>
import { computed } from "vue";
import PorneexWidget from "@/Components/PorneexWidget.vue";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";

const STORAGE = "/storage/";
const DEFAULT_DOCTOR_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";
const DEFAULT_POST_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },

  posts: {
    type: Object,
    required: true,
  },
});

/**
 * تصویر پزشک
 */
const doctorImage = computed(() => {
  if (!props.doctor.photo) {
    return DEFAULT_DOCTOR_IMAGE;
  }

  if (props.doctor.photo.startsWith("http") || props.doctor.photo.startsWith("/")) {
    return props.doctor.photo;
  }

  return props.doctor.photo;
  // return STORAGE+'doctors/' + props.doctor.photo;
});

/**
 * تصویر مقاله
 */
const getPostImage = (post) => {
  if (!post.featured_image) {
    return DEFAULT_POST_IMAGE;
  }

  if (post.featured_image.startsWith("http") || post.featured_image.startsWith("/")) {
    return post.featured_image;
  }

  return STORAGE + post.featured_image;
};
</script>

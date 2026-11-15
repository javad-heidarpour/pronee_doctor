<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <section
      v-if="posts && posts.data.length"
      class="bg-gradient-to-br from-teal-700 via-cyan-700 to-sky-800 text-white"
    >
      <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row items-center gap-8">
          <!-- Doctor -->

          <img
            :src="doctorImage"
            class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-2xl"
          />

          <div class="flex-1 text-center lg:text-right">
            <h1 class="text-4xl font-black">
              مقالات با تگ

              <span v-if="tag" class="text-yellow-300">
                {{ tag.name }}
              </span>
              <span v-else class="text-yellow-300">
                {{ slug }}
              </span>
            </h1>

            <p class="mt-3 text-cyan-100 text-lg">
              {{ doctor.caption }}
              {{ doctor.name }}
              {{ doctor.family }}
            </p>

            <div class="flex flex-wrap justify-center lg:justify-start gap-3 mt-6">
              <div v-if="posts" class="bg-white/20 px-5 py-3 rounded-xl backdrop-blur">
                📝
                {{ posts.total }}
                مقاله
              </div>

              <div v-if="tag" class="bg-white/20 px-5 py-3 rounded-xl backdrop-blur">
                🏷
                {{ tag.name }}
              </div>
              <div v-else class="bg-white/20 px-5 py-3 rounded-xl backdrop-blur">
                🏷
                {{ slug }}
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <Link
              :href="route('/')"
              class="bg-white text-teal-700 font-black px-6 py-3 rounded-2xl shadow-lg hover:scale-105 transition"
            >
              👨‍⚕️ پروفایل پزشک
            </Link>

            <Link
              :href="route('booking')"
              class="bg-yellow-400 text-gray-900 font-black px-6 py-3 rounded-2xl shadow-lg hover:scale-105 transition"
            >
              📅 رزرو نوبت
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- Posts -->

    <section class="max-w-7xl mx-auto px-6 py-12">
      <!-- مقالات -->

      <div v-if="posts" class="grid md:grid-cols-2 xl:grid-cols-3 gap-8">
        <article
          v-for="post in posts.data"
          :key="post.id"
          class="bg-white rounded-3xl overflow-hidden shadow hover:shadow-2xl hover:-translate-y-2 transition duration-300"
        >
          <!-- Image -->

          <img :src="getPostImage(post)" class="h-56 w-full object-cover" />

          <div class="p-6">
            <div class="flex justify-between text-sm mb-4">
              <span class="bg-cyan-50 text-cyan-700 px-3 py-1 rounded-full">
                👁 {{ post.views_count }}
              </span>

              <span class="bg-amber-50 text-amber-700 px-3 py-1 rounded-full">
                ⏱ {{ post.reading_time }} دقیقه
              </span>
            </div>

            <h2 class="font-black text-2xl text-gray-800 leading-9">
              {{ post.title }}
            </h2>

            <p class="text-gray-500 mt-4 line-clamp-3">
              {{ post.excerpt }}
            </p>

            <!-- Tags -->

            <div class="flex flex-wrap gap-2 mt-5">
              <span
                v-for="tag in post.tags"
                :key="tag.id"
                class="bg-teal-50 text-teal-700 text-sm px-3 py-1 rounded-full"
              >
                #{{ tag.name }}
              </span>
            </div>

            <Link
              :href="`/${doctor.slug}/blog/${post.slug}`"
              class="mt-7 flex justify-center items-center gap-2 bg-gradient-to-r from-teal-600 to-cyan-600 text-white py-3 rounded-xl font-black hover:shadow-lg transition"
            >
              📖 مطالعه مقاله
            </Link>
          </div>
        </article>
      </div>

      <!-- بدون نتیجه -->

      <NotFoundPost v-else :doctorImage="doctorImage" :doctor="doctor" :slug="slug" />
    </section>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

import NotFoundPost from "@/Components/Blog/NotFoundPost.vue";
const STORAGE = "/storage/";
const DEFAULT_DOCTOR_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";
const DEFAULT_POST_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const props = defineProps({
  doctor: Object,
  tag: Object,
  slug: String,
  posts: Object,
});

const doctorImage = computed(() => {
  if (!props.doctor.photo) return DEFAULT_DOCTOR_IMAGE;

  if (props.doctor.photo.startsWith("http") || props.doctor.photo.startsWith("/")) {
    return props.doctor.photo;
  }

  return props.doctor.photo;
  // return STORAGE + "doctors/" + props.doctor.photo;
});

const getPostImage = (post) => {
  if (!post.featured_image) return DEFAULT_POST_IMAGE;

  if (post.featured_image.startsWith("http") || post.featured_image.startsWith("/")) {
    return post.featured_image;
  }

  return STORAGE + post.featured_image;
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Doctor Header -->
    <section class="bg-gradient-to-br from-teal-700 via-cyan-700 to-sky-800 text-white">
      <div class="max-w-7xl mx-auto px-5 py-10">
        <div class="flex flex-col md:flex-row items-center gap-8">

            
          <img
            :src="doctorImage"
            :alt="`${doctor.caption} ${doctor.name} ${doctor.family}`"
            class="w-32 h-32 rounded-full border-4 border-white shadow-xl object-cover"
          />

          <div class="text-center md:text-right flex-1">
            <h1 class="text-4xl lg:text-5xl font-extrabold leading-relaxed drop-shadow-lg">
              
              مقالات 
              {{doctor.caption}} 
              {{ doctor.name }}
              {{ doctor.family }}
            </h1>

            <p class="mt-4 text-xl text-cyan-100">
              {{ doctor.specialty?.title }}
            </p>

            <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-6">

              <div
                class="bg-white/15 backdrop-blur-md border border-white/20 px-5 py-3 rounded-2xl font-bold"
              >
                ⭐ {{ doctor.average_rating || 5 }}
              </div>

              <div
                class="bg-white/15 backdrop-blur-md border border-white/20 px-5 py-3 rounded-2xl font-bold"
              >
                📝 {{ posts.total }} مقاله
              </div>

            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-3 w-full md:w-auto">

            <a
              :href="`/`"
              class="bg-white text-teal-700 px-7 py-3 rounded-2xl font-black shadow-lg hover:scale-105 transition text-center"
            >
              👨‍⚕️ مشاهده پروفایل پزشک
            </a>

            <a
              :href="route('booking')"

              class="bg-yellow-400 text-gray-900 px-7 py-3 rounded-2xl font-black shadow-lg hover:scale-105 transition text-center"
            >
              📅 رزرو نوبت
            </a>

          </div>

        </div>
      </div>
    </section>

    <!-- Posts -->
    <section class="max-w-7xl mx-auto px-5 py-12">

      <h2 class="text-3xl font-black text-gray-800 mb-10">
        آخرین مقالات 
        {{doctor.caption}}
        {{ doctor.name }}
        {{ doctor.family }}
      </h2>

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

              <span>
                👁 {{ post.views_count }}
              </span>

              <span>
                ⏱ {{ post.reading_time }} دقیقه
              </span>

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
  </div>
    <PorneexWidget />

</template>

<script setup>
import { computed } from "vue";
import PorneexWidget from "@/Components/PorneexWidget.vue";

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

  if (
    props.doctor.photo.startsWith("http") ||
    props.doctor.photo.startsWith("/")
  ) {
    return props.doctor.photo;
  }

  return  props.doctor.photo;
  // return STORAGE+'doctors/' + props.doctor.photo;
});

/**
 * تصویر مقاله
 */
const getPostImage = (post) => {
  if (!post.featured_image) {
    return DEFAULT_POST_IMAGE;
  }

  if (
    post.featured_image.startsWith("http") ||
    post.featured_image.startsWith("/")
  ) {
    return post.featured_image;
  }

  return STORAGE + post.featured_image;
};
</script>
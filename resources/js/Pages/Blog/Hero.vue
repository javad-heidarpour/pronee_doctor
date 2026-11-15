<template>
  <!-- Hero -->

  <section class="relative overflow-hidden bg-slate-950">
    <!-- Background -->

    <div
      class="absolute inset-0 bg-cover bg-center scale-105"
      :style="{ backgroundImage: `url(${featuredImage})` }"
    />

    <!-- Dark Overlay -->

    <div class="absolute inset-0 bg-black/70"></div>

    <!-- Gradient -->

    <div
      class="absolute inset-0 bg-gradient-to-l from-slate-950/95 via-slate-900/70 to-slate-900/30"
    ></div>

    <!-- Blur only background -->

    <div class="absolute inset-0 backdrop-blur-sm"></div>

    <!-- Content -->

    <div
      class="relative z-10 max-w-7xl mx-auto px-6 py-24 min-h-[300px] flex items-center"
    >
      <div class="grid gap-5 grid lg:grid-cols-2 gap-12">
        <!-- max-w-3xl rounded-[36px] bg-slate-900/65 backdrop-blur-xl border border-white/10 shadow-2xl p-10 md:p-14 -->

        <div>
          <div
            v-if="post.category"
            class="inline-flex px-5 py-2 rounded-full bg-teal-500 text-white font-bold"
          >
            {{ post.category.name }}
          </div>

          <h1 class="mt-8 text-4xl md:text-6xl font-black leading-[1.5] text-white">
            {{ post.title }}
          </h1>

          <p class="mt-8 text-white/80 text-xl leading-10">
            {{ post.excerpt }}
          </p>

          <div class="flex flex-wrap gap-4 mt-10">
            <div class="px-5 py-3 rounded-xl bg-white/10 text-white">
              👁 {{ post.views_count }} بازدید
            </div>

            <div class="px-5 py-3 rounded-xl bg-white/10 text-white">
              🕒 {{ readingTime }} دقیقه مطالعه
            </div>

            <div class="px-5 py-3 rounded-xl bg-white/10 text-white">
              📅 {{ post.persian_published_at }}
            </div>

            <div
              v-if="post.average_rating"
              class="px-5 py-3 rounded-xl bg-white/10 text-white"
            >
              ⭐ {{ post.average_rating }}
            </div>
          </div>
        </div>
        <div>
          <div class="flex items-center gap-5 mt-12 pt-8 border-t border-white/10">
            <img
              :src="doctorAvatar"
              class="w-20 h-20 rounded-full border-4 border-white object-cover"
            />

            <div class="flex-1">
              <div class="text-2xl text-white font-black">
                {{ post.doctor?.caption }}

                {{ post.doctor?.name }}

                {{ post.doctor?.family }}
              </div>

              <div class="text-white/70 mt-2">
                {{ post.doctor?.speciality }}
              </div>
            </div>
          </div>

          <div class="flex gap-5 mt-12">
            <Link
              :href="route('booking')"

              class="bg-teal-500 hover:bg-teal-600 text-white px-8 py-4 rounded-2xl font-black transition"
            >
              رزرو نوبت
            </Link>

            <Link
              :href="`/${post.doctor.slug}/blog`"
              class="bg-white hover:bg-slate-100 text-slate-900 px-8 py-4 rounded-2xl font-black transition"
            >
              همه مقالات
            </Link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { computed, ref, onMounted, onUnmounted, nextTick } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
  post: Object,
  doctorAvatar: String,
  doctorPosts: {
    type: Array,
    default: () => [],
  },

  commentsStats: {
    type: Object,
    default: () => ({
      average: 0,
      count: 0,
    }),
  },
});
const DEFAULT_POST_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const readingTime = computed(() => {
  const words = (props.post.content || "").replace(/<[^>]*>/g, "").split(/\s+/).length;

  return Math.max(1, Math.ceil(words / 220));
});
const featuredImage = computed(() => {
  const image = props.post.featured_image;

  if (!image) {
    return DEFAULT_POST_IMAGE;
  }

  if (image.startsWith("http") || image.startsWith("/")) {
    return image;
  }

  return `/storage/${image}`;
});
</script>

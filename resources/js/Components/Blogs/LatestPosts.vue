<template>
  <div>
    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
      <span class="text-2xl">🆕</span>
      آخرین مقالات
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article
        v-for="post in latestPosts"
        :key="post.id"
        class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300"
      >

        <Link
          :href="`/${doctor.slug}/blog/${post.slug}`"
          class="relative h-40 overflow-hidden"
        >
          <img
            :src="post.featured_image || defaultImage"
            :alt="post.title"
            class="w-100 h-100  object-cover group-hover:scale-105 transition-transform duration-300"
          />
          <div
            class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"
          ></div>
          <div class="absolute bottom-3 right-3">
            <span
              class="px-2 py-1 bg-white/90 backdrop-blur-sm rounded-lg text-xs font-medium text-gray-700"
            >
              {{ post.category.icon }} {{ post.category.name }}
            </span>
          </div>
        </Link>
        <div class="p-5">
          <h4
            class="text-base font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-teal-600 transition-colors"
          >
            {{ post.title }}
          </h4>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            {{ post.excerpt }}
          </p>
          <div
            class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-100"
          >
            <span class="flex items-center gap-1">
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
              {{ $formatDate(post.published_at) }}
            </span>
            <span class="flex items-center gap-1">
              <svg
                class="w-3.5 h-3.5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              {{ post.reading_time }} دقیقه
            </span>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
    default: () => ({}),
  },
  latestPosts: {
    type: Object,
    required: true,
    default: () => ({}),
  },
  defaultImage: {
    type: String,
    required: true,
  },
});


</script>

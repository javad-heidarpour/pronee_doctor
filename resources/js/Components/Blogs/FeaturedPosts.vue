<template>
  <div v-if="featuredPosts.length > 0" class="mb-12">
    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
      <span class="text-2xl">⭐</span>
      مقالات ویژه
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Link
        v-for="post in featuredPosts"
        :key="post.id"
        class="block"
        :href="`/${doctor.slug}/blog/${post.slug}`"
      >
        <article
          class="group bg-gradient-to-br from-teal-50 to-cyan-50 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300"
        >
          <div class="relative h-48 overflow-hidden">
            <img
              :src="post.featured_image || defaultImage"
              :alt="post.title"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
            />
            <div class="absolute top-4 right-4">
              <span
                class="px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-xs font-bold text-teal-700"
              >
                ویژه
              </span>
            </div>
          </div>

          <div class="p-6">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-lg">{{ post.category.icon }}</span>
              <span class="text-xs font-medium text-gray-600">
                {{ post.category.name }}
              </span>
            </div>
            <h4
              class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-teal-600 transition-colors"
            >
              {{ post.title }}
            </h4>
            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              {{ post.excerpt }}
            </p>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <svg
                  class="w-4 h-4"
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
                {{ post.reading_time }} دقیقه مطالعه
              </span>
              <span class="flex items-center gap-1">
                <svg
                  class="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                {{ post.views_count }}
              </span>
            </div>
          </div>
        </article>
      </Link>
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
  featuredPosts: {
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

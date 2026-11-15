<template>
  <tr class="hover:bg-slate-50 transition-colors group">
    <td class="px-6 py-4">
      <div class="flex items-center gap-3">
        <FeaturedImage :src="'/storage/' + post.featured_image" :alt="post.title" size="small" />
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold text-slate-900 truncate max-w-xs">
              {{ post.title }}
            </h3>
            <PinnedBadge :show="post.is_pinned" />
          </div>
          <p class="text-sm text-slate-500 truncate max-w-xs mt-0.5">
            {{ post.excerpt }}
          </p>
        </div>
      </div>
    </td>
    <td class="px-6 py-4">
      <span
        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700"
      >
        {{ getCategoryName(post.category_id) }}
      </span>
    </td>
    <td class="px-6 py-4">
      <div class="flex items-center justify-center gap-1">
        <button
          @click="$emit('view', post)"
          class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
          title="مشاهده"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        </button>
        <button
          @click="$emit('edit', post)"
          class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
          title="ویرایش"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
            />
          </svg>
        </button>
       
        <button
          @click="$emit('delete', post)"
          class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
          title="حذف"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
        </button>
      </div>
    </td>
    <td class="px-6 py-4">
      <div class="flex items-center gap-2">
        <div
          class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold"
        >
          {{ getInitial(post.author?.name || "U") }}
          {{ getInitial(post.author?.family || "U") }}
        </div>
        <span class="text-sm text-slate-700">{{
          post.author?.name + post.author?.family || "نامشخص"
        }}</span>
      </div>
    </td>
    <td class="px-6 py-4">
      <div class="flex items-center justify-center gap-1 flex-wrap">
        <StatusBadge :isPublished="post.is_published" />
        <FeaturedBadge
          :show="post.is_featured"
          class="!bg-amber-100 !text-amber-700 !shadow-none"
        />
      </div>
    </td>
    <td class="px-6 py-4 text-center"><ViewsCounter :viewsCount="post.views_count" /></td>
    <td class="px-6 py-4 text-center">
      <span class="text-sm text-slate-600">{{ post.reading_time }} دقیقه</span>
    </td>
    <td class="px-6 py-4 text-right">
      <PublishedDate :dateStr="post.published_at || post.created_at" />
    </td>
    
  </tr>
</template>

<script setup>
import FeaturedImage from "./FeaturedImage.vue";
import PinnedBadge from "./PinnedBadge.vue";
import StatusBadge from "./StatusBadge.vue";
import FeaturedBadge from "./FeaturedBadge.vue";
import ViewsCounter from "./ViewsCounter.vue";
import PublishedDate from "./PublishedDate.vue";

const props = defineProps({
  post: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
});
defineEmits(["view", "edit", "togglePin", "delete"]);

const getCategoryName = (id) => {
  const cat = props.categories.find((c) => c.id === id);
  return cat ? cat.name : "نامشخص";
};
const getInitial = (name) => (name ? name.charAt(0).toUpperCase() : "U");
</script>

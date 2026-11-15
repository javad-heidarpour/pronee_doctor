<template>
  <div>
    <!-- Table View -->
    <div
      v-if="viewMode === 'table'"
      class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full">
          <TableHeader />
          <tbody class="divide-y divide-slate-100">
            <PostRow
              v-for="post in posts"
              :key="post.id"
              :post="post"
              :categories="categories"
              @view="$emit('view', $event)"
              @edit="$emit('edit', $event)"
              @togglePin="$emit('togglePin', $event)"
              @delete="$emit('delete', $event)"
            />
          </tbody>
        </table>
      </div>
      <EmptyState v-if="posts.length === 0" message="مقاله‌ای یافت نشد" className="" />
    </div>

    <!-- Grid View -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="post in posts"
        :key="post.id"
        class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-slate-100 group"
      >
        <div
          class="relative h-48 bg-gradient-to-br from-indigo-100 to-purple-100 overflow-hidden"
        >
        
          <FeaturedImage :src="'/storage/' + post.featured_image" :alt="post.title" size="large" />
          <div class="absolute top-3 right-3 flex flex-col gap-2">
            <FeaturedBadge :show="post.is_featured" />
            <PinnedBadge :show="post.is_pinned" />
          </div>
          <div class="absolute top-3 left-3">
            <StatusBadge :isPublished="post.is_published" />
          </div>
        </div>

        <div class="p-5">
          <div class="flex items-center gap-2 mb-3">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700"
            >
              {{ getCategoryName(post.category_id) }}
            </span>
            <span class="text-xs text-slate-500 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

          <h3
            class="font-bold text-lg text-slate-900 mb-2 line-clamp-2 group-hover:text-indigo-600 transition"
          >
            {{ post.title }}
          </h3>
          <p class="text-sm text-slate-600 line-clamp-2 mb-4">{{ post.excerpt }}</p>

          <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold"
              >
                {{ getInitial(post.author?.name + post.author?.family || "U") }}
              </div>
              <div>
                <p class="text-xs font-medium text-slate-900">
                  {{ post.author?.name + post.author?.family || "نامشخص" }}
                </p>
                <p class="text-xs text-slate-500">
                  {{ formatDate(post.published_at || post.created_at) }}
                </p>
              </div>
            </div>
            <ViewsCounter :viewsCount="post.views_count" />
          </div>

          <div class="flex items-center gap-2 mt-4">
            <button
              @click="$emit('view', post)"
              class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
            >
              مشاهده
            </button>
            <button
              @click="$emit('edit', post)"
              class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition"
            >
              ویرایش
            </button>
            <button
              @click="$emit('delete', post)"
              class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
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
        </div>
      </div>
      <EmptyState
        v-if="posts.length === 0"
        message="مقاله‌ای یافت نشد"
        className="col-span-full"
      />
    </div>
  </div>
</template>

<script setup>
import TableHeader from "./TableHeader.vue";
import PostRow from "./PostRow.vue";
import EmptyState from "./EmptyState.vue";
import FeaturedImage from "./FeaturedImage.vue";
import FeaturedBadge from "./FeaturedBadge.vue";
import PinnedBadge from "./PinnedBadge.vue";
import StatusBadge from "./StatusBadge.vue";
import ViewsCounter from "./ViewsCounter.vue";

// ۱. تعریف props در یک متغیر تا بتوانیم داخل توابع به آن دسترسی داشته باشیم
const props = defineProps({
  posts: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  viewMode: { type: String, default: "table" },
});

defineEmits(["view", "edit", "togglePin", "delete"]);

// ۲. اصلاح تابع: حذف آرگومان دوم و استفاده مستقیم از props.categories
const getCategoryName = (id) => {
  const cat = props.categories.find((c) => c.id === id);
  return cat ? cat.name : "نامشخص";
};

// ۳. بهبود جزئی: جلوگیری از خطای "undefinedundefined" در صورت نبودن نویسنده
const getInitial = (name) => {
  if (!name || name === "undefinedundefined") return "U";
  return name.charAt(0).toUpperCase();
};

const formatDate = (dateStr) => {
  if (!dateStr) return "-";
  try {
    return new Intl.DateTimeFormat("fa-IR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }).format(new Date(dateStr));
  } catch {
    return "-";
  }
};
</script>
<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

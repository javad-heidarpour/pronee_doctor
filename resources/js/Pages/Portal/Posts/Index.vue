<template>
  <PortalLayout :doctor="doctor">
    <div class="min-h-screen w-full">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-black text-gray-900">مدیریت مقالات</h2>
      </div>
      <main class="">
        <!-- Stats Cards -->
        <StatisticsCards :stats="stats" />

        <!-- Filters & Search -->
        <Filters
          v-model:search="searchQuery"
          v-model:category="categoryFilter"
          v-model:status="statusFilter"
          v-model:viewMode="viewMode"
          :categories="categories"
        />
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium hover:shadow-lg hover:scale-105 transition-all duration-300 shadow-md"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          مقاله جدید
        </button>
        <!-- Posts Table / Grid -->
        <PostsTable
          :posts="paginatedPosts"
          :categories="categories"
          :viewMode="viewMode"
          @view="viewPost"
          @edit="editPost"
          @togglePin="togglePin"
          @delete="confirmDelete"
        />

        <!-- Pagination -->
        <Pagination
          v-model:currentPage="currentPage"
          :totalPages="totalPages"
          :startIndex="startIndex"
          :endIndex="endIndex"
          :totalFiltered="filteredPosts.length"
          :visiblePages="visiblePages"
        />
      </main>

      <!-- Delete Confirmation Modal -->
      <DeleteDialog
        v-model:show="showDeleteModal"
        :post="postToDelete"
        @confirm="deletePost"
      />
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import StatisticsCards from "./Components/Index/StatisticsCards.vue";
import Filters from "./Components/Index/Filters.vue";
import PostsTable from "./Components/Index/PostsTable.vue";
import Pagination from "./Components/Index/Pagination.vue";
import DeleteDialog from "./Components/Index/DeleteDialog.vue";

// 1. پذیرش هم آرایه و هم آبجکت (برای سازگاری با Pagination لاراول)
const props = defineProps({
   doctor: {
    type: Object,
    required: true,
  },
  posts: { type: [Array, Object], default: () => ({ data: [] }) },
  categories: { type: [Array, Object], default: () => ({ data: [] }) },
});

// State
const searchQuery = ref("");
const categoryFilter = ref("");
const statusFilter = ref("");
const viewMode = ref("table");
const currentPage = ref(1);
const perPage = 9;
const showDeleteModal = ref(false);
const postToDelete = ref(null);

// 2. استخراج ایمن آرایه از آبجکت اینرشیا
const postsArray = computed(() => {
  if (Array.isArray(props.posts)) {
    return props.posts;
  }
  // اگر آبجکت صفحه‌بندی اینرشیا باشد، آرایه اصلی درون ویژگی data قرار دارد
  return props.posts?.data || [];
});

// 3. استفاده از postsArray.value به جای props.posts
const stats = computed(() => {
  const list = postsArray.value;
  const total = list.length;
  const published = list.filter((p) => p.is_published).length;
  const featured = list.filter((p) => p.is_featured).length;
  const totalViews = list.reduce((sum, p) => sum + (p.views_count || 0), 0);
  return { total, published, featured, totalViews, totalGrowth: 12 };
});

const filteredPosts = computed(() => {
  return postsArray.value.filter((post) => {
    const matchesSearch =
      !searchQuery.value ||
      post.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (post.excerpt &&
        post.excerpt.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (post.keywords &&
        post.keywords.some((k) =>
          k.toLowerCase().includes(searchQuery.value.toLowerCase())
        ));

    const matchesCategory =
      !categoryFilter.value || post.category_id === parseInt(categoryFilter.value);

    let matchesStatus = true;
    if (statusFilter.value === "published") matchesStatus = post.is_published;
    else if (statusFilter.value === "draft") matchesStatus = !post.is_published;
    else if (statusFilter.value === "featured") matchesStatus = post.is_featured;
    else if (statusFilter.value === "pinned") matchesStatus = post.is_pinned;

    return matchesSearch && matchesCategory && matchesStatus;
  });
});

// Pagination
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredPosts.value.length / perPage))
);
const startIndex = computed(() => (currentPage.value - 1) * perPage);
const endIndex = computed(() => startIndex.value + perPage);
const paginatedPosts = computed(() =>
  filteredPosts.value.slice(startIndex.value, endIndex.value)
);

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
  let end = Math.min(totalPages.value, start + maxVisible - 1);
  if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

// Actions
const openCreateModal = () => {
  router.visit(route("portal.posts.create"));
};

// const viewPost = (post) => {
//   router.visit(route("doctor.blog.show", post.slug));
// };
const viewPost = (post) => {
  const url = route("doctor.blog.show", {
    doctor: post.doctor.slug,
    post: post.slug,
  });

  // باز کردن لینک در تب جدید
  window.open(url, "_blank");

  // router.visit(
  //   route("doctor.blog.show", {
  //     doctor: post.doctor.slug,
  //     post: post.slug,
  //   })
  // );
};

const editPost = (post) => {
  router.visit(route("portal.posts.edit", post.id));
};

const togglePin = (post) => {
  router.patch(route("portal.posts.toggle-pin", post.id));
};

const confirmDelete = (post) => {
  postToDelete.value = post;
  showDeleteModal.value = true;
};

const deletePost = () => {
  if (!postToDelete.value) return;

  router.delete(route("portal.posts.destroy", postToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      postToDelete.value = null;
    },
    onError: (errors) => {
      console.error("خطا در حذف مقاله:", errors);
    },
  });
};
</script>

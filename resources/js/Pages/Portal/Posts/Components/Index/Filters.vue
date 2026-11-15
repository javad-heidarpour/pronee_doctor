<template>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
    <div class="flex flex-col lg:flex-row gap-4">
      <!-- Search -->
      <div class="flex-1 relative">
        <svg
          class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
        <input
          :value="search"
          @input="$emit('update:search', $event.target.value)"
          type="text"
          placeholder="جستجو در عنوان، خلاصه یا کلمات کلیدی..."
          class="w-full pr-10 pl-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
        />
      </div>

      <!-- Category Filter -->
      <select
        :value="category"
        @change="$emit('update:category', $event.target.value)"
        class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition min-w-[160px] appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236366f1%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-no-repeat bg-left bg-[length:20px] pr-4 pl-10"
      >
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ cat.name }}
        </option>
      </select>

      <!-- Status Filter -->
      <select
        :value="status"
        @change="$emit('update:status', $event.target.value)"
        class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition min-w-[160px] appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236366f1%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-no-repeat bg-left bg-[length:20px] pr-4 pl-10"
      >
        <option value="">همه وضعیت‌ها</option>
        <option value="published">منتشر شده</option>
        <option value="draft">پیش‌نویس</option>
        <option value="featured">ویژه</option>
        <option value="pinned">پین شده</option>
      </select>

      <!-- View Toggle -->
      <div
        class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl p-1"
      >
        <button
          @click="$emit('update:viewMode', 'table')"
          :class="[
            'p-2 rounded-lg transition',
            viewMode === 'table'
              ? 'bg-white shadow-sm text-indigo-600'
              : 'text-slate-500 hover:text-slate-700',
          ]"
          title="نمای جدولی"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 10h16M4 14h16M4 18h16"
            />
          </svg>
        </button>
        <button
          @click="$emit('update:viewMode', 'grid')"
          :class="[
            'p-2 rounded-lg transition',
            viewMode === 'grid'
              ? 'bg-white shadow-sm text-indigo-600'
              : 'text-slate-500 hover:text-slate-700',
          ]"
          title="نمای کارتی"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
            />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  search: { type: String, default: "" },
  category: { type: [String, Number], default: "" },
  status: { type: String, default: "" },
  viewMode: { type: String, default: "table" },
  categories: { type: Array, default: () => [] },
});
defineEmits(["update:search", "update:category", "update:status", "update:viewMode"]);
</script>

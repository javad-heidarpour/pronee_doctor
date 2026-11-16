<template>
  <div
    v-if="images && images.length > 0"
    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 hover:shadow-lg transition-shadow duration-300"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-purple-50 rounded-xl">
          <svg
            class="w-6 h-6 text-purple-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">گالری تصاویر مطب</h2>
      </div>
    </div>

    <!-- فیلتر دسته‌بندی -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button
        type="button"
        @click="selectedCategory = 'all'"
        :class="[
          'px-4 py-2 rounded-xl text-sm font-medium transition-all',
          selectedCategory === 'all'
            ? 'bg-purple-600 text-white shadow-md shadow-purple-200'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
        ]"
      >
        همه تصاویر
        <span
          class="mr-1 px-1.5 py-0.5 text-xs rounded-full"
          :class="selectedCategory === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'"
        >
          {{ images.length }}
        </span>
      </button>

      <button
        v-for="cat in categories"
        :key="cat.value"
        @click="selectedCategory = cat.value"
        :class="[
          'px-4 py-2 rounded-xl text-sm font-medium transition-all',
          selectedCategory === cat.value
            ? 'bg-purple-600 text-white shadow-md shadow-purple-200'
            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
        ]"
      >
        {{ cat.label }}
        <span
          class="mr-1 px-1.5 py-0.5 text-xs rounded-full"
          :class="
            selectedCategory === cat.value
              ? 'bg-white/20 text-white'
              : 'bg-gray-200 text-gray-600'
          "
        >
          {{ cat.count }}
        </span>
      </button>
    </div>

    <!-- Grid تصاویر -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="(image, index) in filteredImages"
        :key="image.id"
        @click="openLightbox(index)"
        class="group relative aspect-square rounded-2xl overflow-hidden cursor-pointer bg-gray-100"

      >
      
        <img
          :src="image.image_url"
          :alt="image.caption || 'تصویر مطب'"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          loading="lazy"
        />

        <!-- Overlay با اطلاعات -->
        <div
          class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4"
        >
          <p class="text-white font-bold text-sm mb-1">
            {{ image.caption || "بدون توضیح" }}
          </p>
          <span class="text-white/80 text-xs">
            {{ image.category_name }}
          </span>
        </div>

        <!-- آیکون زوم -->
        <div
          class="absolute top-3 left-3 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        >
          <svg
            class="w-5 h-5 text-gray-700"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
            />
          </svg>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredImages.length === 0" class="text-center py-12">
      <svg
        class="w-16 h-16 text-gray-300 mx-auto mb-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
      <p class="text-gray-500 font-medium">تصویری در این دسته‌بندی وجود ندارد</p>
    </div>

    <!-- ✅ Lightbox Modal -->
    <Teleport to="body">
      <Transition name="lightbox">
        <div
          v-if="lightboxOpen"
          class="fixed inset-0 bg-black/95 z-[9999] flex items-center justify-center"
          @click.self="closeLightbox"
        >
          <!-- دکمه بستن -->
          <button
            @click="closeLightbox"
            class="absolute top-4 right-4 w-12 h-12 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-all z-10"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>

          <!-- دکمه قبلی -->
          <button
            v-if="currentImageIndex > 0"
            @click.stop="previousImage"
            class="absolute right-4 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-all z-10"
          >
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>

          <!-- تصویر اصلی -->
          <Transition name="fade" mode="out-in">
            <div
              :key="currentImageIndex"
              class="max-w-6xl max-h-[85vh] mx-4 flex flex-col items-center"
            >
              <img
                :src="filteredImages[currentImageIndex].image_url"
                :alt="filteredImages[currentImageIndex].caption"
                class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-2xl"
              />

              <!-- اطلاعات تصویر -->
              <div class="mt-4 text-center text-white max-w-2xl">
                <p class="text-lg font-bold mb-1">
                  {{ filteredImages[currentImageIndex].caption || "بدون توضیح" }}
                </p>
                <p class="text-white/70 text-sm">
                  {{ filteredImages[currentImageIndex].category_name }}
                  &nbsp;•&nbsp;
                  {{ currentImageIndex + 1 }} از {{ filteredImages.length }}
                </p>
              </div>
            </div>
          </Transition>

          <!-- دکمه بعدی -->
          <button
            v-if="currentImageIndex < filteredImages.length - 1"
            @click.stop="nextImage"
            class="absolute left-4 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white transition-all z-10"
          >
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              />
            </svg>
          </button>

          <!-- Thumbnail Strip -->
          <div
            v-if="filteredImages.length > 1"
            class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 max-w-[90vw] overflow-x-auto hide-scrollbar py-2 px-4 bg-black/50 backdrop-blur-sm rounded-2xl"
          >
            <button
              v-for="(img, idx) in filteredImages"
              :key="img.id"
              @click.stop="currentImageIndex = idx"
              :class="[
                'w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 transition-all border-2',
                currentImageIndex === idx
                  ? 'border-white scale-110 shadow-lg'
                  : 'border-transparent opacity-60 hover:opacity-100',
              ]"
            >
              <img
                :src="img.image_url"
                :alt="img.caption"
                class="w-full h-full object-cover"
              />
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
const ImgPath = "\\storage\\doctor-gallery\\";

const props = defineProps({
  images: {
    type: Array,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
});

// --- STATE ---
const selectedCategory = ref("all");
const lightboxOpen = ref(false);
const currentImageIndex = ref(0);

// --- COMPUTED ---
const filteredImages = computed(() => {
  if (selectedCategory.value === "all") {
    return props.images;
  }
  return props.images.filter((img) => img.category === selectedCategory.value);
});

// --- METHODS ---
const openLightbox = (index) => {
  currentImageIndex.value = index;
  lightboxOpen.value = true;
  document.body.style.overflow = "hidden";
};

const closeLightbox = () => {
  lightboxOpen.value = false;
  document.body.style.overflow = "";
};

const nextImage = () => {
  if (currentImageIndex.value < filteredImages.value.length - 1) {
    currentImageIndex.value++;
  }
};

const previousImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--;
  }
};

// --- KEYBOARD NAVIGATION ---
const handleKeydown = (e) => {
  if (!lightboxOpen.value) return;

  if (e.key === "Escape") {
    closeLightbox();
  } else if (e.key === "ArrowRight") {
    previousImage(); // در RTL، راست = قبلی
  } else if (e.key === "ArrowLeft") {
    nextImage(); // در RTL، چپ = بعدی
  }
};

onMounted(() => {
  document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener("keydown", handleKeydown);
});

// ریست ایندکس وقتی فیلتر تغییر می‌کند
watch(selectedCategory, () => {
  currentImageIndex.value = 0;
});
</script>

<style scoped>
/* انیمیشن Lightbox */
.lightbox-enter-active,
.lightbox-leave-active {
  transition: all 0.3s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
  opacity: 0;
}

/* انیمیشن Fade برای تغییر تصویر */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: scale(0.95);
}

.fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* مخفی کردن اسکرولبار */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

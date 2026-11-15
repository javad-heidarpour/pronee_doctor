<template>
  <PortalLayout :doctor="doctor">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-900">مدیریت گالری تصاویر</h2>
      <button
        @click="showUploadModal = true"
        class="bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg"
      >
        + آپلود تصویر جدید
      </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="img in images"
        :key="img.id"
        class="relative group aspect-square rounded-2xl overflow-hidden bg-gray-100"
      >
        <img
          :src="img.image_url"
          class="w-full h-full object-cover placeholder:text-gray-100"
        />
        <div
          class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3"
        >
          <p class="text-white text-sm font-bold">{{ img.caption || "بدون توضیح" }}</p>
          <p class="text-white/80 text-xs">{{ img.category_name }}</p>
          <div class="flex gap-2 mt-2">
            <button
              @click="toggleActive(img)"
              :class="img.is_active ? 'bg-green-500' : 'bg-gray-500'"
              class="flex-1 text-white text-xs py-1 rounded-lg"
            >
              {{ img.is_active ? "فعال" : "غیرفعال" }}
            </button>
            <button
              @click="deleteImage(img.id)"
              class="bg-red-500 text-white text-xs py-1 px-3 rounded-lg"
            >
              حذف
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <Teleport to="body">
      <div
        v-if="showUploadModal"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-3xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">آپلود تصاویر جدید</h3>
          <form @submit.prevent="upload" class="space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">دسته‌بندی</label>
              <select
                v-model="form.category"
                required
                class="w-full mt-1 block px-4 py-3 border border-gray-200 rounded-xl"
              >
                <option v-for="(label, value) in categories" :key="value" :value="value">
                  {{ label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">توضیحات</label>
              <input
                type="text"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                v-model="form.caption"
              />
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2"
                >تصاویر (حداکثر ۱۰)</label
              >
              <input
                type="file"
                multiple
                accept="image/*"
                @change="handleFiles"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                required
              />
            </div>
            <div class="flex gap-2">
              <button
                type="submit"
                class="flex-1 bg-teal-600 text-white py-3 rounded-xl font-bold"
              >
                آپلود
              </button>
              <button
                type="button"
                @click="showUploadModal = false"
                class="flex-1 bg-gray-100 py-3 rounded-xl font-bold"
              >
                انصراف
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </PortalLayout>
</template>

<script setup>
import PortalLayout from "@/Layouts/PortalLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// ✅ دریافت اطلاعات پزشک از auth
const page = usePage();


const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  images: Array,
  caption: String,
  categories: Object,
});

const showUploadModal = ref(false);
const form = ref({ category: "clinic", caption: null, images: [] });

const handleFiles = (e) => {
  form.value.images = Array.from(e.target.files);
};

const upload = () => {
  const formData = new FormData();
  formData.append("category", form.value.category);
  formData.append("caption", form.value.caption);
  form.value.images.forEach((file, i) => formData.append(`images[${i}]`, file));

  router.post(route("portal.gallery.store"), formData, {
    onSuccess: () => {
      showUploadModal.value = false;
      form.value = { category: "clinic", caption: null, images: [] };
    },
  });
};

const toggleActive = (img) => {
  router.put(route("portal.gallery.update", img.id), {
    is_active: !img.is_active,
    category: img.category,
  });
};

const deleteImage = (id) => {
  if (confirm("حذف شود؟")) router.delete(route("portal.gallery.destroy", id));
};
</script>

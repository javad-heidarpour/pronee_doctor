<!-- resources/js/Pages/Doctor/Clinics.vue -->
<template>
  <PortalLayout :doctor="doctor">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-bold text-gray-900">مدیریت مطب‌ها</h2>
      <button
        @click="openModal()"
        class="bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all"
      >
        + افزودن مطب جدید
      </button>
    </div>
    <!-- لیست مطب‌ها -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="clinic in clinics"
        :key="clinic.id"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-bold text-gray-900 text-lg mb-1">{{ clinic.name }}</h3>
            <p class="text-sm text-gray-500">{{ clinic.city?.name }}</p>
          </div>
          <div class="flex gap-2">
            <button @click="openModal(clinic)" class="p-2 hover:bg-gray-100 rounded-lg">
              <svg
                class="w-5 h-5 text-gray-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
            </button>
            <button
              @click="deleteClinic(clinic.id)"
              class="p-2 hover:bg-red-50 rounded-lg"
            >
              <svg
                class="w-5 h-5 text-red-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"
                />
              </svg>
            </button>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-2">{{ clinic.full_address }}</p>
        <p v-if="clinic.phone" class="text-sm text-gray-500">📞 {{ clinic.phone }}</p>
      </div>
    </div>
    <!-- Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
      >
        <div class="bg-white rounded-3xl max-w-md w-full p-6">
          <h3 class="text-xl font-bold mb-4">
            {{ editingClinic ? "ویرایش مطب" : "افزودن مطب جدید" }}
          </h3>
    <!-- {{ cities }} -->

          <form @submit.prevent="submitClinic" class="space-y-4">
            <FormField v-model="form.name" label="نام مطب" required />
            <FormField v-model="form.full_address" label="آدرس کامل" required />
            <FormField v-model="form.phone" label="تلفن" />
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">شهر</label>

              <select
                v-model="form.city_id"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl"
              >
                <option value="">انتخاب شهر</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.name }}
                </option>
              </select>
            </div>
            <div class="flex gap-2">
              <button
                type="submit"
                class="flex-1 bg-teal-600 text-white py-3 rounded-xl font-bold"
              >
                ذخیره
              </button>
              <button
                type="button"
                @click="showModal = false"
                class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold"
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
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import FormField from "@/Components/Portal/FormField.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  clinics: Array,
  cities: Array,
  
});

const showModal = ref(false);
const editingClinic = ref(null);
const form = ref({ name: "", full_address: "", phone: "", city_id: "" });

const openModal = (clinic = null) => {
  editingClinic.value = clinic;
  form.value = clinic
    ? { ...clinic }
    : { name: "", full_address: "", phone: "", city_id: "" };
  showModal.value = true;
};

const submitClinic = () => {
  if (editingClinic.value) {
    router.put(route("portal.clinics.update", editingClinic.value.id), form.value);
  } else {
    router.post(route("portal.clinics.store"), form.value);
  }
  showModal.value = false;
};

const deleteClinic = (id) => {
  if (confirm("آیا از حذف این مطب اطمینان دارید؟")) {
    router.delete(route("portal.clinics.destroy", id));
  }
};
</script>

<!-- resources/js/Pages/Doctor/Clinics.vue -->
<template>
  <PortalLayout :doctor="doctor">
    <div class="mb-6 flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center">
      <h2 class="text-xl font-bold text-gray-900">مدیریت مطب‌ها</h2>
      <button
        type="button"
        v-if="!isSecretary"
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
          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <button type="button" aria-label="ویرایش مطب" @click="openModal(clinic)" class="p-2 hover:bg-gray-100 rounded-lg">
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
              type="button"
              aria-label="حذف مطب"
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
      >
        <div role="dialog" aria-modal="true" aria-labelledby="clinic-modal-title" class="max-h-[calc(100dvh-1.5rem)] w-full max-w-md overflow-y-auto rounded-3xl bg-white p-4 shadow-2xl sm:p-6">
          <h3 id="clinic-modal-title" class="mb-4 text-lg font-bold sm:text-xl">
            {{ editingClinic ? "ویرایش مطب" : "افزودن مطب جدید" }}
          </h3>
    <!-- {{ cities }} -->

          <form @submit.prevent="submitClinic" class="space-y-4">
            <FormField v-model="form.name" label="نام مطب" required :error="$page.props.errors?.name" />
            <FormField v-model="form.full_address" label="آدرس کامل" required :error="$page.props.errors?.full_address" />
            <FormField v-model="form.phone" label="تلفن" :error="$page.props.errors?.phone" />
            <div>
              <label for="clinic-city" class="mb-2 block text-sm font-bold text-gray-700">شهر</label>

              <select
                id="clinic-city"
                v-model="form.city_id"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl"
              >
                <option value="">انتخاب شهر</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.name }}
                </option>
              </select>
              <p v-if="$page.props.errors?.city_id" role="alert" class="mt-1 text-xs text-red-600">{{ $page.props.errors.city_id }}</p>
            </div>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
              <button
                type="submit"
                :disabled="processing"
                class="flex-1 bg-teal-600 text-white py-3 rounded-xl font-bold"
              >
                {{ processing ? "در حال ذخیره..." : "ذخیره" }}
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
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
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

const page = usePage();
const isSecretary = computed(() => page.props.auth?.user?.role === "secretary");

const showModal = ref(false);
const processing = ref(false);
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
  if (!form.value.name?.trim() || !form.value.full_address?.trim()) return;
  processing.value = true;
  const options = {
    preserveScroll: true,
    onError: () => { showModal.value = true; },
    onFinish: () => { processing.value = false; },
    onSuccess: () => { showModal.value = false; },
  };

  if (editingClinic.value) {
    router.put(route("portal.clinics.update", editingClinic.value.id), form.value, options);
  } else {
    router.post(route("portal.clinics.store"), form.value, options);
  }
};

const deleteClinic = (id) => {
  if (confirm("آیا از حذف این مطب اطمینان دارید؟")) {
    router.delete(route("portal.clinics.destroy", id));
  }
};
</script>

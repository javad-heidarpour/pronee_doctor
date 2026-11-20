<template>
  <PortalLayout :doctor="doctor">
    <div class="mb-6 flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-900">مدیریت مطب‌ها و شعب</h2>
        <p class="mt-1 text-sm text-gray-500">نشانی، اطلاعات تماس و موقعیت جغرافیایی هر شعبه را مدیریت کنید.</p>
      </div>
      <button
        v-if="!isSecretary"
        type="button"
        @click="openModal()"
        class="rounded-xl bg-gradient-to-l from-teal-500 to-cyan-600 px-5 py-2.5 font-bold text-white shadow-lg transition-all hover:shadow-xl"
      >
        + افزودن مطب جدید
      </button>
    </div>

    <ActionDialog
      :open="deleteDialogOpen"
      title="حذف مطب"
      description="مطب به‌صورت نرم‌حذف از فهرست فعال خارج می‌شود. اطلاعات وابسته در پایگاه داده باقی می‌ماند."
      submit-label="حذف مطب"
      @close="deleteDialogOpen = false"
      @submit="confirmDeleteClinic"
    />

    <div v-if="clinics?.length" class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <article
        v-for="clinic in clinics"
        :key="clinic.id"
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
      >
        <div class="mb-4 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="mb-1 text-lg font-bold text-gray-900">{{ clinic.name }}</h3>
            <p class="text-sm text-gray-500">{{ clinic.city?.name || "شهر ثبت نشده" }}</p>
          </div>
          <div v-if="!isSecretary" class="flex shrink-0 gap-2">
            <button
              type="button"
              aria-label="ویرایش مطب"
              @click="openModal(clinic)"
              class="rounded-lg p-2 hover:bg-gray-100"
            >
              <svg class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              type="button"
              aria-label="حذف مطب"
              @click="deleteClinic(clinic.id)"
              class="rounded-lg p-2 hover:bg-red-50"
            >
              <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
              </svg>
            </button>
          </div>
        </div>

        <p class="mb-2 text-sm leading-7 text-gray-700">{{ clinic.full_address || "نشانی ثبت نشده" }}</p>
        <p v-if="clinic.phone" class="mb-2 text-sm text-gray-500">تلفن: {{ clinic.phone }}</p>
        <a
          v-if="hasCoordinates(clinic)"
          :href="mapUrl(clinic)"
          target="_blank"
          rel="noopener noreferrer"
          class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-teal-700 hover:text-teal-900"
        >
          مشاهده موقعیت روی نقشه
          <span aria-hidden="true">↗</span>
        </a>
        <p v-else class="mt-2 text-xs text-amber-700">موقعیت جغرافیایی ثبت نشده است.</p>
      </article>
    </div>
    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
      <p class="font-bold text-slate-700">هنوز مطبی ثبت نشده است.</p>
      <p class="mt-2 text-sm text-slate-500">برای نمایش نشانی و زمان‌های نوبت، ابتدا شعبه خود را اضافه کنید.</p>
    </div>

    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4"
      >
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="clinic-modal-title"
          class="max-h-[calc(100dvh-1.5rem)] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-4 shadow-2xl sm:p-6"
        >
          <h3 id="clinic-modal-title" class="mb-2 text-lg font-bold sm:text-xl">
            {{ editingClinic ? "ویرایش مطب" : "افزودن مطب جدید" }}
          </h3>
          <p class="mb-5 text-sm text-gray-500">مختصات را از نقشه کپی کنید؛ ثبت آن اختیاری است.</p>

          <form @submit.prevent="submitClinic" class="space-y-4">
            <FormField v-model="form.name" label="نام مطب" required :error="$page.props.errors?.name" />
            <FormField v-model="form.full_address" label="نشانی کامل" required :error="$page.props.errors?.full_address" />
            <FormField v-model="form.phone" label="تلفن تماس" :error="$page.props.errors?.phone" />

            <div>
              <label for="clinic-city" class="mb-2 block text-sm font-bold text-gray-700">شهر</label>
              <select
                id="clinic-city"
                v-model="form.city_id"
                required
                class="w-full rounded-xl border border-gray-200 px-4 py-3"
              >
                <option value="">انتخاب شهر</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.name }}
                </option>
              </select>
              <p v-if="$page.props.errors?.city_id" role="alert" class="mt-1 text-xs text-red-600">{{ $page.props.errors.city_id }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <label for="clinic-lat" class="mb-2 block text-sm font-bold text-gray-700">عرض جغرافیایی (Latitude)</label>
                <input id="clinic-lat" v-model="form.lat" type="number" step="any" min="-90" max="90" inputmode="decimal" class="w-full rounded-xl border border-gray-200 px-4 py-3" placeholder="مثلاً 37.4742" />
                <p v-if="$page.props.errors?.lat" role="alert" class="mt-1 text-xs text-red-600">{{ $page.props.errors.lat }}</p>
              </div>
              <div>
                <label for="clinic-lng" class="mb-2 block text-sm font-bold text-gray-700">طول جغرافیایی (Longitude)</label>
                <input id="clinic-lng" v-model="form.lng" type="number" step="any" min="-180" max="180" inputmode="decimal" class="w-full rounded-xl border border-gray-200 px-4 py-3" placeholder="مثلاً 57.3238" />
                <p v-if="$page.props.errors?.lng" role="alert" class="mt-1 text-xs text-red-600">{{ $page.props.errors.lng }}</p>
              </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row">
              <button type="submit" :disabled="processing" class="flex-1 rounded-xl bg-teal-600 py-3 font-bold text-white disabled:opacity-60">
                {{ processing ? "در حال ذخیره..." : "ذخیره اطلاعات مطب" }}
              </button>
              <button type="button" @click="showModal = false" class="flex-1 rounded-xl bg-gray-100 py-3 font-bold text-gray-700">
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
import ActionDialog from "@/Components/Portal/ActionDialog.vue";

const props = defineProps({
  doctor: { type: Object, required: true },
  clinics: { type: Array, default: () => [] },
  cities: { type: Array, default: () => [] },
});

const page = usePage();
const isSecretary = computed(() => page.props.auth?.user?.role === "secretary");
const showModal = ref(false);
const processing = ref(false);
const deleteDialogOpen = ref(false);
const clinicToDelete = ref(null);
const editingClinic = ref(null);
const emptyForm = () => ({ name: "", full_address: "", phone: "", city_id: "", lat: "", lng: "" });
const form = ref(emptyForm());

const hasCoordinates = (clinic) =>
  clinic?.Latitude !== null && clinic?.Latitude !== undefined && clinic?.Latitude !== "" &&
  clinic?.Longitude !== null && clinic?.Longitude !== undefined && clinic?.Longitude !== "";

const mapUrl = (clinic) => {
  const lat = encodeURIComponent(clinic.Latitude);
  const lng = encodeURIComponent(clinic.Longitude);
  return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=17/${lat}/${lng}`;
};

const openModal = (clinic = null) => {
  editingClinic.value = clinic;
  form.value = clinic
    ? {
        name: clinic.name || "",
        full_address: clinic.full_address || "",
        phone: clinic.phone || "",
        city_id: clinic.city_id || "",
        lat: clinic.Latitude ?? "",
        lng: clinic.Longitude ?? "",
      }
    : emptyForm();
  showModal.value = true;
};

const submitClinic = () => {
  if (!form.value.name?.trim() || !form.value.full_address?.trim() || !form.value.city_id) return;

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
  clinicToDelete.value = id;
  deleteDialogOpen.value = true;
};

const confirmDeleteClinic = () => {
  if (!clinicToDelete.value) return;

  router.delete(route("portal.clinics.destroy", clinicToDelete.value), {
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false;
      clinicToDelete.value = null;
    },
  });
};
</script>

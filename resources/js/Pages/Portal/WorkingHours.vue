<template>
  <PortalLayout :doctor="doctor">
    <ActionDialog :open="deleteDialogOpen" title="حذف بازه کاری" description="آیا از حذف این بازه کاری اطمینان دارید؟" submit-label="حذف بازه" @close="deleteDialogOpen = false" @submit="confirmDeleteHour" />
    <!-- آمار -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <StatCard label="کل بازه‌ها" :value="workingHours.total" icon="" color="blue" />
      <StatCard label="فعال" :value="activeCount" icon="✅" color="green" />
      <StatCard label="غیرفعال" :value="inactiveCount" icon="⏸️" color="gray" />
      <StatCard label="مطب‌ها" :value="clinics.length" icon="🏥" color="purple" />
    </div>

    <!-- فیلترها و جستجو -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
      <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">جستجو</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="نام مطب یا شهر"
            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
          />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">مطب</label>
          <select
            v-model="filters.clinic_id"
            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
          >
            <option value="">همه مطب‌ها</option>
            <option v-for="clinic in clinics" :key="clinic.id" :value="clinic.id">
              {{ clinic.city?.name }} - {{ clinic.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">وضعیت</label>
          <select
            v-model="filters.is_active"
            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
          >
            <option value="">همه</option>
            <option value="true">فعال</option>
            <option value="false">غیرفعال</option>
          </select>
        </div>

        <div class="flex items-end gap-2">
          <button
            type="submit"
            class="flex-1 bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-4 py-2 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all"
          >
            اعمال فیلتر
          </button>
          <button
            type="button"
            @click="resetFilters"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl font-bold hover:bg-gray-200 transition-all"
          >
            پاک کردن
          </button>
        </div>
      </form>
    </div>

    <!-- دکمه افزودن -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-gray-900">نمای گانت ساعات کاری</h2>
      <button
        @click="openModal()"
        class="bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all"
      >
        + افزودن بازه جدید
      </button>
    </div>

    <!-- ✅ نمایش گانت‌مانند -->
    <div
      class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6"
    >
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead
            class="bg-gradient-to-l from-teal-50 to-cyan-50 border-b border-gray-200"
          >
            <tr>
              <th
                class="px-4 py-3 text-right text-xs font-bold text-gray-700 sticky right-0 bg-teal-50 z-10 min-w-[200px]"
              >
                مطب / روز
              </th>
              <th
                v-for="day in days"
                :key="day.value"
                class="px-4 py-3 text-center text-xs font-bold text-gray-700 min-w-[140px]"
              >
                {{ day.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <!-- ردیف برای هر مطب -->
            <tr v-for="clinic in clinics" :key="clinic.id">
              <td class="px-4 py-3 sticky right-0 bg-white z-10 border-l border-gray-100">
                <div class="flex items-center gap-2">
                  <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center text-white text-xs font-bold"
                  >
                    🏥
                  </div>
                  <div>
                    <p class="font-bold text-gray-900 text-sm">{{ clinic.name }}</p>
                    <p class="text-xs text-gray-500">{{ clinic.city?.name }}</p>
                  </div>
                </div>
              </td>
              <!-- سلول برای هر روز -->
              <td
                v-for="day in days"
                :key="day.value"
                class="px-2 py-2 border-l border-gray-50"
              >
                <div class="space-y-1">
                  <div
                    v-for="wh in getHoursForClinicAndDay(clinic.id, day.value)"
                    :key="wh.id"
                    :class="[
                      'rounded-lg px-2 py-1.5 text-xs font-medium transition-all cursor-pointer group',
                      wh.is_active
                        ? 'bg-gradient-to-l from-teal-400 to-cyan-500 text-white hover:shadow-md hover:scale-105'
                        : 'bg-gray-200 text-gray-500 line-through',
                    ]"
                    @click="openEditModal(wh)"
                  >
                    <div class="flex items-center justify-between gap-1">
                      <span class="truncate">
                        {{ wh.start_time.substr(0, 5) }} - {{ wh.end_time.substr(0, 5) }}
                      </span>
                      <!-- دکمه‌های عملیات -->
                      <div
                        class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                      >
                        <button
                          @click.stop="toggleStatus(wh)"
                          :title="wh.is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                          class="w-5 h-5 rounded-full bg-white/30 hover:bg-white/50 flex items-center justify-center"
                        >
                          <svg
                            v-if="wh.is_active"
                            class="w-3 h-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                            />
                          </svg>
                          <svg
                            v-else
                            class="w-3 h-3"
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
                        </button>
                        <button
                          @click.stop="deleteHour(wh.id)"
                          title="حذف"
                          class="w-5 h-5 rounded-full bg-white/30 hover:bg-red-500 flex items-center justify-center"
                        >
                          <svg
                            class="w-3 h-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"
                            />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div
                    v-if="getHoursForClinicAndDay(clinic.id, day.value).length === 0"
                    class="text-center text-gray-300 text-xs py-2"
                  >
                    -
                  </div>
                </div>
              </td>
            </tr>
            <tr v-if="clinics.length === 0">
              <td :colspan="days.length + 1" class="px-6 py-12 text-center text-gray-500">
                <p>ابتدا یک مطب اضافه کنید</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="workingHours.last_page > 1"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
    >
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600">
          نمایش {{ workingHours.from }} تا {{ workingHours.to }} از
          {{ workingHours.total }}
        </p>
        <div class="flex gap-2">
          <button
            v-for="(link, idx) in workingHours.links"
            :key="idx"
            @click="changePage(link.url)"
            :disabled="!link.url"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
              link.active
                ? 'bg-teal-600 text-white'
                : link.url
                ? 'bg-gray-100 hover:bg-gray-200'
                : 'bg-gray-50 text-gray-400 cursor-not-allowed',
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Modal افزودن/ویرایش -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showModal"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="showModal = false"
        >
          <div class="bg-white rounded-3xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-bold text-gray-900">
                {{ editingHour ? "ویرایش بازه" : "افزودن بازه جدید" }}
              </h3>
              <button
                @click="showModal = false"
                class="p-2 hover:bg-gray-100 rounded-full"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- نمایش خطای تکراری -->
            <div
              v-if="$page.props.errors.duplicate"
              class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4"
            >
              <div class="flex items-start gap-2">
                <svg
                  class="w-5 h-5 text-amber-600 flex-shrink-0"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
                <p class="text-sm text-amber-800">{{ $page.props.errors.duplicate }}</p>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">مطب</label>
                <select
                  v-model="form.clinic_id"
                  required
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                >
                  <option value="">انتخاب مطب</option>
                  <option v-for="c in clinics" :key="c.id" :value="c.id">
                    {{ c.city?.name }} - {{ c.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">روز هفته</label>
                <select
                  v-model="form.day_of_week"
                  required
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                >
                  <option v-for="day in days" :key="day.value" :value="day.value">
                    {{ day.label }}
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2"
                    >ساعت شروع</label
                  >
                  <input
                    v-model="form.start_time"
                    type="time"
                    required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2"
                    >ساعت پایان</label
                  >
                  <input
                    v-model="form.end_time"
                    type="time"
                    required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                  />
                </div>
              </div>

              <!-- مدت هر نوبت -->
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                  مدت هر نوبت
                </label>

                <select
                  v-model.number="form.duration_minutes"
                  required
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                >
                  <option :value="2">۲ دقیقه</option>
                  <option :value="5">۵ دقیقه</option>
                  <option :value="10">۱۰ دقیقه</option>
                  <option :value="15">۱۵ دقیقه</option>
                  <option :value="20">۲۰ دقیقه</option>
                  <option :value="25">۲۵ دقیقه</option>
                  <option :value="30">۳۰ دقیقه</option>
                </select>

                <p class="text-xs text-gray-500 mt-2">
                  این مقدار برای تولید خودکار نوبت‌های این بازه استفاده می‌شود.
                </p>
              </div>

              <!-- پیش‌نمایش بازه -->
              <div
                v-if="form.start_time && form.end_time"
                class="bg-gradient-to-l from-teal-50 to-cyan-50 rounded-xl p-3"
              >
                <p class="text-xs text-gray-600 mb-2">پیش‌نمایش بازه کاری:</p>

                <p class="font-bold text-teal-700">
                  {{ days.find((d) => d.value === Number(form.day_of_week))?.label }}
                  : {{ form.start_time.substring(0, 5) }} تا
                  {{ form.end_time.substring(0, 5) }}
                </p>

                <div class="flex items-center gap-2 mt-2 text-sm text-teal-800">
                  <span>⏱️</span>
                  <span>
                    مدت هر نوبت:
                    <strong>{{ form.duration_minutes }} دقیقه</strong>
                  </span>
                </div>
              </div>

              <div class="flex gap-2">
                <button
                  type="submit"
                  :disabled="processing"
                  class="flex-1 bg-teal-600 text-white py-3 rounded-xl font-bold hover:bg-teal-700 transition-all disabled:opacity-50"
                >
                  {{ processing ? "در حال ذخیره..." : "ذخیره" }}
                </button>
                <button
                  type="button"
                  @click="showModal = false"
                  class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all"
                >
                  انصراف
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </PortalLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import ActionDialog from "@/Components/Portal/ActionDialog.vue";
import StatCard from "@/Components/Portal/StatCard.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  clinics: Array,
  workingHours: Object,
  filters: Object,
});

const page = usePage();

// روزهای هفته
const days = [
  { value: 0, label: "شنبه" },
  { value: 1, label: "یکشنبه" },
  { value: 2, label: "دوشنبه" },
  { value: 3, label: "سه‌شنبه" },
  { value: 4, label: "چهارشنبه" },
  { value: 5, label: "پنجشنبه" },
  { value: 6, label: "جمعه" },
];

// فیلترها
const filters = ref({
  search: props.filters?.search || "",
  clinic_id: props.filters?.clinic_id || "",
  is_active: props.filters?.is_active || "",
});

const showModal = ref(false);
const deleteDialogOpen = ref(false);
const hourToDelete = ref(null);
const editingHour = ref(null);
const processing = ref(false);

const form = ref({
  clinic_id: "",
  day_of_week: 0,
  start_time: "16:00",
  end_time: "20:00",
  duration_minutes: 10,
});

// آمار
const activeCount = computed(() => {
  return props.workingHours.data?.filter((wh) => wh.is_active).length || 0;
});

const inactiveCount = computed(() => {
  return props.workingHours.data?.filter((wh) => !wh.is_active).length || 0;
});

// دریافت ساعات کاری برای یک مطب و روز خاص
const getHoursForClinicAndDay = (clinicId, dayOfWeek) => {
  return (
    props.workingHours.data
      ?.filter((wh) => wh.clinic_id === clinicId && wh.day_of_week === dayOfWeek)
      .sort((a, b) => a.start_time.localeCompare(b.start_time)) || []
  );
};

// فرمت زمان به فارسی
const formatTime = (timeStr) => {
  if (!timeStr) return "";
  return timeStr.replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[Number(d)]);
};

// باز کردن مودال افزودن
const openModal = () => {
  editingHour.value = null;

  form.value = {
    clinic_id: props.clinics[0]?.id || "",
    day_of_week: 0,
    start_time: "16:00",
    end_time: "20:00",
    duration_minutes: 10,
  };

  showModal.value = true;
};

// باز کردن مودال ویرایش
const openEditModal = (hour) => {
  editingHour.value = hour;

  form.value = {
    clinic_id: hour.clinic_id,
    day_of_week: hour.day_of_week,
    start_time: hour.start_time.substring(0, 5),
    end_time: hour.end_time.substring(0, 5),
    duration_minutes: Number(hour.duration_minutes ?? 10),
  };

  showModal.value = true;
};

// ارسال فرم
const submit = () => {
  processing.value = true;

  if (editingHour.value) {
    router.put(route("portal.working-hours.update", editingHour.value.id), form.value, {
      onFinish: () => {
        processing.value = false;
      },
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    router.post(route("portal.working-hours.store"), form.value, {
      onFinish: () => {
        processing.value = false;
      },
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
};

// تغییر وضعیت فعال/غیرفعال
const toggleStatus = (hour) => {
  router.put(route("portal.working-hours.toggle", hour.id));
};

// حذف
const deleteHour = (id) => { hourToDelete.value = id; deleteDialogOpen.value = true; };
const confirmDeleteHour = () => {
  if (!hourToDelete.value) return;
  router.delete(route("portal.working-hours.destroy", hourToDelete.value), {
    preserveScroll: true,
    onSuccess: () => { deleteDialogOpen.value = false; hourToDelete.value = null; },
  });
};

// اعمال فیلتر
const applyFilters = () => {
  router.get(route("portal.working-hours.index"), filters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

// ریست فیلتر
const resetFilters = () => {
  filters.value = { search: "", clinic_id: "", is_active: "" };
  applyFilters();
};

// تغییر صفحه
const changePage = (url) => {
  if (url) {
    router.visit(url, { preserveState: true, preserveScroll: true });
  }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* اسکرول افقی نرم */
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}

/* سلول‌های گانت */
td {
  transition: background-color 0.2s;
}

tr:hover td {
  background-color: #f9fafb;
}

tr:hover td.sticky {
  background-color: #f0fdfa;
}
</style>

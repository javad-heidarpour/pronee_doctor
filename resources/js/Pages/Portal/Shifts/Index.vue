<template>
  <PortalLayout :doctor="doctor">
    <!-- آمار -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <StatCard label="کل نوبت‌ها" :value="stats.total" icon="📋" color="blue" />
      <StatCard label="آزاد" :value="stats.available" icon="✅" color="green" />
      <StatCard label="رزرو شده" :value="stats.booked" icon="🔒" color="amber" />
      <StatCard label="لغو شده" :value="stats.cancelled" icon="❌" color="red" />
    </div>

    <!-- کارت تولید گروهی -->
    <div
      class="bg-gradient-to-l from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 mb-6 text-white"
    >
      <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-bold mb-1">🚀 تولید خودکار نوبت‌ها</h3>
          <p class="text-indigo-100 text-sm">
            بر اساس الگوهای شیفت فعال، نوبت‌های قابل رزرو را برای هفته‌های آینده ایجاد
            کنید.
          </p>
        </div>

        <div class="flex items-center gap-3 bg-white/10 p-2 rounded-xl backdrop-blur-sm">
          <div>
            <label class="text-xs text-indigo-100 block mb-1">مطب (اختیاری)</label>
            <select
              v-model="generateClinicId"
              class="px-3 py-2 rounded-lg text-gray-900 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-white"
            >
              <option value="">همه مطب‌ها</option>
              <option v-for="c in clinics" :key="c.id" :value="c.id">
                {{ c.city?.name }} - {{ c.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="text-xs text-indigo-100 block mb-1">تعداد هفته</label>
            <input
              v-model="weeksToGenerate"
              type="number"
              min="1"
              max="52"
              class="w-20 px-3 py-2 rounded-lg text-gray-900 font-bold text-center focus:outline-none focus:ring-2 focus:ring-white"
            />
          </div>

          <button
            @click="generateSlots"
            :disabled="processingGenerate"
            class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-bold hover:bg-indigo-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-md self-end"
          >
            {{ processingGenerate ? "در حال تولید..." : "ایجاد نوبت‌ها" }}
          </button>
        </div>
      </div>
    </div>

    <!-- فیلترها -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
      <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">جستجو</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="تاریخ یا ساعت"
            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
          />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">مطب</label>

          <!-- مطب با SelectInput -->
          <SelectInput
            v-model="filters.clinic_id"
            :options="
              clinics.map((c) => ({
                value: c.id,
                label: `${c.city?.name} - ${c.name}`,
              }))
            "
            placeholder="همه مطب‌ها"
            searchable
            :clearable="false"
            @keydown.enter.prevent
          />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">وضعیت</label>

          <!-- وضعیت با SelectInput -->
          <SelectInput
            v-model="filters.status"
            :options="[
              { value: '', label: 'همه' },
              { value: 'available', label: 'آزاد' },
              { value: 'booked', label: 'رزرو شده' },
              { value: 'cancelled', label: 'لغو شده' },
            ]"
            placeholder="وضعیت"
            :clearable="false"
            @keydown.enter.prevent
          />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">از تاریخ</label>

          <JalaliDatePicker
            v-model="filters.date_from"
            placeholder="انتخاب تاریخ"
            :has-error="!!$page.props.errors.date"
            :min-date="new Date().toISOString().split('T')[0]"
          />
          <p v-if="$page.props.errors.date" class="text-red-500 text-xs mt-1">
            {{ $page.props.errors.filters }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">تا تاریخ</label>
          <!-- <input
            v-model="filters.date_to"
            type="date"
            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
          /> -->
          <JalaliDatePicker
            v-model="filters.date_to"
            placeholder="انتخاب تاریخ"
            :has-error="!!$page.props.errors.date"
            :min-date="new Date().toISOString().split('T')[0]"
          />
          <p v-if="$page.props.errors.date" class="text-red-500 text-xs mt-1">
            {{ $page.props.errors.date }}
          </p>
        </div>
      </form>

      <div class="flex gap-2 mt-4">
        <button
          type="submit"
          @click="applyFilters"
          class="bg-gradient-to-l from-teal-500 to-cyan-600 text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all"
        >
          اعمال فیلتر
        </button>
        <button
          type="button"
          @click="resetFilters"
          class="bg-gray-100 text-gray-700 px-6 py-2 rounded-xl font-bold hover:bg-gray-200 transition-all"
        >
          پاک کردن
        </button>
        <button
          type="button"
          @click="openModal()"
          class="mr-auto bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition-all"
        >
          + نوبت تکی جدید
        </button>
      </div>
    </div>

    <!-- جدول نوبت‌ها -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead
            class="bg-gradient-to-l from-teal-50 to-cyan-50 border-b border-gray-200"
          >
            <tr>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">شماره</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">تاریخ</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">روز</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">ساعت</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">مطب</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">وضعیت</th>
              <th class="px-4 py-3 text-right text-xs font-bold text-gray-700">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="slot in slots.data"
              :key="slot.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ slot.id }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ toPersianDate(slot.date) }}
              </td>
              <td class="px-4 py-3 text-sm font-medium text-gray-700">
                {{ getPersianDayName(slot.date) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">
                {{ slot.start_time.substr(0, 5) }} - {{ slot.end_time.substr(0, 5) }}
              </td>
              <td class="px-4 py-3">
                <div>
                  <p class="font-bold text-gray-900 text-sm">{{ slot.clinic?.name }}</p>
                  <p class="text-xs text-gray-500">{{ slot.clinic?.city?.name }}</p>
                </div>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="getStatusClasses(slot.status)"
                  class="px-3 py-1 rounded-lg text-xs font-bold"
                >
                  {{ getStatusLabel(slot.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button
                    v-if="slot.status !== 'booked'"
                    @click="toggleStatus(slot)"
                    :title="slot.status === 'available' ? 'لغو' : 'فعال'"
                    :class="
                      slot.status === 'available'
                        ? 'text-amber-600 hover:text-amber-800'
                        : 'text-green-600 hover:text-green-800'
                    "
                    class="text-sm font-medium"
                  >
                    {{ slot.status === "available" ? "لغو" : "فعال" }}
                  </button>
                  <button
                    v-if="slot.status !== 'booked'"
                    @click="openEditModal(slot)"
                    class="text-teal-600 hover:text-teal-800 text-sm font-medium"
                  >
                    ویرایش
                  </button>
                  <button
                    v-if="slot.status !== 'booked'"
                    @click="deleteSlot(slot.id)"
                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    حذف
                  </button>
                  <span v-if="slot.status === 'booked'" class="text-xs text-gray-400">
                    🔒 رزرو شده
                  </span>
                </div>
              </td>
            </tr>
            <tr v-if="slots.data.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500">
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
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <p>هیچ نوبتی یافت نشد</p>
                <p class="text-xs mt-2">برای شروع، از بخش "تولید خودکار" استفاده کنید</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="slots.last_page > 1" class="px-6 py-4 border-t border-gray-100">
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600">
            نمایش {{ slots.from }} تا {{ slots.to }} از {{ slots.total }}
          </p>
          <div class="flex gap-2">
            <button
              v-for="(link, idx) in slots.links"
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
    </div>

    <!-- Modal ایجاد/ویرایش نوبت تکی -->
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
                {{ editingSlot ? "ویرایش نوبت" : "ایجاد نوبت جدید" }}
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

            <!-- خطای تکراری -->
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

                <SelectInput
                  v-model="form.clinic_id"
                  :options="
                    clinics.map((c) => ({
                      value: c.id,
                      label: `${c.city?.name} - ${c.name}`,
                    }))
                  "
                  placeholder="همه مطب‌ها"
                  searchable
                  :clearable="false"
                  @keydown.enter.prevent
                />
              </div>

              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">تاریخ</label>
                <!-- <input v-model="form.date" type="date" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500" /> -->
                <JalaliDatePicker
                  required
                  v-model="form.date"
                  placeholder="انتخاب تاریخ"
                  :has-error="!!$page.props.errors.date"
                  :min-date="new Date().toISOString().split('T')[0]"
                />
                <p v-if="$page.props.errors.date" class="text-red-500 text-xs mt-1">
                  {{ $page.props.errors.date }}
                </p>
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
import StatCard from "@/Components/Portal/StatCard.vue";
import JalaliDatePicker from "@/Components/Portal/JalaliDatePicker.vue";
import SelectInput from "@/Components/Portal/SelectInput.vue";

const props = defineProps({
   doctor: {
    type: Object,
    required: true,
  },
  clinics: Array,
  slots: Object,
  stats: Object,
  filters: Object,
});

const page = usePage();

// فیلترها
const filters = ref({
  search: props.filters?.search || "",
  clinic_id: props.filters?.clinic_id || "",
  status: props.filters?.status || "",
  date_from: props.filters?.date_from || "",
  date_to: props.filters?.date_to || "",
});

// تولید گروهی
const weeksToGenerate = ref(4);
const generateClinicId = ref("");
const processingGenerate = ref(false);

// Modal
const showModal = ref(false);
const editingSlot = ref(null);
const processing = ref(false);

const form = ref({
  clinic_id: "",
  date: "",
  start_time: "09:00",
  end_time: "10:00",
});

// فرمت زمان به فارسی
const formatTime = (timeStr) => {
  if (!timeStr) return "";
  return timeStr.replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[d]);
};

// تبدیل تاریخ به شمسی
const parseLocalDate = (dateStr) => {
  const [year, month, day] = String(dateStr).slice(0, 10).split("-").map(Number);
  return new Date(year, month - 1, day);
};

const todayLocalDate = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

const toPersianDate = (dateStr) => {
  if (!dateStr) return "-";
  const date = parseLocalDate(dateStr);
  return new Intl.DateTimeFormat("fa-IR", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(date);
};

// نام روز هفته به فارسی
const getPersianDayName = (dateStr) => {
  if (!dateStr) return "";
  const date = parseLocalDate(dateStr);
  return new Intl.DateTimeFormat("fa-IR", { weekday: "long" }).format(date);
};

// کلاس وضعیت
const getStatusClasses = (status) => {
  const classes = {
    available: "bg-green-100 text-green-700",
    booked: "bg-amber-100 text-amber-700",
    cancelled: "bg-red-100 text-red-700",
  };
  return classes[status] || classes.available;
};

// برچسب وضعیت
const getStatusLabel = (status) => {
  const labels = {
    available: "آزاد",
    booked: "رزرو شده",
    cancelled: "لغو شده",
  };
  return labels[status] || status;
};

// باز کردن مودال ایجاد
const openModal = () => {
  editingSlot.value = null;
  form.value = {
    clinic_id: props.clinics[0]?.id || "",
    date: todayLocalDate(),
    start_time: "09:00",
    end_time: "10:00",
  };
  showModal.value = true;
};

// باز کردن مودال ویرایش
const openEditModal = (slot) => {
  editingSlot.value = slot;
  form.value = {
    clinic_id: slot.clinic_id,
    date: slot.date,
    start_time: slot.start_time,
    end_time: slot.end_time,
  };
  showModal.value = true;
};

// ارسال فرم
const submit = () => {
  processing.value = true;

  if (editingSlot.value) {
    router.put(route("portal.shifts.update", editingSlot.value.id), form.value, {
      onFinish: () => {
        processing.value = false;
      },
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    router.post(route("portal.shifts.store"), form.value, {
      onFinish: () => {
        processing.value = false;
      },
      onSuccess: () => {
        showModal.value = false;
      },
    });
  }
};

// تغییر وضعیت
const toggleStatus = (slot) => {
  const action = slot.status === "available" ? "لغو" : "فعال";
  if (!confirm(`آیا از ${action} این نوبت اطمینان دارید؟`)) return;

  router.put(route("portal.shifts.toggle", slot.id));
};

// حذف
const deleteSlot = (id) => {
  if (confirm("آیا از حذف این نوبت اطمینان دارید؟")) {
    router.delete(route("portal.shifts.destroy", id));
  }
};

// تولید گروهی
const generateSlots = () => {
  if (
    !confirm(
      `آیا از ایجاد نوبت‌ها برای ${weeksToGenerate.value} هفته آینده اطمینان دارید؟`
    )
  ) {
    return;
  }

  processingGenerate.value = true;

  router.post(
    route("portal.shifts.generate"),
    {
      weeks_count: weeksToGenerate.value,
      clinic_id: generateClinicId.value || null,
    },
    {
      onFinish: () => {
        processingGenerate.value = false;
      },
    }
  );
};

// اعمال فیلتر
const applyFilters = () => {
  router.get(route("portal.shifts.index"), filters.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

// ریست فیلتر
const resetFilters = () => {
  filters.value = { search: "", clinic_id: "", status: "", date_from: "", date_to: "" };
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

.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}

tr:hover td {
  background-color: #f9fafb;
}
</style>

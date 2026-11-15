<template>
  <div dir="rtl">
    <!-- دکمه منوی ساندویچی -->
    <button
      type="button"
      @click="openMenu"
      :aria-expanded="isOpen"
      aria-controls="doctor-main-menu"
      aria-label="باز کردن منو"
      class="relative z-10 inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/70 bg-white/80 text-gray-800 shadow-md backdrop-blur-md transition-all hover:bg-teal-50 hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
    >
      <svg
        v-if="!isOpen"
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M4 6h16M4 12h16M4 18h16"
        />
      </svg>

      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- پنل منو و پس‌زمینه -->
    <Teleport to="body">
      <!-- پس‌زمینه تیره -->
      <Transition name="menu-fade">
        <div
          v-if="isOpen"
          class="fixed inset-0 z-[99998] bg-black/40"
          @click="closeMenu"
          aria-hidden="true"
        />
      </Transition>

      <!-- پنل منو -->
      <Transition name="menu-slide">
        <aside
          v-if="isOpen"
          id="doctor-main-menu"
          class="fixed inset-y-0 right-0 z-[99999] flex w-[290px] max-w-[85vw] flex-col overflow-y-auto overscroll-contain bg-white shadow-2xl"
          aria-label="منوی اصلی پزشک"
          dir="rtl"
        >
          <!-- سربرگ -->
          <div
            class="shrink-0 bg-gradient-to-l from-teal-700 to-cyan-600 px-5 py-6 text-white"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="mb-2 text-xs text-teal-100">سامانه نوبت‌دهی و معرفی پزشک</p>

                <h2 class="truncate text-lg font-bold">
                  {{ doctor.caption }}
                  {{ doctor.name }}
                  {{ doctor.family }}
                </h2>
              </div>

              <button
                type="button"
                @click="closeMenu"
                aria-label="بستن منو"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-2xl leading-none transition hover:bg-white/20"
              >
                ×
              </button>
            </div>
          </div>

          <!-- لینک‌های منو -->
          <nav class="flex-1 space-y-1 p-3">
            <button
              v-for="item in menuItems"
              :key="item.label"
              type="button"
              @click="navigateTo(item)"
              class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-right text-sm font-medium text-gray-700 transition-colors hover:bg-teal-50 hover:text-teal-700"
              :class="{
                'bg-teal-50 text-teal-700': activeSection === item.section,
              }"
            >
              <!-- آیکون -->
              <span
                v-if="activeSection === item.section"
                class="h-2 w-2 shrink-0 rounded-full bg-teal-600"
              ></span>
              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-teal-700"
              >
                <!-- خانه -->
                <svg
                  v-if="item.icon === 'home'"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m3 10 9-7 9 7M5 9v11h14V9M9 20v-7h6v7"
                  />
                </svg>

                <!-- نوبت -->
                <svg
                  v-else-if="item.icon === 'calendar'"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <rect x="3" y="5" width="18" height="16" rx="2" />
                  <path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18" />
                </svg>

                <!-- موقعیت -->
                <svg
                  v-else-if="item.icon === 'map'"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z
                       M9 3v15M15 6v15"
                  />
                </svg>

                <!-- گالری -->
                <svg
                  v-else-if="item.icon === 'image'"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <rect x="3" y="3" width="18" height="18" rx="2" />
                  <circle cx="8.5" cy="8.5" r="1.5" />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m21 15-5-5L5 21"
                  />
                </svg>

                <!-- نظرات -->
                <svg
                  v-else-if="item.icon === 'star'"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3
                       1.1-6.2L3 9.6l6.2-.9L12 3Z"
                  />
                </svg>

                <!-- مقاله -->
                <svg
                  v-else
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z
                       M8 8h8M8 12h8M8 16h5"
                  />
                </svg>
              </span>

              <span class="min-w-0 flex-1">
                {{ item.label }}
              </span>
            </button>
          </nav>

          <!-- پایین منو -->
          <div class="shrink-0 border-t border-gray-100 p-5">
            <p class="text-center text-xs text-gray-400">تمامی حقوق محفوظ است.</p>
          </div>
        </aside>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  activeSection: {
    type: String,
    default: "about",
  },
});

const doctor = computed(() => props.doctor);

const isOpen = ref(false);
const page = usePage();

const profileUrl = computed(() => {
  return `/${doctor.value.slug || "home"}`;
});

const menuItems = [
  {
    label: "صفحه اصلی",
    section: "about",
    icon: "home",
  },
  {
    label: "رزرو نوبت",
    section: "booking",
    icon: "calendar",
  },
  
  {
    label: "ساعات کار",
    section: "WorkingHours",
    icon: "calendar",
  },

  {
    label: "آدرس و موقعیت مطب",
    section: "location",
    icon: "map",
  },
  {
    label: "گالری تصاویر",
    section: "gallery",
    icon: "image",
  },
  {
    label: "نظرات بیماران",
    section: "reviews",
    icon: "star",
  },
  {
    label: "مقالات و مطالب آموزشی",
    section: "blog",
    icon: "article",
  },
];

function openMenu() {
  isOpen.value = true;
}

function closeMenu() {
  isOpen.value = false;
}

/*
|--------------------------------------------------------------------------
| ارسال مسیر و آبجکت doctor
|--------------------------------------------------------------------------
*/
function navigateTo(item) {
  closeMenu();
  emit("select-section", item.section);
}

function normalizePath(path) {
  const normalized = path.split("?")[0].split("#")[0];

  return normalized.length > 1 ? normalized.replace(/\/+$/, "") : normalized;
}

function handleKeydown(event) {
  if (event.key === "Escape") {
    closeMenu();
  }
}

onMounted(() => {
  window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
});
const emit = defineEmits(["select-section"]);
</script>

<style scoped>
.menu-fade-enter-active,
.menu-fade-leave-active {
  transition: opacity 0.2s ease;
}

.menu-fade-enter-from,
.menu-fade-leave-to {
  opacity: 0;
}

.menu-slide-enter-active,
.menu-slide-leave-active {
  transition: transform 0.25s ease;
}

.menu-slide-enter-from,
.menu-slide-leave-to {
  transform: translateX(100%);
}

@media (prefers-reduced-motion: reduce) {
  .menu-fade-enter-active,
  .menu-fade-leave-active,
  .menu-slide-enter-active,
  .menu-slide-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>

<!-- resources/js/Layouts/DoctorLayout.vue -->
<template>
  <div dir="rtl" class="min-h-screen bg-slate-50 text-slate-800">
    <!-- Mobile Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-200"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isMobileMenuOpen"
        class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm md:hidden"
        @click="closeMobileMenu"
      />
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 right-0 z-50 flex flex-col bg-white border-l border-slate-200 shadow-xl shadow-slate-200/50 transition-[width,transform] duration-300 ease-in-out',
        'w-[min(82vw,280px)]',
        isMobileMenuOpen ? 'translate-x-0' : 'translate-x-full',
        'md:translate-x-0',
        isSidebarCollapsed ? 'md:w-[76px]' : 'md:w-64 lg:w-72',
      ]"
    >
      <!-- Doctor Identity -->
      <div
        class="relative shrink-0 px-4 py-5 border-b border-slate-100 bg-gradient-to-b from-white to-slate-50/70"
      >
        <div
          :class="[
            'flex items-center gap-3',
            isSidebarCollapsed ? 'md:justify-center' : '',
          ]"
        >
          <!-- Doctor Photo -->
          <div class="relative shrink-0">
            <div
              :class="[
                'overflow-hidden rounded-2xl bg-gradient-to-br from-teal-100 to-cyan-100 ring-4 ring-teal-50 shadow-md transition-all duration-300',
                isSidebarCollapsed ? 'w-12 h-12' : 'w-16 h-16',
              ]"
            >
              <img
                v-if="doctorPhoto && !imageHasError"
                :key="doctorPhoto"
                :src="doctorPhoto"
                :alt="doctorFullName"
                class="w-full h-full object-cover"
                loading="eager"
                @error="handleImageError"
              />

              <div
                v-else
                class="w-full h-full flex items-center justify-center text-teal-700 font-black text-xl"
              >
                {{ doctorInitials }}
              </div>
            </div>

            <!-- Online Status -->
            <span
              class="absolute -bottom-1 -left-1 w-4 h-4 rounded-full bg-emerald-500 border-[3px] border-white"
            ></span>
          </div>

          <!-- Doctor Information -->
          <div v-if="!isSidebarCollapsed" class="flex-1 min-w-0">
            <p class="text-[10px] font-semibold text-teal-600 mb-1">پنل اختصاصی پزشک</p>

            <h2 class="text-[15px] font-black text-slate-800 truncate">
              {{ doctorFullName }}
            </h2>

            <p class="text-xs text-slate-500 mt-1 truncate">
              {{ currentDoctor?.specialty_title || "پزشک" }}
            </p>

            <div class="flex items-center gap-1.5 mt-2">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

              <span class="text-[10px] text-emerald-600 font-medium">
                حساب کاربری فعال
              </span>
            </div>
          </div>
        </div>

        <!-- Mobile Close -->
        <button
          type="button"
          @click="closeMobileMenu"
          aria-label="بستن منو"
          class="md:hidden absolute left-3 top-3 w-8 h-8 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-400"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <!-- Collapse Button -->
        <button
          type="button"
          @click="toggleCollapse"
          :aria-label="isSidebarCollapsed ? 'باز کردن منو' : 'جمع کردن منو'"
          class="hidden md:flex absolute -left-3 top-1/2 -translate-y-1/2 w-7 h-7 bg-white border border-slate-200 rounded-full items-center justify-center shadow-md text-slate-500 hover:text-teal-600 hover:border-teal-300 transition z-10"
        >
          <svg
            class="w-3.5 h-3.5 transition-transform duration-300 rotate-180"
            :class="isSidebarCollapsed ? 'rotate-0' : ''"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M15 18l-6-6 6-6"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav
        class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-3 py-4 overscroll-contain"
      >
        <div
          v-if="!isSidebarCollapsed"
          class="px-3 mb-3 text-[10px] font-bold text-slate-400 tracking-wider"
        >
          منوی مدیریت
        </div>

        <div class="space-y-1">
          <Link
            v-for="menu in menuItems"
            :key="menu.id"
            :href="getMenuUrl(menu)"
            @click="closeMobileMenu"
            :class="[
              'relative flex items-center gap-3 rounded-xl transition-all duration-200 group',
              isSidebarCollapsed
                ? 'justify-center px-2 py-3 mx-auto md:w-12'
                : 'px-3 py-3 w-full',
              isActive(menu.route)
                ? 'bg-teal-50 text-teal-700 shadow-sm'
                : 'text-slate-600 hover:bg-slate-50 hover:text-teal-700',
            ]"
          >
            <!-- Active Indicator -->
            <span
              v-if="isActive(menu.route)"
              class="absolute right-0 top-2 bottom-2 w-1 rounded-l-full bg-teal-500"
            ></span>

            <!-- Icon -->
            <span
              class="w-6 h-6 shrink-0 flex items-center justify-center"
              :class="
                isActive(menu.route)
                  ? 'text-teal-600'
                  : 'text-slate-400 group-hover:text-teal-600'
              "
            >
              <svg
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  :d="getIconPath(menu.icon)"
                />
              </svg>
            </span>

            <!-- Title -->
            <span
              v-if="!isSidebarCollapsed"
              class="flex-1 text-[13px] font-medium truncate"
            >
              {{ menu.title }}
            </span>

            <!-- Pending Badge -->
            <span
              v-if="menu.route === 'portal.patients.index' && pendingAppointments > 0"
              :class="[
                'flex items-center justify-center bg-rose-500 text-white font-bold rounded-full shrink-0',
                isSidebarCollapsed
                  ? 'absolute -top-1 -left-1 w-5 h-5 text-[9px]'
                  : 'min-w-5 h-5 px-1.5 text-[10px]',
              ]"
            >
              {{ pendingAppointments }}
            </span>

            <!-- Tooltip -->
            <span
              v-if="isSidebarCollapsed"
              class="hidden md:block absolute right-full mr-3 top-1/2 -translate-y-1/2 px-3 py-2 bg-slate-900 text-white text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all whitespace-nowrap shadow-xl z-[60] pointer-events-none"
            >
              {{ menu.title }}
            </span>
          </Link>

          <Link
            v-if="currentUser.role === 'admin'"
            :href="route('portal.security.audit-logs')"
            @click="closeMobileMenu"
            :class="[
              'relative flex items-center gap-3 rounded-xl transition-all duration-200 group',
              isSidebarCollapsed
                ? 'justify-center px-2 py-3 mx-auto md:w-12'
                : 'px-3 py-3 w-full',
              isActive('portal.security.audit-logs')
                ? 'bg-indigo-50 text-indigo-700 shadow-sm'
                : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-700',
            ]"
          >
            <span class="w-6 h-6 shrink-0 flex items-center justify-center text-lg">🛡️</span>
            <span v-if="!isSidebarCollapsed" class="flex-1 text-[13px] font-medium truncate">
              گزارش ممیزی امنیتی
            </span>
            <span
              v-if="isSidebarCollapsed"
              class="hidden md:block absolute right-full mr-3 top-1/2 -translate-y-1/2 px-3 py-2 bg-slate-900 text-white text-xs rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all whitespace-nowrap shadow-xl z-[60] pointer-events-none"
            >
              گزارش ممیزی امنیتی
            </span>
          </Link>
        </div>
      </nav>

      <!-- Sidebar Bottom -->
      <div class="shrink-0 border-t border-slate-100 p-3 bg-white">
        <!-- Logged-in User -->
        <div
          v-if="!isSidebarCollapsed"
          class="flex items-center gap-3 px-3 py-3 mb-2 rounded-xl bg-slate-50 border border-slate-100"
        >
          <div
            class="w-10 h-10 shrink-0 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-black"
          >
            {{ userFullName.charAt(0) }}
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-slate-800 truncate">
              {{ userFullName }}
            </p>

            <p class="text-[11px] text-slate-500 mt-1">
              {{ userRoleTitle }}
            </p>
          </div>
        </div>
        <!-- Logout -->
        <form @submit.prevent="logout">
          <button
            type="submit"
            :disabled="isLoggingOut"
            :class="[
              'w-full flex items-center gap-3 rounded-xl text-rose-600 hover:bg-rose-50 transition-all disabled:opacity-50',
              isSidebarCollapsed ? 'justify-center py-3' : 'px-3 py-3',
            ]"
          >
            <svg
              class="w-5 h-5 shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>

            <span v-if="!isSidebarCollapsed" class="text-sm font-semibold">
              {{ isLoggingOut ? "در حال خروج..." : "خروج از حساب کاربری" }}
            </span>
          </button>
        </form>

        <p v-if="!isSidebarCollapsed" class="text-center text-[9px] text-slate-300 mt-3">
          پنل اختصاصی پزشک
        </p>
      </div>
    </aside>

    <!-- Main Content -->
    <main
      :class="[
        'min-h-screen min-w-0 transition-[margin] duration-300',
        isSidebarCollapsed ? 'md:mr-[76px]' : 'md:mr-64 lg:mr-72',
      ]"
    >
      <!-- Header -->
      <header
        class="sticky top-0 z-30 bg-white/90 backdrop-blur-xl border-b border-slate-200/80"
      >
        <div
          class="min-h-[72px] px-3 sm:px-5 lg:px-8 flex items-center justify-between gap-3"
        >
          <!-- Right: Mobile Menu + Page Title -->
          <div class="flex items-center gap-3 min-w-0">
            <button
              type="button"
              @click="openMobileMenu"
              aria-label="باز کردن منو"
              class="md:hidden w-10 h-10 shrink-0 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center hover:bg-teal-100 transition"
            >
              <svg
                class="w-5 h-5"
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
            </button>

            <div class="min-w-0">
              <h1 class="font-black text-base sm:text-lg text-slate-800 truncate">
                {{ pageTitle }}
              </h1>

              <p class="hidden sm:block text-[11px] text-slate-400 mt-1">
                مدیریت خدمات و اطلاعات مطب
              </p>
            </div>
          </div>

          <!-- Left: Status + Flash -->
          <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <!-- Success Message -->
            <Transition
              enter-active-class="transition-all duration-300"
              enter-from-class="opacity-0 translate-y-2"
              leave-active-class="transition-all duration-200"
              leave-to-class="opacity-0 translate-y-2"
            >
              <div
                v-if="flashSuccess"
                class="flex items-center gap-2 px-2 sm:px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-100"
              >
                <svg
                  class="w-4 h-4 text-emerald-600 shrink-0"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>

                <span
                  class="text-xs text-emerald-700 font-medium hidden sm:block max-w-48 truncate"
                >
                  {{ flashSuccess }}
                </span>
              </div>
            </Transition>

            <!-- System Status -->
            <div
              class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 border border-slate-100"
            >
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

              <span class="text-[11px] font-medium text-slate-500"> سیستم فعال </span>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="w-full min-w-0 p-3 sm:p-5 lg:p-8">
        <div class="w-full min-w-0 max-w-[1800px] mx-auto">
          <slot />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from "vue";

import { Link, router, usePage } from "@inertiajs/vue3";

// --------------------------------------------------
// Props
// --------------------------------------------------

const props = defineProps({
  menus: {
    type: Array,
    default: () => [],
  },
  doctor: {
    type: Object,
    default: null,
  },

  title: {
    type: String,
    default: "",
  },

  stats: {
    type: Object,
    default: () => ({}),
  },
});

// --------------------------------------------------
// Inertia
// --------------------------------------------------

const page = usePage();
// --------------------------------------------------
// Authenticated User
// --------------------------------------------------
const currentUser = computed(() => {
  return page.props.auth?.user || {};
});

const userFullName = computed(() => {
  return (
    [currentUser.value.name, currentUser.value.family].filter(Boolean).join(" ") ||
    "کاربر سامانه"
  );
});

const userRoleTitle = computed(() => {
  const roles = {
    admin: "مدیر سامانه",
    doctor: "پزشک",
    secretary: "منشی",
    user: "کاربر",
  };

  return roles[currentUser.value.role] || "کاربر";
});
// --------------------------------------------------
// State
// --------------------------------------------------

const isMobileMenuOpen = ref(false);
const isCollapsed = ref(false);
const imageHasError = ref(false);
const isLoggingOut = ref(false);

const windowWidth = ref(typeof window !== "undefined" ? window.innerWidth : 1024);

// فقط در دسکتاپ وضعیت جمع‌شدن Sidebar اعمال می‌شود.
const isSidebarCollapsed = computed(() => {
  return isCollapsed.value && windowWidth.value >= 768;
});

// --------------------------------------------------
// Doctor Information
// اولویت با اطلاعات ارسالی صفحه و سپس Inertia
// --------------------------------------------------

const currentDoctor = computed(() => {
  const sharedDoctor = page.props.auth?.doctor || {};
  const pageDoctor = props.doctor || {};

  return {
    ...sharedDoctor,
    ...pageDoctor,

    photo: pageDoctor.photo || sharedDoctor.photo || null,
    name: pageDoctor.name || sharedDoctor.name || null,
    family: pageDoctor.family || sharedDoctor.family || null,
    caption: pageDoctor.caption || sharedDoctor.caption || null,
  };
});

// Doctor Photo
const doctorPhoto = computed(() => {
  return currentDoctor.value?.photo || null;
});

// Doctor Full Name
const doctorFullName = computed(() => {
  const doctor = currentDoctor.value;

  return [doctor.caption, doctor.name, doctor.family].filter(Boolean).join(" ") || "پزشک";
});

// Doctor Initials
const doctorInitials = computed(() => {
  const doctor = currentDoctor.value;

  const first = doctor?.name?.charAt(0) || "";
  const last = doctor?.family?.charAt(0) || "";

  return first + last || "پ";
});

// Reset image error when photo changes
watch(doctorPhoto, () => {
  imageHasError.value = false;
});

// --------------------------------------------------
// Menu & Page Information
// --------------------------------------------------

const menuItems = computed(() => {
  return page.props.menus || [];
});

const flashSuccess = computed(() => {
  return page.props.flash?.success || "";
});

const pendingAppointments = computed(() => {
  return Number(
    props.stats?.pending_appointments ?? page.props.stats?.pending_appointments ?? 0
  );
});

const pageTitle = computed(() => {
  const currentMenu = menuItems.value.find((item) => {
    return isActive(item.route);
  });

  return currentMenu?.title || props.title || "پنل مدیریت";
});

// --------------------------------------------------
// Responsive Sidebar
// --------------------------------------------------

const handleResize = () => {
  windowWidth.value = window.innerWidth;

  if (window.innerWidth >= 768) {
    isMobileMenuOpen.value = false;
  }
};

const openMobileMenu = () => {
  isMobileMenuOpen.value = true;
};

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false;
};

// --------------------------------------------------
// Lifecycle
// --------------------------------------------------

onMounted(() => {
  window.addEventListener("resize", handleResize, {
    passive: true,
  });

  try {
    const savedState = localStorage.getItem("sidebarCollapsed");

    if (savedState !== null) {
      isCollapsed.value = JSON.parse(savedState) === true;
    }
  } catch (error) {
    isCollapsed.value = false;
  }
});

onUnmounted(() => {
  window.removeEventListener("resize", handleResize);
});

// --------------------------------------------------
// Collapse Sidebar
// --------------------------------------------------

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;

  try {
    localStorage.setItem("sidebarCollapsed", JSON.stringify(isCollapsed.value));
  } catch (error) {
    console.warn("ذخیره وضعیت منو امکان‌پذیر نیست", error);
  }
};

// --------------------------------------------------
// Navigation Icons
// --------------------------------------------------

const iconPaths = {
  IconDashboard:
    "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",

  IconUser: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z",

  IconBuilding:
    "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",

  IconClock: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",

  IconPhoto:
    "M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z",

  IconChat:
    "M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z",

  IconUsers:
    "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",

  IconCalendar:
    "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
};

const getIconPath = (iconName) => {
  return iconPaths[iconName] || iconPaths.IconDashboard;
};

// --------------------------------------------------
// Route Handling
// --------------------------------------------------

const getMenuUrl = (menu) => {
  if (menu.route) {
    try {
      return route(menu.route);
    } catch (error) {
      console.error(`Route ${menu.route} not found`, error);

      return "#";
    }
  }

  return menu.url || "#";
};

const isActive = (routeName) => {
  if (!routeName) {
    return false;
  }

  try {
    const currentPath =
      route(routeName).replace(window.location.origin, "").replace(/\/+$/, "") || "/";

    const currentUrl = page.url.split("?")[0].replace(/\/+$/, "") || "/";

    return currentUrl === currentPath || currentUrl.startsWith(currentPath + "/");
  } catch (error) {
    return false;
  }
};

// --------------------------------------------------
// Image Handling
// --------------------------------------------------

const handleImageError = () => {
  imageHasError.value = true;

  console.warn("بارگذاری تصویر پزشک ناموفق بود:", doctorPhoto.value);
};

// --------------------------------------------------
// Logout
// --------------------------------------------------

const logout = () => {
  if (isLoggingOut.value) {
    return;
  }

  isLoggingOut.value = true;

  router.post(
    route("portal.logout"),
    {},
    {
      onFinish: () => {
        isLoggingOut.value = false;
      },
    }
  );
};
</script>

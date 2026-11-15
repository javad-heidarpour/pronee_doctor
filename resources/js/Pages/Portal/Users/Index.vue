<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  users: {
    type: [Array, Object],
    default: () => [],
  },
  doctor: {
    type: Object,
  },
  roles: {
    type: Object,
  },
});

const page = usePage();

const search = ref("");
const selectedRole = ref("all");
const selectedStatus = ref("all");
const sortBy = ref("newest");

const userList = computed(() => {
  if (Array.isArray(props.users)) {
    return props.users;
  }

  return props.users?.data ?? [];
});

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const roles = {
  doctor: { label: "پزشک", icon: "⚕️" },
  secretary: { label: "منشی", icon: "🗂️" },
  admin: { label: "مدیر سیستم", icon: "🛡️" },
  user: { label: "کاربر عادی", icon: "👤" },
};

const roleInfo = (role) =>
  roles[role] || {
    label: role || "نامشخص",
    icon: "👤",
  };

const fullName = (user) =>
  [user.name, user.family].filter(Boolean).join(" ") || "بدون نام";

const initials = (user) => {
  const first = user.name?.charAt(0) || "";
  const last = user.family?.charAt(0) || "";

  return first + last || "؟";
};

const formatDate = (date) => {
  if (!date) return "—";

  const parsed = new Date(date);

  if (Number.isNaN(parsed.getTime())) return "—";

  return new Intl.DateTimeFormat("fa-IR", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(parsed);
};

// Statistics
const totalUsers = computed(() => userList.value.length);

const deletedUsers = computed(
  () => userList.value.filter((user) => Boolean(user.deleted_at)).length
);

const activeUsers = computed(
  () =>
    userList.value.filter((user) => !user.deleted_at && Boolean(user.is_active)).length
);

const inactiveUsers = computed(
  () => userList.value.filter((user) => !user.deleted_at && !user.is_active).length
);

const doctorCount = computed(
  () => userList.value.filter((user) => !user.deleted_at && user.role === "doctor").length
);

// Search and filters
const filteredUsers = computed(() => {
  let result = [...userList.value];

  const term = search.value.trim().toLowerCase();

  if (term) {
    result = result.filter((user) => {
      const searchable = [
        user.name,
        user.family,
        user.phone,
        user.mobile,
        user.email,
        user.username,
        user.id,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();

      return searchable.includes(term);
    });
  }

  if (selectedRole.value !== "all") {
    result = result.filter((user) => user.role === selectedRole.value);
  }

  if (selectedStatus.value === "deleted") {
    result = result.filter((user) => Boolean(user.deleted_at));
  } else if (selectedStatus.value === "active") {
    result = result.filter((user) => !user.deleted_at && Boolean(user.is_active));
  } else if (selectedStatus.value === "inactive") {
    result = result.filter((user) => !user.deleted_at && !user.is_active);
  } else if (selectedStatus.value === "normal") {
    result = result.filter((user) => !user.deleted_at);
  }

  result.sort((a, b) => {
    if (sortBy.value === "newest") {
      return new Date(b.created_at || 0) - new Date(a.created_at || 0);
    }

    if (sortBy.value === "oldest") {
      return new Date(a.created_at || 0) - new Date(b.created_at || 0);
    }

    if (sortBy.value === "deleted") {
      return new Date(b.deleted_at || 0) - new Date(a.deleted_at || 0);
    }

    return String(a.name || "").localeCompare(String(b.name || ""), "fa");
  });

  return result;
});

// Change active status
const changeStatus = (user) => {
  if (user.deleted_at) return;

  router.patch(
    route("portal.users.status", user.id),
    {
      is_active: !user.is_active,
    },
    {
      preserveScroll: true,
    }
  );
};

// Delete / Restore
const confirmDelete = (user) => {
  const isDeleted = Boolean(user.deleted_at);

  const message = isDeleted
    ? `آیا می‌خواهید کاربر «${fullName(user)}» بازیابی شود؟`
    : `آیا از حذف کاربر «${fullName(user)}» مطمئن هستید؟`;

  if (!window.confirm(message)) return;

  router.delete(route("portal.users.destroy", user.id), {
    preserveScroll: true,
  });
};

// Reset filters
const resetFilters = () => {
  search.value = "";
  selectedRole.value = "all";
  selectedStatus.value = "all";
  sortBy.value = "newest";
};
</script>

<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="w-full text-slate-800 sm:p-5">
      <div class="mx-auto w-full max-w-[1800px]">
        <!-- Header -->
        <header
          class="mb-7 flex flex-col justify-between gap-5 md:flex-row md:items-center"
        >
          <Link
            :href="route('portal.users.create')"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:-translate-y-0.5 hover:bg-indigo-700"
          >
            <span class="text-xl">+</span>
            افزودن کاربر جدید
          </Link>
        </header>

        <!-- Flash Messages -->
        <div
          v-if="flashSuccess"
          class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700"
        >
          <span
            class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100"
          >
            ✓
          </span>
          {{ flashSuccess }}
        </div>

        <div
          v-if="flashError"
          class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700"
        >
          <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100">
            !
          </span>
          {{ flashError }}
        </div>

        <!-- Statistics -->
        <section
          class="mb-7 grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-5 lg:gap-5"
        >
          <!-- Total -->
          <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-400 sm:text-sm">
                کل کاربران
              </span>
              <span class="rounded-xl bg-indigo-50 p-2.5 text-xl">👥</span>
            </div>
            <div class="mt-4 text-2xl font-black text-slate-900">
              {{ totalUsers }}
            </div>
            <div class="mt-1 text-xs text-slate-400">تمام حساب‌ها</div>
          </div>

          <!-- Active -->
          <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-400 sm:text-sm">
                کاربران فعال
              </span>
              <span class="rounded-xl bg-emerald-50 p-2.5 text-xl">✓</span>
            </div>
            <div class="mt-4 text-2xl font-black text-emerald-600">
              {{ activeUsers }}
            </div>
            <div class="mt-1 text-xs text-slate-400">حساب فعال</div>
          </div>

          <!-- Inactive -->
          <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-400 sm:text-sm">
                غیرفعال
              </span>
              <span class="rounded-xl bg-amber-50 p-2.5 text-xl">⊘</span>
            </div>
            <div class="mt-4 text-2xl font-black text-amber-600">
              {{ inactiveUsers }}
            </div>
            <div class="mt-1 text-xs text-slate-400">حساب غیرفعال</div>
          </div>

          <!-- Deleted -->
          <div class="rounded-3xl border border-rose-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-400 sm:text-sm">
                حذف‌شده
              </span>
              <span class="rounded-xl bg-rose-50 p-2.5 text-xl">🗑️</span>
            </div>
            <div class="mt-4 text-2xl font-black text-rose-600">
              {{ deletedUsers }}
            </div>
            <div class="mt-1 text-xs text-slate-400">حذف نرم‌شده</div>
          </div>

          <!-- Doctors -->
          <div
            class="col-span-2 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm sm:col-span-1 sm:p-5"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-400 sm:text-sm">
                پزشکان
              </span>
              <span class="rounded-xl bg-violet-50 p-2.5 text-xl">⚕️</span>
            </div>
            <div class="mt-4 text-2xl font-black text-violet-600">
              {{ doctorCount }}
            </div>
            <div class="mt-1 text-xs text-slate-400">حساب با نقش پزشک</div>
          </div>
        </section>

        <!-- Filters -->
        <section
          class="mb-6 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5"
        >
          <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="font-black text-slate-800">جست‌وجو و فیلتر کاربران</h2>
              <p class="mt-1 text-xs text-slate-400">
                جست‌وجو در اطلاعات حساب‌های کاربری
              </p>
            </div>

            <span
              class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600"
            >
              {{ filteredUsers.length }} نتیجه
            </span>
          </div>

          <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-4">
            <!-- Search -->
            <div class="relative xl:col-span-2">
              <span
                class="absolute right-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"
              >
                ⌕
              </span>

              <input
                v-model="search"
                type="search"
                placeholder="نام، نام خانوادگی، موبایل، ایمیل یا شناسه..."
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pr-11 pl-4 text-sm outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-50"
              />
            </div>

            <!-- Role -->
            <select
              v-model="selectedRole"
              class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-indigo-400"
            >
              <option value="all">همه نقش‌ها</option>
              <option value="doctor">پزشک</option>
              <option value="secretary">منشی</option>
              <option value="admin">مدیر سیستم</option>
              <option value="user">کاربر عادی</option>
            </select>

            <!-- Status -->
            <select
              v-model="selectedStatus"
              class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-indigo-400"
            >
              <option value="all">همه وضعیت‌ها</option>
              <option value="normal">کاربران حذف‌نشده</option>
              <option value="active">فعال</option>
              <option value="inactive">غیرفعال</option>
              <option value="deleted">حذف‌شده‌ها</option>
            </select>
            <select
              v-model="sortBy"
              class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none focus:border-indigo-400"
            >
              <option value="newest">جدیدترین ثبت‌نام</option>
              <option value="oldest">قدیمی‌ترین ثبت‌نام</option>
              <option value="name">مرتب‌سازی بر اساس نام</option>
              <option value="deleted">جدیدترین حذف</option>
            </select>
          </div>

          <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <button
              @click="resetFilters"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-800"
            >
              پاک کردن فیلترها
            </button>
          </div>
        </section>

        <!-- Desktop Table -->
        <section
          class="hidden overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm lg:block"
        >
          <div
            class="flex items-center justify-between border-b border-slate-100 px-6 py-5"
          >
            <div>
              <h2 class="font-black text-slate-800">فهرست کاربران</h2>
              <p class="mt-1 text-xs text-slate-400">
                اطلاعات حساب‌ها و وضعیت حذف کاربران
              </p>
            </div>

            <span
              class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600"
            >
              {{ filteredUsers.length }} کاربر
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-right">
              <thead class="bg-slate-50 text-xs text-slate-500">
                <tr>
                  <th class="px-5 py-4 font-bold">کاربر</th>
                  <th class="px-4 py-4 font-bold">اطلاعات تماس</th>
                  <th class="px-4 py-4 font-bold">نام کاربری</th>
                  <th class="px-4 py-4 font-bold">نقش</th>
                  <th class="px-4 py-4 font-bold">تاریخ عضویت</th>
                  <th class="px-4 py-4 font-bold">وضعیت</th>
                  <th class="px-4 py-4 font-bold">تاریخ حذف</th>
                  <th class="px-5 py-4 text-center font-bold">عملیات</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="user in filteredUsers"
                  :key="user.id"
                  :class="user.deleted_at ? 'bg-rose-50/40' : 'hover:bg-slate-50/80'"
                  class="transition"
                >
                  <!-- User -->
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                      <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-black text-white"
                      >
                        {{ initials(user) }}
                      </div>

                      <div>
                        <div class="font-bold text-slate-800">
                          {{ fullName(user) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-400">
                          شناسه: {{ user.id }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Contact -->
                  <td class="px-4 py-4">
                    <div class="text-sm text-slate-700">
                      {{ user.phone || user.mobile || "—" }}
                    </div>
                    <div class="mt-1 text-xs text-slate-400">
                      {{ user.email || "بدون ایمیل" }}
                    </div>
                  </td>

                  <!-- Username -->
                  <td class="px-4 py-4 text-sm text-slate-600">
                    {{ user.username || "—" }}
                  </td>

                  <!-- Role -->
                  <td class="px-4 py-4">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700"
                    >
                      {{ roleInfo(user.role).icon }}
                      {{ roleInfo(user.role).label }}
                    </span>
                  </td>

                  <!-- Created -->
                  <td class="px-4 py-4 text-xs text-slate-500">
                    {{ formatDate(user.created_at) }}
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-4">
                    <span
                      v-if="user.deleted_at"
                      class="inline-flex rounded-full bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700"
                    >
                      حذف‌شده
                    </span>

                    <span
                      v-else
                      :class="
                        user.is_active
                          ? 'bg-emerald-50 text-emerald-700'
                          : 'bg-amber-50 text-amber-700'
                      "
                      class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-bold"
                    >
                      <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="user.is_active ? 'bg-emerald-500' : 'bg-amber-500'"
                      ></span>
                      {{ user.is_active ? "فعال" : "غیرفعال" }}
                    </span>
                  </td>

                  <!-- Deleted date -->
                  <td class="px-4 py-4 text-xs">
                    <span v-if="user.deleted_at" class="font-semibold text-rose-600">
                      {{ formatDate(user.deleted_at) }}
                    </span>
                    <span v-else class="text-slate-300">—</span>
                  </td>

                  <!-- Actions -->
                  <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-2">
                      <template v-if="!user.deleted_at">
                        <Link
                          :href="route('portal.users.edit', user.id)"
                          class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-600 hover:bg-indigo-100"
                        >
                          ویرایش
                        </Link>
                        <Link
                          v-if="user.role === 'secretary' && !user.deleted_at"
                          :href="route('portal.users.assignments', user.id)"
                          class="rounded-xl bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100"
                        >
                          پزشکان و دسترسی‌ها
                        </Link>
                        <button
                          @click="changeStatus(user)"
                          class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-100"
                        >
                          {{ user.is_active ? "تعلیق" : "فعال‌سازی" }}
                        </button>
                      </template>

                      <button
                        type="button"
                        @click="confirmDelete(user)"
                        :class="
                          user.deleted_at
                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                            : 'bg-rose-50 text-rose-600 hover:bg-rose-100'
                        "
                        class="rounded-xl px-3 py-2 text-xs font-bold transition"
                      >
                        {{ user.deleted_at ? "بازیابی" : "حذف" }}
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="filteredUsers.length === 0">
                  <td colspan="8" class="px-6 py-16 text-center">
                    <div class="text-4xl">🔎</div>
                    <div class="mt-3 font-bold text-slate-700">کاربری پیدا نشد</div>
                    <p class="mt-1 text-sm text-slate-400">
                      عبارت جست‌وجو یا فیلترها را تغییر دهید.
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Mobile / Tablet Cards -->
        <section class="space-y-3 lg:hidden">
          <article
            v-for="user in filteredUsers"
            :key="user.id"
            :class="
              user.deleted_at
                ? 'border-rose-200 bg-rose-50/40'
                : 'border-slate-100 bg-white'
            "
            class="rounded-3xl border p-4 shadow-sm"
          >
            <!-- Card Header -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 items-center gap-3">
                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 font-black text-white"
                >
                  {{ initials(user) }}
                </div>

                <div class="min-w-0">
                  <h3 class="truncate font-black text-slate-800">
                    {{ fullName(user) }}
                  </h3>
                  <p class="mt-1 text-xs text-slate-400">شناسه کاربر: {{ user.id }}</p>
                </div>
              </div>

              <span
                v-if="user.deleted_at"
                class="shrink-0 rounded-full bg-rose-100 px-2.5 py-1.5 text-[10px] font-bold text-rose-700"
              >
                حذف‌شده
              </span>

              <span
                v-else
                :class="
                  user.is_active
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-amber-50 text-amber-700'
                "
                class="shrink-0 rounded-full px-2.5 py-1.5 text-[10px] font-bold"
              >
                {{ user.is_active ? "فعال" : "غیرفعال" }}
              </span>
            </div>

            <!-- Card Details -->
            <div class="my-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3">
              <div>
                <div class="text-[10px] text-slate-400">شماره تماس</div>
                <div
                  dir="ltr"
                  class="mt-1 text-right text-xs font-semibold text-slate-700"
                >
                  {{ user.phone || user.mobile || "—" }}
                </div>
              </div>

              <div>
                <div class="text-[10px] text-slate-400">نقش کاربر</div>
                <div class="mt-1 text-xs font-bold text-slate-700">
                  {{ roleInfo(user.role).icon }}
                  {{ roleInfo(user.role).label }}
                </div>
              </div>

              <div class="col-span-2 min-w-0">
                <div class="text-[10px] text-slate-400">ایمیل</div>
                <div dir="ltr" class="mt-1 truncate text-right text-xs text-slate-700">
                  {{ user.email || "—" }}
                </div>
              </div>

              <div class="col-span-2">
                <div class="text-[10px] text-slate-400">نام کاربری</div>
                <div class="mt-1 text-xs text-slate-700">
                  {{ user.username || "—" }}
                </div>
              </div>

              <div>
                <div class="text-[10px] text-slate-400">تاریخ عضویت</div>
                <div class="mt-1 text-xs text-slate-700">
                  {{ formatDate(user.created_at) }}
                </div>
              </div>

              <div>
                <div class="text-[10px] text-slate-400">تاریخ حذف</div>
                <div
                  :class="user.deleted_at ? 'text-rose-600' : 'text-slate-300'"
                  class="mt-1 text-xs font-semibold"
                >
                  {{ formatDate(user.deleted_at) }}
                </div>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="flex flex-wrap items-center justify-end gap-2">
              <template v-if="!user.deleted_at">
                <Link
                  :href="route('portal.users.edit', user.id)"
                  class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-600"
                >
                  ویرایش
                </Link>
                <Link
                  v-if="user.role === 'secretary' && !user.deleted_at"
                  :href="route('portal.users.assignments', user.id)"
                  class="rounded-xl bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100"
                >
                  پزشکان و دسترسی‌ها
                </Link>
                <button
                  type="button"
                  @click="changeStatus(user)"
                  class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700"
                >
                  {{ user.is_active ? "تعلیق" : "فعال‌سازی" }}
                </button>
              </template>

              <button
                type="button"
                @click="confirmDelete(user)"
                :class="
                  user.deleted_at
                    ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    : 'bg-rose-50 text-rose-600 hover:bg-rose-100'
                "
                class="rounded-xl px-3 py-2 text-xs font-bold transition"
              >
                {{ user.deleted_at ? "بازیابی" : "حذف" }}
              </button>
            </div>
          </article>

          <!-- Empty State -->
          <div
            v-if="filteredUsers.length === 0"
            class="rounded-3xl bg-white px-5 py-14 text-center shadow-sm"
          >
            <div class="text-4xl">🔎</div>
            <div class="mt-3 font-bold">کاربری پیدا نشد</div>
            <p class="mt-1 text-sm text-slate-400">فیلترها را تغییر دهید.</p>
          </div>
        </section>

        <!-- Footer -->
        <footer
          class="mt-6 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400"
        >
          <span> نمایش {{ filteredUsers.length }} کاربر از {{ totalUsers }} کاربر </span>
          <span> کاربران حذف‌شده: {{ deletedUsers }} </span>
        </footer>
      </div>
    </div>
  </PortalLayout>
</template>

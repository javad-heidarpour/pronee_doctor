<script setup>
import { computed, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  logs: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
  events: { type: Array, default: () => [] },
});

const rows = computed(() => props.logs?.data ?? []);
const form = reactive({
  event: props.filters?.event ?? "",
  actor_id: props.filters?.actor_id ?? "",
  from: props.filters?.from ?? "",
  to: props.filters?.to ?? "",
});

const eventLabels = {
  "managed_user.created": "ایجاد حساب کاربری",
  "managed_user.updated": "ویرایش حساب کاربری",
  "managed_user.status_changed": "تغییر وضعیت حساب",
  "managed_user.deleted": "حذف حساب کاربری",
  "managed_user.restored": "بازیابی حساب کاربری",
  "secretary.assignments_updated": "تغییر دسترسی منشی",
  "auth.login_succeeded": "ورود موفق به پنل",
  "auth.login_failed": "تلاش ناموفق برای ورود",
  "auth.logout": "خروج از پنل",
  "auth.login_rejected": "ورود ردشده به پنل",
  "security.session_revoked": "خاتمه نشست دیگر",
};

const applyFilters = () => {
  router.get(route("portal.security.audit-logs"), form, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const clearFilters = () => {
  Object.assign(form, { event: "", actor_id: "", from: "", to: "" });
  applyFilters();
};

const formatDate = (value) => {
  if (!value) return "—";
  const date = new Date(value);
  return new Intl.DateTimeFormat("fa-IR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
};

const roleLabel = (role) => ({
  admin: "مدیر",
  doctor: "پزشک",
  secretary: "منشی",
}[role] || role || "—");

const metadataLabel = (metadata = {}) => {
  const parts = [];
  if (metadata.role) parts.push("نقش: " + roleLabel(metadata.role));
  if (typeof metadata.is_active === "boolean") parts.push(metadata.is_active ? "حساب فعال" : "حساب غیرفعال");
  if (metadata.doctor_id) parts.push("شناسه پزشک: " + metadata.doctor_id);
  if (typeof metadata.clinic_count === "number") parts.push("تعداد مطب: " + metadata.clinic_count);
  if (typeof metadata.permission_count === "number") parts.push("تعداد مجوز: " + metadata.permission_count);
  if (typeof metadata.restored === "boolean") parts.push(metadata.restored ? "بازیابی" : "حذف");
  return parts.join(" · ") || "—";
};
</script>

<template>
  <Head title="گزارش ممیزی امنیتی" />
  <PortalLayout title="گزارش ممیزی امنیتی">
    <main dir="rtl" class="mx-auto w-full max-w-7xl p-4 text-slate-800 sm:p-6">
      <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-xl font-black text-slate-900 sm:text-2xl">گزارش ممیزی امنیتی</h1>
          <p class="mt-2 text-sm text-slate-500">ردیابی تغییرات حساس حساب‌ها و دسترسی‌ها</p>
        </div>
        <span class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">
          {{ logs.total ?? 0 }} رویداد ثبت‌شده
        </span>
      </header>

      <form @submit.prevent="applyFilters" class="mb-5 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
        <label class="text-xs font-bold text-slate-600">
          نوع رویداد
          <select v-model="form.event" class="mt-2 w-full rounded-xl border-slate-200 text-sm">
            <option value="">همه رویدادها</option>
            <option v-for="event in events" :key="event" :value="event">{{ eventLabels[event] || event }}</option>
          </select>
        </label>
        <label class="text-xs font-bold text-slate-600">
          شناسه کاربر
          <input v-model="form.actor_id" type="number" min="1" inputmode="numeric" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="شناسه انجام‌دهنده" />
        </label>
        <label class="text-xs font-bold text-slate-600">
          از تاریخ
          <input v-model="form.from" type="date" class="mt-2 w-full rounded-xl border-slate-200 text-sm" />
        </label>
        <label class="text-xs font-bold text-slate-600">
          تا تاریخ
          <input v-model="form.to" type="date" class="mt-2 w-full rounded-xl border-slate-200 text-sm" />
        </label>
        <div class="flex items-end gap-2">
          <button type="submit" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">اعمال فیلتر</button>
          <button type="button" @click="clearFilters" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200">پاک‌کردن</button>
        </div>
        <p v-if="form.from && form.to && form.to < form.from" class="text-xs text-rose-600 sm:col-span-2 lg:col-span-5">تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.</p>
      </form>

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="!rows.length" class="p-10 text-center text-sm text-slate-500">
          برای فیلترهای انتخاب‌شده رویدادی یافت نشد.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[850px] text-right text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
              <tr>
                <th class="px-4 py-4 font-bold">زمان</th>
                <th class="px-4 py-4 font-bold">رویداد</th>
                <th class="px-4 py-4 font-bold">انجام‌دهنده</th>
                <th class="px-4 py-4 font-bold">هدف</th>
                <th class="px-4 py-4 font-bold">جزئیات امن</th>
                <th class="px-4 py-4 font-bold">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="log in rows" :key="log.id" class="hover:bg-slate-50/70">
                <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ formatDate(log.created_at) }}</td>
                <td class="px-4 py-4 font-bold text-slate-800">{{ eventLabels[log.event] || log.event }}</td>
                <td class="px-4 py-4">
                  <div class="font-semibold">{{ log.actor?.name || "حساب حذف‌شده / سیستمی" }}</div>
                  <div class="mt-1 text-xs text-slate-400">{{ roleLabel(log.actor?.role) }} <span v-if="log.actor_id">#{{ log.actor_id }}</span></div>
                </td>
                <td class="px-4 py-4 text-slate-600">{{ log.subject_type?.split("\\").pop() || "—" }} #{{ log.subject_id || "—" }}</td>
                <td class="max-w-sm px-4 py-4 text-xs leading-6 text-slate-500">{{ metadataLabel(log.metadata) }}</td>
                <td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-500">{{ log.ip_address || "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <nav v-if="logs.links?.length > 3" class="flex flex-wrap justify-center gap-1 border-t border-slate-100 p-4">
          <template v-for="(link, index) in logs.links" :key="index">
            <Link
              v-if="link.url"
              :href="link.url"
              preserve-scroll
              class="rounded-lg px-3 py-2 text-xs font-bold"
              :class="link.active ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              v-html="link.label"
            />
            <span v-else class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-300" v-html="link.label" />
          </template>
        </nav>
      </section>
    </main>
  </PortalLayout>
</template>

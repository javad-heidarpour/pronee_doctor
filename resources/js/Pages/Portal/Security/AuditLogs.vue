<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  logs: { type: Object, default: () => ({ data: [], links: [] }) },
});

const rows = computed(() => props.logs?.data ?? []);

const eventLabels = {
  "managed_user.created": "ایجاد حساب کاربری",
  "managed_user.updated": "ویرایش حساب کاربری",
  "managed_user.status_changed": "تغییر وضعیت حساب",
  "managed_user.deleted": "حذف حساب کاربری",
  "managed_user.restored": "بازیابی حساب کاربری",
  "secretary.assignments_updated": "تغییر دسترسی منشی",
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

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="!rows.length" class="p-10 text-center text-sm text-slate-500">
          هنوز رویدادی برای نمایش ثبت نشده است.
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
                  <div class="mt-1 text-xs text-slate-400">{{ roleLabel(log.actor?.role) }}</div>
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

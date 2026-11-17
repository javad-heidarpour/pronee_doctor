<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  sessionData: {
    type: Object,
    default: () => ({ supported: false, sessions: [], message: "" }),
  },
});

const formatActivity = (timestamp) => {
  if (!timestamp) return "نامشخص";
  return new Intl.DateTimeFormat("fa-IR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(timestamp * 1000));
};

const revoke = (key) => {
  if (!window.confirm("آیا از خروج این دستگاه اطمینان دارید؟")) return;
  router.delete(route("portal.security.sessions.destroy", { session: key }), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="نشست‌های فعال" />
  <PortalLayout title="نشست‌های فعال">
    <main dir="rtl" class="mx-auto w-full max-w-5xl p-4 text-slate-800 sm:p-6">
      <header class="mb-6">
        <h1 class="text-xl font-black text-slate-900 sm:text-2xl">نشست‌های فعال حساب</h1>
        <p class="mt-2 text-sm text-slate-500">
          دستگاه‌هایی که با حساب کاربری شما وارد سامانه شده‌اند.
        </p>
      </header>

      <div v-if="!sessionData.supported" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
        {{ sessionData.message }}
      </div>

      <section v-else class="space-y-3">
        <article
          v-for="item in sessionData.sessions"
          :key="item.key"
          class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex min-w-0 items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl">💻</div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-bold text-slate-800">{{ item.device }} · {{ item.browser }}</h2>
                <span v-if="item.is_current" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                  نشست فعلی
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-500">IP: <span dir="ltr" class="font-mono">{{ item.ip_address || "نامشخص" }}</span></p>
              <p class="mt-1 text-xs text-slate-500">آخرین فعالیت: {{ formatActivity(item.last_activity) }}</p>
            </div>
          </div>

          <button
            v-if="!item.is_current"
            type="button"
            @click="revoke(item.key)"
            class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-700 transition hover:bg-rose-50"
          >
            خروج از این دستگاه
          </button>
          <span v-else class="text-xs font-medium text-slate-400">برای خروج از این نشست از گزینه خروج استفاده کنید.</span>
        </article>

        <div v-if="!sessionData.sessions.length" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
          نشست فعالی برای این حساب پیدا نشد.
        </div>
      </section>

      <div class="mt-6">
        <Link :href="route('portal.dashboard')" class="text-sm font-bold text-teal-700 hover:text-teal-800">
          بازگشت به داشبورد
        </Link>
      </div>
    </main>
  </PortalLayout>
</template>

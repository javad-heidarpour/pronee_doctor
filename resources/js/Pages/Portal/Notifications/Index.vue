<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="mx-auto w-full max-w-5xl space-y-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-black text-slate-900">مرکز اعلان‌ها</h1>
          <p class="mt-1 text-sm text-slate-500">پیام‌های ثبت‌شده برای حساب کاربری شما</p>
        </div>
        <button
          v-if="unreadCount > 0"
          type="button"
          @click="markAllRead"
          class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-700"
        >
          خواندن همه ({{ unreadCount }})
        </button>
      </header>

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="notifications.data.length" class="divide-y divide-slate-100">
          <article v-for="item in notifications.data" :key="item.id" class="flex gap-3 p-4 sm:p-5">
            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="item.read_at ? 'bg-slate-200' : 'bg-teal-500'"></span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-bold text-slate-900">{{ item.title }}</h2>
                <time class="text-xs text-slate-400">{{ formatDate(item.created_at) }}</time>
              </div>
              <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-600">{{ item.message }}</p>
              <div class="mt-3 flex flex-wrap gap-3 text-sm">
                <a v-if="item.url" :href="item.url" class="font-bold text-teal-700 hover:underline">مشاهده جزئیات</a>
                <button v-if="!item.read_at" type="button" @click="markRead(item.id)" class="font-semibold text-slate-500 hover:text-slate-800">علامت‌گذاری به‌عنوان خوانده‌شده</button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="px-6 py-16 text-center">
          <div class="text-4xl">🔔</div>
          <p class="mt-3 font-bold text-slate-700">اعلانی برای نمایش وجود ندارد</p>
          <p class="mt-1 text-sm text-slate-400">پیام‌های جدید در همین بخش نمایش داده می‌شوند.</p>
        </div>
      </section>

      <nav v-if="notifications.links?.length > 3" class="flex flex-wrap justify-center gap-2">
        <template v-for="link in notifications.links" :key="link.label">
          <span v-if="!link.url" class="rounded-lg px-3 py-2 text-sm text-slate-300" v-html="link.label"></span>
          <a v-else :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active ? 'border-teal-600 bg-teal-50 text-teal-700' : 'border-slate-200 text-slate-600'" v-html="link.label"></a>
        </template>
      </nav>
    </div>
  </PortalLayout>
</template>

<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { router } from '@inertiajs/vue3'

defineProps({
  doctor: { type: Object, default: null },
  notifications: { type: Object, required: true },
  unreadCount: { type: Number, default: 0 },
})

const markRead = (id) => router.patch(route('portal.notifications.read', id), {}, { preserveScroll: true })
const markAllRead = () => router.post(route('portal.notifications.read-all'), {}, { preserveScroll: true })
const formatDate = (value) => value ? new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : ''
</script>

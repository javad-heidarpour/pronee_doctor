<script setup>
import { reactive } from "vue";
import { Head, router } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
const props = defineProps({ tickets: { type: Object, required: true } });
const drafts = reactive({});
function draft(ticket) { return drafts[ticket.id] ||= { status: ticket.status, staff_response: ticket.staff_response || "" }; }
function save(ticket) { router.patch(route("portal.support-tickets.respond", ticket.id), draft(ticket), { preserveScroll: true }); }
</script>
<template>
  <PortalLayout>
    <Head title="درخواست‌های پشتیبانی" />
    <main dir="rtl" class="mx-auto max-w-6xl space-y-4 p-4">
      <h1 class="text-2xl font-bold">درخواست‌های پشتیبانی</h1>
      <article v-for="ticket in tickets.data" :key="ticket.id" class="space-y-3 rounded-xl border bg-white p-5">
        <div class="flex flex-wrap justify-between gap-2"><strong>{{ ticket.subject }}</strong><span dir="ltr" class="text-xs text-gray-500">{{ ticket.reference }}</span></div>
        <p class="text-sm text-gray-500">{{ ticket.name }} · {{ ticket.email }} · {{ ticket.category }}</p>
        <p class="whitespace-pre-line leading-7">{{ ticket.message }}</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <select v-model="draft(ticket).status" class="rounded-lg border p-2"><option value="open">باز</option><option value="in_progress">در حال بررسی</option><option value="resolved">حل‌شده</option></select>
          <textarea v-model="draft(ticket).staff_response" maxlength="4000" rows="2" placeholder="پاسخ داخلی/ثبت نتیجه پیگیری" class="rounded-lg border p-2" />
        </div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-white" @click="save(ticket)">ذخیره وضعیت</button>
      </article>
      <p v-if="!tickets.data.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">درخواستی ثبت نشده است.</p>
      <nav class="flex flex-wrap gap-2"><a v-for="link in tickets.links" :key="link.label" :href="link.url || '#'" class="rounded border px-3 py-2" :class="{ 'bg-blue-50': link.active, 'pointer-events-none opacity-40': !link.url }" v-html="link.label" /></nav>
    </main>
  </PortalLayout>
</template>

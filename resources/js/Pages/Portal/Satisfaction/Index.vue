<script setup>
import { Head } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
defineProps({ doctor: Object, summary: Object, surveys: Object });
</script>
<template>
  <PortalLayout :doctor="doctor">
    <Head title="رضایت بیماران" />
    <main dir="rtl" class="mx-auto max-w-6xl space-y-5 p-4">
      <h1 class="text-2xl font-bold">سنجش تجربه مراجعه بیماران</h1>
      <p class="text-sm text-gray-500">گزارش فقط بر اساس پاسخ‌های ثبت‌شده پس از ویزیت است و نماینده همه بیماران نیست.</p>
      <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <article v-for="item in [{label:'تعداد پاسخ',value:summary.responses},{label:'رضایت کلی',value:summary.overall},{label:'ارتباط',value:summary.communication},{label:'زمان انتظار',value:summary.waiting},{label:'امکانات',value:summary.facility}]" :key="item.label" class="rounded-xl border bg-white p-4"><p class="text-sm text-gray-500">{{ item.label }}</p><strong class="mt-2 block text-2xl">{{ item.value }}</strong></article>
      </section>
      <section class="space-y-3">
        <article v-for="survey in surveys.data" :key="survey.id" class="rounded-xl border bg-white p-4">
          <div class="flex flex-wrap justify-between gap-2"><strong>رضایت کلی: {{ survey.overall_rating }} از ۵</strong><span class="text-xs text-gray-500">{{ survey.created_at }}</span></div>
          <p class="mt-2 text-sm text-gray-600">ارتباط: {{ survey.communication_rating }} · انتظار: {{ survey.waiting_rating }} · امکانات: {{ survey.facility_rating }}</p>
          <p v-if="survey.comment" class="mt-3 whitespace-pre-line">{{ survey.comment }}</p>
        </article>
        <p v-if="!surveys.data.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">هنوز پاسخی ثبت نشده است.</p>
      </section>
    </main>
  </PortalLayout>
</template>

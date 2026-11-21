<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";
const props = defineProps({ doctor: { type: Object, required: true }, faqs: { type: Array, default: () => [] } });
const doctorName = computed(() => [props.doctor?.name, props.doctor?.family].filter(Boolean).join(" "));
</script>
<template>
  <PublicPageLayout :doctor="doctor" :title="'پرسش‌های متداول | ' + doctorName" subtitle="پاسخ به پرسش‌های رایج بیماران" active-item="faq">
    <Head :title="'پرسش‌های متداول | ' + doctorName" />
    <main dir="rtl" class="mx-auto max-w-4xl space-y-4 px-4 py-8">
      <h1 class="text-2xl font-bold">پرسش‌های متداول</h1>
      <article v-for="item in faqs" :key="item.id" class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold">{{ item.question }}</h2>
        <p class="mt-3 whitespace-pre-line leading-8 text-gray-600">{{ item.answer }}</p>
      </article>
      <p v-if="!faqs.length" class="rounded-xl bg-gray-50 p-6 text-center text-gray-500">پرسش متداولی برای نمایش ثبت نشده است.</p>
    </main>
  </PublicPageLayout>
</template>

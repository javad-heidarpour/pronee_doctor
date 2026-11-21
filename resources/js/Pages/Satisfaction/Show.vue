<script setup>
import { useForm, usePage, Head } from "@inertiajs/vue3";
const props = defineProps({ submitted: Boolean });
const page = usePage();
const form = useForm({ overall_rating: 5, communication_rating: 5, waiting_rating: 5, facility_rating: 5, comment: "", is_anonymous: true });
function submit() { form.post(window.location.href, { preserveScroll: true }); }
const labels = [
  { key: "overall_rating", label: "رضایت کلی" },
  { key: "communication_rating", label: "نحوه ارتباط و پاسخ‌گویی" },
  { key: "waiting_rating", label: "زمان انتظار" },
  { key: "facility_rating", label: "محیط و امکانات" },
];
</script>
<template>
  <Head title="نظرسنجی تجربه مراجعه" />
  <main dir="rtl" class="mx-auto max-w-xl space-y-6 p-4 py-10">
    <header><h1 class="text-2xl font-bold">ارزیابی تجربه مراجعه</h1><p class="mt-2 text-sm text-gray-600">نظر شما به بهبود کیفیت خدمات کمک می‌کند. این فرم اطلاعات بالینی درخواست نمی‌کند.</p></header>
    <p v-if="submitted" class="rounded-xl bg-green-50 p-5 text-green-800">برای این نوبت قبلاً نظرسنجی ثبت شده است. سپاس از همراهی شما.</p>
    <form v-else class="space-y-5 rounded-2xl border bg-white p-5" @submit.prevent="submit">
      <fieldset v-for="item in labels" :key="item.key" class="space-y-2"><legend class="font-semibold">{{ item.label }}</legend><div class="flex gap-3" dir="ltr"><label v-for="score in [1,2,3,4,5]" :key="score" class="flex items-center gap-1"><input v-model.number="form[item.key]" type="radio" :value="score" />{{ score }}</label></div></fieldset>
      <label class="block text-sm">توضیحات اختیاری<textarea v-model="form.comment" maxlength="1000" rows="4" class="mt-2 w-full rounded-lg border p-3" placeholder="لطفاً اطلاعات پزشکی یا شناسایی را وارد نکنید." /></label>
      <label class="flex items-center gap-2 text-sm"><input v-model="form.is_anonymous" type="checkbox" /> پاسخ من به‌صورت ناشناس در گزارش نمایش داده شود</label>
      <button :disabled="form.processing" class="rounded-lg bg-teal-700 px-6 py-3 text-white disabled:opacity-50">ثبت نظرسنجی</button>
    </form>
  </main>
</template>

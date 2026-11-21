<script setup>
import { ref } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";

const props = defineProps({
  doctor: { type: Object, required: true },
  faqs: { type: Array, default: () => [] },
});
const editing = ref(null);
const form = useForm({ question: "", answer: "", category: "", sort_order: 0, is_published: false });
function reset() {
  editing.value = null;
  form.reset();
  form.clearErrors();
}
function edit(item) {
  editing.value = item.id;
  form.question = item.question;
  form.answer = item.answer;
  form.category = item.category || "";
  form.sort_order = item.sort_order;
  form.is_published = item.is_published;
}
function submit() {
  const options = { preserveScroll: true, onSuccess: reset };
  editing.value ? form.put(route("portal.faqs.update", editing.value), options)
    : form.post(route("portal.faqs.store"), options);
}
function remove(id) {
  if (window.confirm("این پرسش متداول حذف شود؟")) router.delete(route("portal.faqs.destroy", id), { preserveScroll: true });
}
</script>

<template>
  <PortalLayout :doctor="doctor">
    <Head title="پرسش‌های متداول" />
    <main dir="rtl" class="mx-auto max-w-5xl space-y-6 p-4">
      <header><h1 class="text-2xl font-bold">پرسش‌های متداول و راهنما</h1><p class="mt-2 text-sm text-gray-500">پاسخ‌های عمومی برای کاهش ابهام بیماران؛ اطلاعات محرمانه بیمار را وارد نکنید.</p></header>
      <form class="space-y-3 rounded-2xl border bg-white p-5" @submit.prevent="submit">
        <h2 class="font-semibold">{{ editing ? "ویرایش پرسش" : "افزودن پرسش" }}</h2>
        <input v-model="form.question" maxlength="240" required placeholder="پرسش" class="w-full rounded-lg border p-3" />
        <textarea v-model="form.answer" maxlength="5000" required rows="4" placeholder="پاسخ" class="w-full rounded-lg border p-3" />
        <div class="grid gap-3 sm:grid-cols-2">
          <input v-model="form.category" maxlength="80" placeholder="دسته‌بندی (اختیاری)" class="rounded-lg border p-3" />
          <input v-model.number="form.sort_order" type="number" min="0" placeholder="ترتیب نمایش" class="rounded-lg border p-3" />
        </div>
        <label class="flex items-center gap-2"><input v-model="form.is_published" type="checkbox" /> انتشار در صفحه عمومی پزشک</label>
        <p v-if="form.errors.question || form.errors.answer" class="text-sm text-red-600">{{ form.errors.question || form.errors.answer }}</p>
        <div class="flex gap-2"><button :disabled="form.processing" class="rounded-lg bg-blue-600 px-5 py-2 text-white">ذخیره</button><button v-if="editing" type="button" class="rounded-lg border px-4 py-2" @click="reset">انصراف</button></div>
      </form>
      <section class="space-y-3">
        <article v-for="item in faqs" :key="item.id" class="rounded-xl border bg-white p-4">
          <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-semibold">{{ item.question }}</h3><p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ item.answer }}</p><p class="mt-2 text-xs text-gray-400">{{ item.category || "بدون دسته‌بندی" }} · {{ item.is_published ? "منتشرشده" : "پیش‌نویس" }}</p></div><div class="flex gap-2"><button class="text-blue-700" @click="edit(item)">ویرایش</button><button class="text-red-600" @click="remove(item.id)">حذف</button></div></div>
        </article>
        <p v-if="!faqs.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">هنوز پرسشی ثبت نشده است.</p>
      </section>
    </main>
  </PortalLayout>
</template>

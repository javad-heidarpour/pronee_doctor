<script setup>
import { ref } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
const props = defineProps({ doctor: Object, platforms: Array, socials: Array });
const editing = ref(null);
const form = useForm({ social_platform_id: "", username: "", url: "", is_visible: true });
function reset() { editing.value = null; form.reset(); form.clearErrors(); }
function edit(item) {
  editing.value = item.id;
  form.social_platform_id = String(item.social_platform_id);
  form.username = item.username || "";
  form.url = item.url || "";
  form.is_visible = Boolean(item.is_visible);
}
function submit() {
  const options = { preserveScroll: true, onSuccess: reset };
  editing.value ? form.put(route("portal.socials.update", editing.value), options) : form.post(route("portal.socials.store"), options);
}
function remove(id) {
  if (window.confirm("این راه ارتباطی حذف شود؟")) router.delete(route("portal.socials.destroy", id), { preserveScroll: true });
}
</script>
<template>
  <PortalLayout :doctor="doctor">
    <Head title="شبکه‌های اجتماعی و راه‌های تماس" />
    <main dir="rtl" class="mx-auto max-w-4xl space-y-6 p-4">
      <header><h1 class="text-2xl font-bold">شبکه‌های اجتماعی و راه‌های تماس</h1><p class="mt-2 text-sm text-gray-500">فقط لینک‌هایی که نمایش عمومی آن‌ها را فعال کنید در صفحه پزشک دیده می‌شوند.</p></header>
      <form class="space-y-3 rounded-2xl border bg-white p-5" @submit.prevent="submit">
        <select v-model="form.social_platform_id" required class="w-full rounded-lg border p-3"><option value="" disabled>انتخاب شبکه اجتماعی</option><option v-for="platform in platforms" :key="platform.id" :value="String(platform.id)">{{ platform.title }}</option></select>
        <input v-model="form.username" maxlength="190" placeholder="نام کاربری (در صورت نیاز)" class="w-full rounded-lg border p-3" />
        <input v-model="form.url" type="url" maxlength="2048" placeholder="لینک کامل https:// (اختیاری)" dir="ltr" class="w-full rounded-lg border p-3 text-left" />
        <label class="flex items-center gap-2"><input v-model="form.is_visible" type="checkbox" /> نمایش عمومی</label>
        <p v-if="form.errors.social_platform_id || form.errors.url" class="text-sm text-red-600">{{ form.errors.social_platform_id || form.errors.url }}</p>
        <div class="flex gap-2"><button :disabled="form.processing" class="rounded-lg bg-blue-600 px-5 py-2 text-white">ذخیره</button><button v-if="editing" type="button" class="rounded-lg border px-4 py-2" @click="reset">انصراف</button></div>
      </form>
      <section class="space-y-3">
        <article v-for="item in socials" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-4">
          <div><strong>{{ item.platform?.title || "شبکه اجتماعی" }}</strong><p class="mt-1 text-sm text-gray-500">{{ item.username || item.url || "بدون شناسه" }} · {{ item.is_visible ? "نمایش عمومی" : "مخفی" }}</p></div>
          <div class="flex gap-3"><button class="text-blue-700" @click="edit(item)">ویرایش</button><button class="text-red-600" @click="remove(item.id)">حذف</button></div>
        </article>
        <p v-if="!socials.length" class="rounded-xl border border-dashed p-8 text-center text-gray-500">راه ارتباطی ثبت نشده است.</p>
      </section>
    </main>
  </PortalLayout>
</template>

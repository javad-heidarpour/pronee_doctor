<script setup>
import { computed } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
const page = usePage();
const reference = computed(() => page.props.flash?.ticket_reference || null);
const form = useForm({ name: "", email: "", category: "booking", subject: "", message: "" });
function submit() { form.post(route("support.tickets.store"), { preserveScroll: true, onSuccess: () => form.reset("subject", "message") }); }
</script>
<template>
  <Head title="پشتیبانی پرونیکس" />
  <main dir="rtl" class="mx-auto max-w-2xl space-y-6 p-4 py-10">
    <header><h1 class="text-2xl font-bold">پشتیبانی پرونیکس</h1><p class="mt-2 text-sm text-gray-600">برای مشکلات فنی، حساب کاربری یا فرایند رزرو درخواست ثبت کنید.</p></header>
    <div v-if="reference" role="status" class="rounded-xl bg-green-50 p-4 text-green-800">کد پیگیری شما: <strong dir="ltr">{{ reference }}</strong></div>
    <form class="space-y-4 rounded-2xl border bg-white p-5" @submit.prevent="submit">
      <label class="block text-sm">نام و نام خانوادگی<input v-model="form.name" required maxlength="120" class="mt-1 w-full rounded-lg border p-3" /></label>
      <label class="block text-sm">ایمیل<input v-model="form.email" required type="email" maxlength="190" dir="ltr" class="mt-1 w-full rounded-lg border p-3 text-left" /></label>
      <label class="block text-sm">موضوع درخواست<select v-model="form.category" class="mt-1 w-full rounded-lg border p-3"><option value="booking">نوبت‌گیری</option><option value="account">حساب کاربری</option><option value="technical">مشکل فنی</option><option value="other">سایر</option></select></label>
      <label class="block text-sm">عنوان<input v-model="form.subject" required maxlength="180" class="mt-1 w-full rounded-lg border p-3" /></label>
      <label class="block text-sm">شرح درخواست<textarea v-model="form.message" required minlength="10" maxlength="4000" rows="5" class="mt-1 w-full rounded-lg border p-3" /></label>
      <p class="rounded-lg bg-amber-50 p-3 text-xs leading-6 text-amber-800">لطفاً اطلاعات پزشکی، کد ملی، رمز عبور یا اطلاعات پرداخت را در این فرم وارد نکنید.</p>
      <p v-if="form.hasErrors" class="text-sm text-red-600">لطفاً خطاهای فرم را اصلاح کنید.</p>
      <button :disabled="form.processing" class="rounded-lg bg-blue-600 px-6 py-3 text-white disabled:opacity-50">{{ form.processing ? "در حال ثبت..." : "ثبت درخواست" }}</button>
    </form>
  </main>
</template>

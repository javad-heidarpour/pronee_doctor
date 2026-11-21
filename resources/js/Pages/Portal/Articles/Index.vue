<template>
  <PortalLayout :doctor="doctor">
    <div dir="rtl" class="mx-auto max-w-6xl space-y-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-black text-slate-900">مقالات و محتوای آموزشی</h1><p class="mt-1 text-sm text-slate-500">پیش‌نویس‌ها تا زمان انتشار در وب‌سایت عمومی دیده نمی‌شوند.</p></div>
        <button @click="startNew" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white">مقاله جدید</button>
      </header>

      <form v-if="editing" @submit.prevent="save" class="space-y-4 rounded-2xl border bg-white p-5 shadow-sm">
        <h2 class="font-black text-slate-800">{{ form.id ? 'ویرایش مقاله' : 'ثبت مقاله' }}</h2>
        <label class="block text-sm font-bold">عنوان<input v-model="form.title" required minlength="5" maxlength="180" class="mt-2 w-full rounded-xl border-slate-200" /></label>
        <label class="block text-sm font-bold">خلاصه کوتاه<textarea v-model="form.excerpt" rows="2" maxlength="500" class="mt-2 w-full rounded-xl border-slate-200"></textarea></label>
        <label class="block text-sm font-bold">متن مقاله<textarea v-model="form.body" required minlength="30" rows="10" class="mt-2 w-full rounded-xl border-slate-200"></textarea></label>
        <label class="block text-sm font-bold">عنوان سئو<input v-model="form.meta_title" maxlength="180" class="mt-2 w-full rounded-xl border-slate-200" /></label>
        <label class="block text-sm font-bold">توضیحات سئو<textarea v-model="form.meta_description" maxlength="320" rows="2" class="mt-2 w-full rounded-xl border-slate-200"></textarea></label>
        <div class="flex flex-wrap gap-2"><button class="rounded-xl bg-teal-600 px-5 py-2.5 font-bold text-white">ذخیره پیش‌نویس</button><button type="button" @click="editing=false" class="rounded-xl bg-slate-100 px-5 py-2.5 font-bold text-slate-600">انصراف</button></div>
      </form>

      <section class="overflow-hidden rounded-2xl border bg-white">
        <div v-if="articles.data.length" class="divide-y">
          <article v-for="article in articles.data" :key="article.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0"><h2 class="font-bold text-slate-900">{{ article.title }}</h2><p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ article.excerpt }}</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-bold" :class="article.status==='published'?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700'">{{ article.status==='published'?'منتشرشده':'پیش‌نویس' }}</span></div>
            <div class="flex flex-wrap gap-2"><button @click="edit(article)" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold">ویرایش</button><button v-if="article.status!=='published'" @click="publish(article.id)" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white">انتشار</button><button v-else @click="unpublish(article.id)" class="rounded-lg bg-amber-100 px-3 py-2 text-sm font-bold text-amber-800">لغو انتشار</button><button @click="remove(article.id)" class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700">حذف</button></div>
          </article>
        </div>
        <p v-else class="p-12 text-center text-sm text-slate-500">هنوز مقاله‌ای ثبت نشده است.</p>
      </section>
    </div>
  </PortalLayout>
</template>
<script setup>
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
const props=defineProps({doctor:Object,articles:Object})
const editing=ref(false)
const empty=()=>({id:null,title:'',excerpt:'',body:'',meta_title:'',meta_description:''})
const form=reactive(empty())
const startNew=()=>{Object.assign(form,empty());editing.value=true}
const edit=(a)=>{Object.assign(form,{id:a.id,title:a.title,excerpt:a.excerpt||'',body:a.body,meta_title:a.meta_title||'',meta_description:a.meta_description||''});editing.value=true}
const save=()=>{const payload={...form};if(form.id)router.put(route('portal.articles.update',form.id),payload,{onSuccess:()=>editing.value=false});else router.post(route('portal.articles.store'),payload,{onSuccess:()=>editing.value=false})}
const publish=(id)=>router.patch(route('portal.articles.publish',id))
const unpublish=(id)=>router.patch(route('portal.articles.unpublish',id))
const remove=(id)=>{if(confirm('این مقاله حذف شود؟'))router.delete(route('portal.articles.destroy',id))}
</script>

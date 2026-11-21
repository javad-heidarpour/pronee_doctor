<template>
  <main dir="rtl" class="mx-auto max-w-5xl space-y-8 px-4 py-10">
    <header class="rounded-3xl bg-gradient-to-l from-teal-800 to-cyan-700 p-7 text-white">
      <p class="text-sm text-teal-100">مطالب آموزشی پزشک</p>
      <h1 class="mt-2 text-2xl font-black">{{ doctor.caption }} {{ doctor.name }} {{ doctor.family }}</h1>
      <p class="mt-2 text-sm text-teal-50">مقالات و مطالب منتشرشده</p>
    </header>
    <section v-if="articles.data.length" class="grid gap-5 md:grid-cols-2">
      <article v-for="article in articles.data" :key="article.slug" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <time class="text-xs text-slate-400">{{ formatDate(article.published_at) }}</time>
        <h2 class="mt-2 text-xl font-black text-slate-900">{{ article.title }}</h2>
        <p v-if="article.excerpt" class="mt-3 leading-7 text-slate-600">{{ article.excerpt }}</p>
        <details class="mt-4"><summary class="cursor-pointer font-bold text-teal-700">مطالعه مقاله</summary><p class="mt-3 whitespace-pre-line leading-8 text-slate-700">{{ article.body }}</p></details>
      </article>
    </section>
    <p v-else class="rounded-2xl border bg-white p-12 text-center text-slate-500">مقاله منتشرشده‌ای برای نمایش وجود ندارد.</p>
    <nav v-if="articles.links?.length>3" class="flex flex-wrap justify-center gap-2"><a v-for="link in articles.links" :key="link.label" :href="link.url||undefined" v-html="link.label" class="rounded-lg border px-3 py-2 text-sm" :class="link.active?'border-teal-600 bg-teal-50 text-teal-700':'border-slate-200 text-slate-600'"></a></nav>
  </main>
</template>
<script setup>
defineProps({doctor:Object,articles:Object})
const formatDate=(value)=>value?new Intl.DateTimeFormat('fa-IR',{dateStyle:'long'}).format(new Date(value)):''
</script>

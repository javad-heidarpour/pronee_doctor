<script setup>
import { Head, Link } from "@inertiajs/vue3";

const features = [
  {
    number: "۰۱",
    title: "نوبت‌دهی و پذیرش",
    description: "مدیریت نوبت‌ها، زمان‌های کاری، شعب و پذیرش بیمار در یک مسیر روشن و منظم.",
    icon: "calendar",
  },
  {
    number: "۰۲",
    title: "پرونده و مستندسازی پزشکی",
    description: "ثبت اطلاعات بیمار و مستندات ویزیت با ساختار SOAP؛ از پیش‌نویس تا نهایی‌سازی یادداشت بالینی.",
    icon: "file",
  },
  {
    number: "۰۳",
    title: "نسخه و پیگیری بیمار",
    description: "ثبت اقلام دارویی و پیگیری مراحل مراقبت، یادآوری‌ها و تماس‌های بعدی با رعایت رضایت بیمار.",
    icon: "heart",
  },
  {
    number: "۰۴",
    title: "مدیریت کلینیک و تیم",
    description: "سامان‌دهی پزشکان، منشی‌ها، کارکنان، شعب و سطح دسترسی هر عضو در محدوده مسئولیت خودش.",
    icon: "users",
  },
  {
    number: "۰۵",
    title: "مالی، انبار و تدارکات",
    description: "مدیریت دریافت‌ها، هزینه‌ها، سهم همکاران، موجودی و گردش‌های عملیاتی کلینیک.",
    icon: "wallet",
  },
  {
    number: "۰۶",
    title: "مغز هوشمند مدیریت کلینیک",
    description: "نمایی روشن از ظرفیت نوبت‌ها، روند مراجعه، لغوها و فرصت‌های بهبود برنامه‌ریزی.",
    icon: "brain",
  },
  {
    number: "۰۷",
    title: "همکاری حرفه‌ای",
    description: "پشتیبانی از همکاری پزشکان و ارائه‌دهندگان خدمات، توافق‌های مالی و تسویه‌های داخلی.",
    icon: "handshake",
  },
  {
    number: "۰۸",
    title: "زیرساخت تبادل سلامت",
    description: "آماده‌سازی ساختار امن صف تبادل و ثبت وضعیت پیام‌ها برای اتصال به سرویس‌های رسمی پس از دریافت قرارداد و مشخصات فنی.",
    icon: "shield",
  },
];

const audiences = [
  { title: "پزشکان و متخصصان", text: "تمرکز بیشتر بر مراقبت و مدیریت ساده‌تر مطب." },
  { title: "کلینیک‌ها و درمانگاه‌ها", text: "هماهنگی شعب، تیم، پذیرش و عملیات روزانه." },
  { title: "منشی‌ها و کارکنان", text: "انجام مسئولیت‌ها بر اساس دسترسی تعریف‌شده." },
  { title: "بیماران", text: "دسترسی منظم‌تر به نوبت، اطلاعات و پیگیری مراقبت." },
];

const steps = [
  { title: "ثبت‌نام و راه‌اندازی", text: "حساب کاربری و اطلاعات مجموعه درمانی را تکمیل کنید." },
  { title: "تعریف تیم و خدمات", text: "پزشکان، شعب، کارکنان، زمان‌ها و خدمات را سامان دهید." },
  { title: "شروع مدیریت روزانه", text: "نوبت‌ها، پرونده‌ها و امور کلینیک را از پنل دنبال کنید." },
];

const iconPaths = {
  calendar: "M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z",
  file: "M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm7 0v5h5M9 13h6m-6 4h6",
  heart: "M20.8 8.8c0 5.2-8.8 11-8.8 11s-8.8-5.8-8.8-11A4.8 4.8 0 0 1 12 6.4a4.8 4.8 0 0 1 8.8 2.4ZM12 9v6m-3-3h6",
  users: "M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7.5a4 4 0 0 1 0 7.7M20 15a4 4 0 0 1 2 3.5V21",
  wallet: "M3 6.5A2.5 2.5 0 0 1 5.5 4H20v16H5.5A2.5 2.5 0 0 1 3 17.5v-11ZM3 7h17m-5 6h5m-5 0a1 1 0 1 0 0 .01",
  brain: "M9 4a3 3 0 0 0-5 2.2A3.5 3.5 0 0 0 4 13a3 3 0 0 0 5 2.2V4Zm6 0a3 3 0 0 1 5 2.2A3.5 3.5 0 0 1 20 13a3 3 0 0 1-5 2.2V4ZM9 8H7m2 5H6m9-5h2m-2 5h3M12 3v18",
  handshake: "m8 12 2.5 2.5a2 2 0 0 0 2.8 0L18 10m-9-4 3-2 3 2 3-1 3 4-3 3M3 9l3-3 3 2-4 8-3-2 1-5Zm18 0-3-3-3 2 4 8 3-2-1-5Z",
  shield: "M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Zm-3 9 2 2 4-4",
};
</script>

<template>
  <Head title="درباره پرونیکس | مدیریت هوشمند خدمات سلامت" />

  <div dir="rtl" class="min-h-screen overflow-hidden bg-[#f7faf8] text-slate-800">
    <header class="sticky top-0 z-40 border-b border-emerald-950/5 bg-white/90 backdrop-blur-xl">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" class="flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-lg shadow-emerald-900/15">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
              <path d="M12 4v16M4 12h16" /><circle cx="12" cy="12" r="9" />
            </svg>
          </span>
          <span>
            <span class="block text-lg font-black text-slate-900">پرونیکس</span>
            <span class="block text-[11px] text-slate-500">مدیریت هوشمند خدمات سلامت</span>
          </span>
        </Link>
        <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
          <a href="#features" class="transition hover:text-emerald-700">امکانات</a>
          <a href="#audience" class="transition hover:text-emerald-700">برای چه کسانی؟</a>
          <a href="#start" class="transition hover:text-emerald-700">شروع کار</a>
        </nav>
        <Link href="/portal/login" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800 sm:px-5 sm:text-sm">
          ورود به پنل
        </Link>
      </div>
    </header>

    <main>
      <section class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_85%_20%,rgba(16,185,129,.15),transparent_40%),radial-gradient(ellipse_at_5%_90%,rgba(20,184,166,.10),transparent_35%)]"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[1.1fr_.9fr] lg:px-8 lg:py-28">
          <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-white/80 px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm">
              <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
              یک همراه برای مدیریت بهتر خدمات سلامت
            </span>
            <h1 class="mt-6 max-w-2xl text-3xl font-black leading-[1.7] tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
              پرونیکس؛ نظم بیشتر برای کلینیک،
              <span class="text-emerald-700">رسیدگی بهتر به بیمار</span>
            </h1>
            <p class="mt-5 max-w-2xl text-sm leading-9 text-slate-600 sm:text-base">
              پرونیکس یک سامانه یکپارچه برای مدیریت مطب و کلینیک است؛ از نوبت‌دهی و پذیرش تا پرونده پزشکی، پیگیری بیمار، امور مالی و هماهنگی تیم درمان. ابزارهایی که کارهای روزانه را منظم‌تر می‌کنند تا فرصت بیشتری برای مراقبت از بیمار داشته باشید.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <a href="#features" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-800">
                آشنایی با امکانات
                <span aria-hidden="true">←</span>
              </a>
              <Link href="/portal/login" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-800">
                ورود کاربران
              </Link>
            </div>
            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-xs text-slate-500">
              <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>مناسب مطب و مجموعه درمانی</span>
              <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>دسترسی نقش‌محور</span>
              <span class="inline-flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>طراحی‌شده برای گردش کار واقعی</span>
            </div>
          </div>

          <div class="relative mx-auto w-full max-w-lg">
            <div class="absolute -inset-5 rounded-[2.5rem] bg-gradient-to-br from-emerald-200/50 to-teal-100/30 blur-2xl"></div>
            <div class="relative rounded-[2rem] border border-white bg-white/90 p-5 shadow-[0_30px_90px_rgba(15,70,50,.13)] backdrop-blur">
              <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                  <p class="text-xs text-slate-500">نمایی از تجربه مدیریت</p>
                  <h2 class="mt-1 text-base font-black text-slate-900">همه‌چیز در یک نگاه</h2>
                </div>
                <span class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">پرونیکس</span>
              </div>
              <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-emerald-50 p-4">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-emerald-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13H3V6a2 2 0 0 1 2-2Z"/></svg>
                  </span>
                  <p class="mt-4 text-sm font-black text-slate-800">نوبت و پذیرش</p>
                  <p class="mt-1 text-xs leading-6 text-slate-500">برنامه‌ریزی و نظم روزانه</p>
                </div>
                <div class="rounded-2xl bg-sky-50 p-4">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-sky-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm7 0v5h5M9 13h6m-6 4h6"/></svg>
                  </span>
                  <p class="mt-4 text-sm font-black text-slate-800">پرونده بیمار</p>
                  <p class="mt-1 text-xs leading-6 text-slate-500">سوابق و مستندات ویزیت</p>
                </div>
                <div class="rounded-2xl bg-violet-50 p-4">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-violet-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18m-6-6 6 6 6-6M5 5h14"/></svg>
                  </span>
                  <p class="mt-4 text-sm font-black text-slate-800">مدیریت عملیات</p>
                  <p class="mt-1 text-xs leading-6 text-slate-500">تیم، مالی و منابع</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4">
                  <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-amber-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Zm-3 9 2 2 4-4"/></svg>
                  </span>
                  <p class="mt-4 text-sm font-black text-slate-800">امنیت و دسترسی</p>
                  <p class="mt-1 text-xs leading-6 text-slate-500">دسترسی متناسب با نقش</p>
                </div>
              </div>
              <div class="mt-4 flex items-center gap-3 rounded-2xl border border-emerald-100 bg-white p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 18V9m5 9V5m5 13v-6m5 6V3"/></svg>
                </span>
                <div>
                  <p class="text-sm font-bold text-slate-800">تصمیم‌گیری با دید بهتر</p>
                  <p class="mt-1 text-xs leading-6 text-slate-500">گزارش‌ها و تحلیل‌های عملیاتی برای شناخت وضعیت کلینیک</p>
                </div>
              </div>
              <p class="mt-4 text-center text-[10px] text-slate-400">تصویر مفهومی از بخش‌های سامانه، نه داده واقعی بیماران</p>
            </div>
          </div>
        </div>
      </section>

      <section id="features" class="scroll-mt-24 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="mx-auto max-w-2xl text-center">
            <span class="text-xs font-black tracking-wide text-emerald-700">امکانات پرونیکس</span>
            <h2 class="mt-3 text-2xl font-black text-slate-950 sm:text-3xl">کارهای مهم درمانگاه، در کنار هم</h2>
            <p class="mt-4 text-sm leading-8 text-slate-500">به‌جای پراکندگی اطلاعات و ابزارها، مسیرهای اصلی مدیریت مجموعه درمانی را یکپارچه‌تر دنبال کنید.</p>
          </div>
          <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article v-for="feature in features" :key="feature.number" class="group rounded-3xl border border-slate-100 bg-[#fbfdfb] p-5 transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/5">
              <div class="flex items-center justify-between">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white">
                  <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path :d="iconPaths[feature.icon]" /></svg>
                </span>
                <span class="text-xs font-bold text-slate-300">{{ feature.number }}</span>
              </div>
              <h3 class="mt-5 text-sm font-black text-slate-900">{{ feature.title }}</h3>
              <p class="mt-2 text-xs leading-7 text-slate-500">{{ feature.description }}</p>
            </article>
          </div>
          <p class="mx-auto mt-7 max-w-4xl rounded-2xl bg-amber-50 px-5 py-4 text-xs leading-7 text-amber-900">
            <strong>یک نکته شفاف:</strong>
            اتصال عملیاتی به سامانه‌های بیرونی مانند سپاس و بیمه‌گرها، به قرارداد، مستندات فنی و دسترسی رسمی هر سرویس وابسته است. پرونیکس زیرساخت تبادل و ثبت وضعیت را فراهم می‌کند؛ فعال بودن اتصال زنده هر سرویس باید جداگانه تأیید شود.
          </p>
        </div>
      </section>

      <section id="audience" class="scroll-mt-24 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="grid gap-10 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
            <div>
              <span class="text-xs font-black text-emerald-700">برای اعضای زنجیره سلامت</span>
              <h2 class="mt-3 text-2xl font-black leading-relaxed text-slate-950 sm:text-3xl">هرکس، ابزار مناسب نقش خودش</h2>
              <p class="mt-4 text-sm leading-8 text-slate-600">از مطب مستقل تا مجموعه چندپزشکی؛ پرونیکس برای هماهنگی بهتر میان افراد و فرایندهای درمانی طراحی شده است.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <article v-for="(item, index) in audiences" :key="item.title" class="flex gap-4 rounded-2xl border border-slate-200/80 bg-white p-5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-sm font-black text-emerald-700">۰{{ index + ۱ }}</span>
                <div>
                  <h3 class="text-sm font-black text-slate-900">{{ item.title }}</h3>
                  <p class="mt-1 text-xs leading-7 text-slate-500">{{ item.text }}</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section id="start" class="scroll-mt-24 bg-[#103d32] py-16 text-white sm:py-20">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
          <span class="text-xs font-bold text-emerald-200">شروعی ساده و مرحله‌به‌مرحله</span>
          <h2 class="mt-3 text-2xl font-black sm:text-3xl">برای مدیریت منظم‌تر، از همین‌جا شروع کنید</h2>
          <div class="mt-10 grid gap-7 text-right sm:grid-cols-3">
            <div v-for="(step, index) in steps" :key="step.title" class="relative">
              <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-black text-emerald-200">۰{{ index + ۱ }}</span>
              <h3 class="mt-4 text-sm font-black">{{ step.title }}</h3>
              <p class="mt-2 text-xs leading-7 text-emerald-100/75">{{ step.text }}</p>
            </div>
          </div>
          <Link href="/portal/login" class="mt-10 inline-flex items-center justify-center rounded-2xl bg-white px-7 py-3.5 text-sm font-black text-emerald-900 transition hover:-translate-y-0.5 hover:bg-emerald-50">ورود به پرونیکس</Link>
          <p class="mt-4 text-[11px] text-emerald-100/60">برای دریافت اطلاعات راه‌اندازی و دسترسی، با تیم پرونیکس در ارتباط باشید.</p>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-7 text-center sm:flex-row sm:px-6 lg:px-8">
        <div class="flex items-center gap-2">
          <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-700 text-sm font-black text-white">پ</span>
          <span class="text-sm font-black text-slate-800">پرونیکس</span>
        </div>
        <p class="text-xs text-slate-400">هوش مصنوعی پرونیکس © ۲۰۲۶</p>
        <Link href="/" class="text-xs font-bold text-emerald-700 hover:text-emerald-900">صفحه اصلی</Link>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const openFaq = ref(0)
const openModule = ref(-1)

const audiences = [
  { icon: '🏥', title: 'مدیران کلینیک و درمانگاه', text: 'هماهنگی پذیرش، نوبت‌ها، کارکنان و روندهای روزانه در یک فضای منظم.' },
  { icon: '🩺', title: 'پزشکان و متخصصان', text: 'مدیریت برنامه کاری، پرونده و روند مراجعه بیماران و حضور حرفه‌ای آنلاین.' },
  { icon: '🧑‍💼', title: 'منشی‌ها و تیم پذیرش', text: 'ثبت و پیگیری نوبت‌ها و انجام امور روزانه بر اساس سطح دسترسی.' },
  { icon: '💚', title: 'بیماران و مراجعان', text: 'دسترسی ساده‌تر به نوبت‌ها و اطلاعات سلامت و پیگیری برنامه مراقبت.' },
]

const modules = [
  {
    number: '۰۱', icon: '📅', title: 'نوبت‌دهی و پذیرش',
    text: 'از ثبت درخواست مراجعه تا مدیریت برنامه روزانه پزشک و پذیرش بیمار.',
    details: [
      'تعریف روزها و ساعت‌های کاری و زمان‌های قابل رزرو پزشک',
      'ثبت و مدیریت نوبت‌های حضوری و آنلاین و مشاهده وضعیت آن‌ها',
      'انتخاب محل ارائه خدمت یا کلینیک و سامان‌دهی برنامه پزشک',
      'پیگیری تغییر زمان، لغو نوبت و وضعیت مراجعه بیمار',
      'نمایش اطلاعات نوبت برای تیم پذیرش متناسب با سطح دسترسی'
    ],
    audience: 'پزشک، منشی، مسئول پذیرش و مدیر مرکز',
    workflow: 'تعریف برنامه کاری ← انتخاب زمان مراجعه ← ثبت نوبت ← پذیرش و پیگیری'
  },
  {
    number: '۰۲', icon: '🗂️', title: 'پرونده و اطلاعات بیمار',
    text: 'اطلاعات بیمار و سوابق ثبت‌شده را منظم و قابل پیگیری نگه دارید.',
    details: [
      'ثبت و نگهداری اطلاعات پایه و راه‌های ارتباطی بیمار',
      'دسترسی به سوابق و اطلاعات درمانی ثبت‌شده در پرونده',
      'ارتباط اطلاعات بیمار با نوبت‌ها و مراجعات مرتبط',
      'نمایش اطلاعات بر اساس مجوز کاربر و مسئولیت او',
      'پشتیبانی از مسیر دسترسی بیمار به بخش‌های مجاز اطلاعات سلامت'
    ],
    audience: 'پزشک، کارکنان مجاز درمان و خود بیمار در بخش‌های قابل دسترس',
    workflow: 'شناسایی بیمار ← مشاهده پرونده مجاز ← ثبت اطلاعات مراجعه ← پیگیری سوابق'
  },
  {
    number: '۰۳', icon: '💊', title: 'نسخه و مراقبت درمانی',
    text: 'فراتر از زمان ویزیت؛ برنامه درمان و مراقبت بیمار را دنبال کنید.',
    details: [
      'ثبت اطلاعات نسخه و دارو در فرایندهای پشتیبانی‌شده سامانه',
      'تعریف برنامه دارویی و ثبت نوبت‌های مصرف',
      'ثبت انجام یا عدم انجام دوزهای دارویی توسط بیمار',
      'امکان توقف یا پیگیری برنامه دارویی بر اساس دسترسی و وضعیت آن',
      'ثبت مشاهدات و همراهی بیمار در مسیرهای مراقبتی'
    ],
    audience: 'پزشک، بیمار و کاربران درمانی دارای مجوز',
    workflow: 'ثبت برنامه درمان ← مشاهده دستورها ← ثبت پیگیری بیمار ← بررسی روند مراقبت'
  },
  {
    number: '۰۴', icon: '👥', title: 'مدیریت تیم و دسترسی‌ها',
    text: 'وظایف و دسترسی‌های کاربران را متناسب با نقش آن‌ها سامان دهید.',
    details: [
      'تفکیک حساب کاربری پزشک و کارکنان اجرایی',
      'تعریف نقش‌ها و محدودکردن دسترسی به بخش‌های مجاز',
      'هماهنگی فعالیت منشی و تیم پذیرش با برنامه پزشک',
      'پشتیبانی از فعالیت در محیط کلینیک و محل‌های ارائه خدمت',
      'مدیریت بهتر مسئولیت‌ها بدون نیاز به اشتراک‌گذاری حساب کاربری'
    ],
    audience: 'مدیر کلینیک، پزشک و مدیر سامانه',
    workflow: 'تعریف کاربر ← تعیین نقش و مجوز ← انجام وظیفه ← نظارت مدیر'
  },
  {
    number: '۰۵', icon: '💳', title: 'پرداخت و امور مالی',
    text: 'فرایندهای پرداخت و اطلاعات مالی مرتبط با خدمات را سامان‌دهی کنید.',
    details: [
      'ثبت اطلاعات هزینه خدمت و مبلغ قابل پرداخت در جریان نوبت',
      'پشتیبانی از فرایند پرداخت آنلاین درگاه‌های پیکربندی‌شده',
      'نگهداری وضعیت پرداخت مرتبط با نوبت و مراجعه',
      'نمایش وضعیت پرداخت برای پیگیری کارکنان مجاز',
      'زیرساخت ثبت توافق‌ها و تسویه‌های داخلی در همکاری‌های حرفه‌ای'
    ],
    audience: 'پزشک، مسئول مالی، مدیر مرکز و کارکنان مجاز',
    workflow: 'تعریف هزینه ← ثبت خدمت یا نوبت ← پرداخت یا ثبت وضعیت ← پیگیری مالی'
  },
  {
    number: '۰۶', icon: '📊', title: 'گزارش و مدیریت عملکرد',
    text: 'برای تصمیم‌گیری روزانه، تصویری روشن‌تر از فعالیت‌های مرکز داشته باشید.',
    details: [
      'مرور وضعیت نوبت‌ها و مراجعات ثبت‌شده',
      'پیگیری وضعیت‌های پرداخت و امور اجرایی مرتبط',
      'دسترسی سریع‌تر مدیران به اطلاعات عملیاتی در دسترس',
      'استفاده از داده‌های ثبت‌شده برای شناسایی گلوگاه‌های کاری',
      'زمینه‌سازی برای توسعه گزارش‌های تحلیلی و مدیریتی'
    ],
    audience: 'مدیر کلینیک، پزشک و مسئول اجرایی',
    workflow: 'ثبت فعالیت‌ها ← تجمیع اطلاعات ← مشاهده گزارش ← تصمیم و بهبود فرایند'
  },
  {
    number: '۰۷', icon: '🌐', title: 'معرفی پزشک و جذب بیمار',
    text: 'حضور حرفه‌ای آنلاین داشته باشید و راه ارتباط بیمار با مطب را ساده کنید.',
    details: [
      'ایجاد صفحه اختصاصی پزشک با اطلاعات حرفه‌ای',
      'معرفی تخصص، خدمات، جراحی‌ها و اطلاعات مطب',
      'نمایش نشانی و راه‌های ارتباطی و شبکه‌های اجتماعی',
      'امکان معرفی خدمات و محتوای آموزشی یا اطلاع‌رسانی',
      'هدایت مراجعه‌کننده به مسیر ثبت نوبت آنلاین'
    ],
    audience: 'پزشکان، مطب‌ها و مراکز درمانی',
    workflow: 'تکمیل صفحه پزشک ← معرفی خدمات ← آشنایی بیمار ← درخواست نوبت'
  },
  {
    number: '۰۸', icon: '🤝', title: 'همکاری حرفه‌ای',
    text: 'همکاری پزشکان و مراکز را با توافق‌ها و تسویه‌های روشن‌تر مدیریت کنید.',
    details: [
      'ثبت چارچوب همکاری میان ارائه‌دهنده خدمت و مرکز',
      'پشتیبانی از مدل‌های توافق ثابت، درصدی یا ترکیبی در بخش‌های پیاده‌سازی‌شده',
      'محاسبه و نگهداری مبنای تسویه بر اساس اطلاعات ثبت‌شده',
      'ایجاد صورتحساب داخلی و امکان بررسی و تأیید طرفین',
      'تفکیک دسترسی‌ها و سوابق همکاری برای پیگیری شفاف‌تر'
    ],
    audience: 'پزشکان، متخصصان، کلینیک‌ها و مدیران مراکز',
    workflow: 'تعریف همکاری ← ثبت خدمات و توافق ← محاسبه سهم‌ها ← بررسی و تسویه داخلی'
  },
]

const faqs = [
  { q: 'پرونیکس برای چه مجموعه‌هایی مناسب است؟', a: 'برای مطب پزشکان، کلینیک‌ها، درمانگاه‌ها و مجموعه‌های ارائه‌دهنده خدمات سلامت طراحی شده است. امکانات قابل استفاده به نقش و نوع فعالیت هر مجموعه بستگی دارد.' },
  { q: 'آیا بیمار هم می‌تواند از پرونیکس استفاده کند؟', a: 'بله. بخش‌های مرتبط با بیمار برای مشاهده و پیگیری اطلاعات سلامت و برنامه‌های مراقبتی در نظر گرفته شده‌اند؛ دسترسی به اطلاعات تابع مجوز و تنظیمات سامانه است.' },
  { q: 'آیا پرونیکس به بیمه‌ها و سامانه سپاس متصل است؟', a: 'زیرساخت فنی تبادل اطلاعات و ثبت وضعیت استعلام‌ها در حال توسعه است. اتصال عملیاتی به هر سازمان، منوط به دریافت مستندات، مجوزها و دسترسی رسمی همان سرویس است.' },
  { q: 'قابلیت‌های هوش مصنوعی چه زمانی ارائه می‌شوند؟', a: 'قابلیت‌های هوشمند به‌عنوان مسیر توسعه محصول دنبال می‌شوند و تا زمان عرضه و فعال‌سازی رسمی، نباید آن‌ها را جزو خدمات فعال سامانه در نظر گرفت.' },
]

const scrollTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
</script>

<template>
  <Head title="معرفی پرونیکس | مدیریت هوشمند خدمات سلامت">
    <meta name="description" content="پرونیکس؛ بستری یکپارچه برای مدیریت نوبت، پذیرش، پرونده بیمار، مراقبت درمانی و امور روزمره مطب و کلینیک." />
    <meta property="og:title" content="پرونیکس؛ مدیریت منظم‌تر خدمات سلامت" />
    <meta property="og:description" content="از نوبت‌دهی تا پیگیری مراقبت؛ ابزارهای کاربردی برای پزشکان، کلینیک‌ها و بیماران." />
  </Head>

  <div dir="rtl" class="min-h-screen overflow-hidden bg-[#f5f8f5] font-[Vazirmatn,IRANSans,Tahoma,sans-serif] text-slate-800">
    <header class="relative isolate overflow-hidden bg-[#103f32] text-white">
      <div class="pointer-events-none absolute -left-24 -top-28 h-96 w-96 rounded-full bg-emerald-400/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-48 right-1/3 h-96 w-96 rounded-full bg-lime-300/10 blur-3xl"></div>
      <nav class="relative mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
        <a href="#top" class="flex items-center gap-3" aria-label="پرونیکس">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-2xl text-emerald-800 shadow-lg">✚</span>
          <span>
            <strong class="block text-xl font-black tracking-tight">پرونیکس</strong>
            <span class="text-[10px] text-emerald-100/75">همراه مدیریت خدمات سلامت</span>
          </span>
        </a>
        <div class="hidden items-center gap-7 text-sm font-semibold text-emerald-50/90 md:flex">
          <button @click="scrollTo('features')" class="transition hover:text-lime-200">امکانات</button>
          <button @click="scrollTo('audience')" class="transition hover:text-lime-200">برای چه کسانی؟</button>
          <button @click="scrollTo('faq')" class="transition hover:text-lime-200">پرسش‌های رایج</button>
        </div>
        <Link href="/register/doctor" class="rounded-xl bg-white px-4 py-2.5 text-xs font-extrabold text-emerald-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-lime-100 sm:text-sm">شروع همکاری</Link>
      </nav>

      <section id="top" class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 pb-24 pt-12 sm:px-8 sm:pb-32 sm:pt-20 lg:grid-cols-[1.05fr_.95fr]">
        <div class="max-w-2xl">
          <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold text-emerald-100 backdrop-blur">
            <span class="h-2 w-2 rounded-full bg-lime-300"></span>
            یک مسیر ساده‌تر برای مدیریت خدمات سلامت
          </div>
          <h1 class="text-4xl font-black leading-[1.5] tracking-tight sm:text-5xl lg:text-[3.5rem]">
            تمرکز شما بر درمان؛
            <span class="block text-lime-200">نظم بیشتر در مدیریت</span>
          </h1>
          <p class="mt-6 max-w-xl text-base leading-9 text-emerald-50/85 sm:text-lg">
            پرونیکس مجموعه‌ای از ابزارهای کاربردی برای مدیریت مطب و کلینیک است؛ از نوبت‌دهی و پذیرش تا پرونده بیمار، پیگیری مراقبت و هماهنگی تیم.
          </p>
          <div class="mt-9 flex flex-col gap-3 sm:flex-row">
            <Link href="/register/doctor" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-lime-300 px-7 py-4 text-sm font-black text-emerald-950 shadow-xl shadow-black/10 transition hover:-translate-y-1 hover:bg-lime-200">
              ثبت‌نام پزشک <span aria-hidden="true">←</span>
            </Link>
            <button @click="scrollTo('features')" class="rounded-2xl border border-white/25 bg-white/5 px-7 py-4 text-sm font-bold text-white transition hover:bg-white/10">آشنایی با امکانات</button>
          </div>
          <div class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-xs font-medium text-emerald-100/75">
            <span>✓ محیط فارسی و ساده</span>
            <span>✓ مناسب موبایل و دسکتاپ</span>
            <span>✓ دسترسی بر اساس نقش کاربر</span>
          </div>
        </div>

        <div class="relative mx-auto w-full max-w-xl">
          <div class="absolute -inset-5 rounded-[2.5rem] bg-emerald-300/10 blur-2xl"></div>
          <div class="relative rounded-[2rem] border border-white/15 bg-white/10 p-3 shadow-2xl backdrop-blur-xl">
            <div class="rounded-[1.5rem] bg-[#f8fbf8] p-5 text-slate-800 sm:p-7">
              <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                  <span class="text-xs font-bold text-emerald-700">نمای کلی سامانه</span>
                  <h2 class="mt-1 text-lg font-black">مدیریت روز کاری</h2>
                </div>
                <span class="rounded-xl bg-emerald-100 px-3 py-2 text-xs font-bold text-emerald-800">پرونیکس</span>
              </div>
              <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-emerald-50 p-4">
                  <span class="text-2xl">📅</span>
                  <p class="mt-3 text-xs text-slate-500">نوبت‌دهی</p>
                  <strong class="mt-1 block text-sm font-black">برنامه‌ریزی مراجعه</strong>
                </div>
                <div class="rounded-2xl bg-sky-50 p-4">
                  <span class="text-2xl">🗂️</span>
                  <p class="mt-3 text-xs text-slate-500">اطلاعات بیمار</p>
                  <strong class="mt-1 block text-sm font-black">سوابق ساختارمند</strong>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4">
                  <span class="text-2xl">💊</span>
                  <p class="mt-3 text-xs text-slate-500">مراقبت درمانی</p>
                  <strong class="mt-1 block text-sm font-black">پیگیری برنامه‌ها</strong>
                </div>
                <div class="rounded-2xl bg-violet-50 p-4">
                  <span class="text-2xl">👥</span>
                  <p class="mt-3 text-xs text-slate-500">تیم درمان</p>
                  <strong class="mt-1 block text-sm font-black">هماهنگی کاربران</strong>
                </div>
              </div>
              <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-4">
                <div class="flex items-center justify-between">
                  <strong class="text-sm font-extrabold">یک جریان کاری منظم</strong>
                  <span class="text-xs text-emerald-700">از پذیرش تا پیگیری</span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-slate-500 sm:text-xs">
                  <span class="flex-1 rounded-lg bg-emerald-100 px-2 py-3 text-center text-emerald-800">نوبت</span>
                  <span>←</span>
                  <span class="flex-1 rounded-lg bg-sky-100 px-2 py-3 text-center text-sky-800">پذیرش</span>
                  <span>←</span>
                  <span class="flex-1 rounded-lg bg-amber-100 px-2 py-3 text-center text-amber-800">مراقبت</span>
                </div>
              </div>
              <p class="mt-4 text-center text-[10px] leading-6 text-slate-400">تصویر نمایشی برای معرفی مسیرهای کاری؛ اطلاعات و اعداد نمونه نیستند.</p>
            </div>
          </div>
          <div class="absolute -bottom-6 -left-3 hidden rounded-2xl border border-white/20 bg-white px-4 py-3 text-xs font-extrabold text-emerald-900 shadow-xl sm:block">سلامت، با مدیریت منظم‌تر</div>
        </div>
      </section>
      <div class="absolute bottom-0 left-0 h-12 w-full bg-[#f5f8f5]" style="clip-path: ellipse(60% 55% at 50% 100%)"></div>
    </header>

    <main class="mx-auto max-w-7xl px-5 sm:px-8">
      <section class="py-16 sm:py-20">
        <div class="mx-auto max-w-3xl text-center">
          <span class="text-xs font-black tracking-wide text-emerald-700">چرا پرونیکس؟</span>
          <h2 class="mt-3 text-2xl font-black leading-relaxed text-slate-900 sm:text-3xl">ابزارهای پراکنده را به یک جریان کاری روشن تبدیل کنید</h2>
          <p class="mt-4 text-sm leading-8 text-slate-500 sm:text-base">وقتی نوبت، پذیرش، اطلاعات بیمار و هماهنگی کارکنان در مسیرهای مشخص قرار بگیرند، مدیریت روزانه قابل‌پیگیری‌تر می‌شود و تیم درمان فرصت بیشتری برای تمرکز بر بیمار خواهد داشت.</p>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-3">
          <article class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <span class="text-3xl">🧭</span><h3 class="mt-4 font-black">همه‌چیز روشن‌تر</h3><p class="mt-2 text-sm leading-7 text-slate-500">وضعیت نوبت‌ها و کارهای روزانه را در مسیر مشخص دنبال کنید.</p>
          </article>
          <article class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <span class="text-3xl">🔐</span><h3 class="mt-4 font-black">دسترسی متناسب با نقش</h3><p class="mt-2 text-sm leading-7 text-slate-500">هر کاربر بر اساس مسئولیت و مجوزهای تعریف‌شده به بخش‌های مربوط دسترسی دارد.</p>
          </article>
          <article class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <span class="text-3xl">📱</span><h3 class="mt-4 font-black">همراه در هر اندازه</h3><p class="mt-2 text-sm leading-7 text-slate-500">رابط کاربری واکنش‌گرا برای استفاده در موبایل، تبلت و رایانه.</p>
          </article>
        </div>
      </section>

      <section id="features" class="scroll-mt-8 rounded-[2rem] bg-[#eaf3ed] px-5 py-14 sm:px-10 sm:py-16">
        <div class="max-w-2xl">
          <span class="text-xs font-black text-emerald-700">امکانات پرونیکس</span>
          <h2 class="mt-3 text-2xl font-black leading-relaxed text-slate-900 sm:text-3xl">از اولین تماس بیمار تا پیگیری پس از مراجعه</h2>
          <p class="mt-3 text-sm leading-8 text-slate-600">بخش‌های اصلی سامانه در کنار هم طراحی شده‌اند تا فرایندهای متداول مطب و کلینیک ساده‌تر مدیریت شوند.</p>
        </div>
        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article v-for="(item, index) in modules" :key="item.number"
            class="flex flex-col rounded-3xl border bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
            :class="openModule === index ? 'border-emerald-400 ring-2 ring-emerald-100 lg:col-span-2' : 'border-white'">
            <div class="flex items-center justify-between">
              <span class="text-3xl">{{ item.icon }}</span>
              <span class="text-xs font-black text-emerald-300">{{ item.number }}</span>
            </div>
            <h3 class="mt-5 font-black text-slate-900">{{ item.title }}</h3>
            <p class="mt-2 flex-1 text-xs leading-7 text-slate-500">{{ item.text }}</p>
            <button type="button"
              class="mt-5 flex w-full items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-xs font-extrabold text-emerald-900 transition hover:bg-emerald-100"
              :aria-expanded="openModule === index"
              :aria-controls="'module-detail-' + index"
              @click="openModule = openModule === index ? -1 : index">
              <span>{{ openModule === index ? 'بستن جزئیات' : 'مشاهده جزئیات این امکان' }}</span>
              <span class="text-lg">{{ openModule === index ? '−' : '+' }}</span>
            </button>
            <div v-if="openModule === index" :id="'module-detail-' + index" class="mt-5 border-t border-slate-100 pt-5 sm:col-span-2">
              <h4 class="text-sm font-black text-slate-800">در این بخش چه کارهایی انجام می‌شود؟</h4>
              <ul class="mt-3 space-y-3">
                <li v-for="detail in item.details" :key="detail" class="flex items-start gap-2 text-xs leading-7 text-slate-600">
                  <span class="mt-1 text-emerald-600">✓</span><span>{{ detail }}</span>
                </li>
              </ul>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                  <p class="text-[11px] font-black text-emerald-800">مناسب برای</p>
                  <p class="mt-2 text-xs leading-6 text-slate-600">{{ item.audience }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                  <p class="text-[11px] font-black text-emerald-800">نمونه مسیر استفاده</p>
                  <p class="mt-2 text-xs leading-6 text-slate-600">{{ item.workflow }}</p>
                </div>
              </div>
            </div>
          </article>
        </div>
        <p class="mt-5 text-center text-xs leading-6 text-slate-500">برای دیدن شرح کاربردها، موارد هر بخش را انتخاب کنید. امکانات در دسترس ممکن است با توجه به نقش کاربر و تنظیمات مجموعه متفاوت باشد.</p>
      </section>

      <section id="audience" class="scroll-mt-8 py-16 sm:py-20">
        <div class="text-center">
          <span class="text-xs font-black text-emerald-700">یک سامانه، چند نقش</span>
          <h2 class="mt-3 text-2xl font-black text-slate-900 sm:text-3xl">پرونیکس در کنار اعضای زنجیره سلامت</h2>
        </div>
        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article v-for="person in audiences" :key="person.title" class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-3xl">{{ person.icon }}</span>
            <h3 class="mt-5 font-black text-slate-900">{{ person.title }}</h3>
            <p class="mt-2 text-sm leading-7 text-slate-500">{{ person.text }}</p>
          </article>
        </div>
      </section>

      <section class="overflow-hidden rounded-[2rem] bg-[#123f32] px-6 py-12 text-white sm:px-12 sm:py-14">
        <div class="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
          <div>
            <span class="text-xs font-bold text-lime-200">نگاه رو به آینده</span>
            <h2 class="mt-3 text-2xl font-black leading-relaxed sm:text-3xl">مدیریت سلامت، هوشمندتر و پیوسته‌تر</h2>
            <p class="mt-4 max-w-2xl text-sm leading-8 text-emerald-50/80">پرونیکس در مسیر توسعه قابلیت‌های تحلیلی و هوش مصنوعی قرار دارد. این قابلیت‌ها پس از تکمیل، ارزیابی و فعال‌سازی رسمی در دسترس کاربران قرار خواهند گرفت.</p>
          </div>
          <div class="flex h-28 w-28 items-center justify-center rounded-[2rem] border border-white/15 bg-white/10 text-5xl">✦</div>
        </div>
      </section>

      <section id="faq" class="scroll-mt-8 py-16 sm:py-20">
        <div class="mx-auto max-w-3xl">
          <div class="text-center">
            <span class="text-xs font-black text-emerald-700">پرسش‌های رایج</span>
            <h2 class="mt-3 text-2xl font-black text-slate-900 sm:text-3xl">قبل از شروع، بیشتر بدانید</h2>
          </div>
          <div class="mt-8 space-y-3">
            <article v-for="(faq, index) in faqs" :key="faq.q" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
              <button class="flex w-full items-center justify-between gap-4 px-5 py-5 text-right text-sm font-extrabold text-slate-800" :aria-expanded="openFaq === index" @click="openFaq = openFaq === index ? -1 : index">
                {{ faq.q }}
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-lg text-emerald-800">{{ openFaq === index ? '−' : '+' }}</span>
              </button>
              <div v-if="openFaq === index" class="border-t border-slate-100 px-5 py-4 text-sm leading-8 text-slate-500">{{ faq.a }}</div>
            </article>
          </div>
        </div>
      </section>

      <section class="mb-16 rounded-[2rem] border border-emerald-100 bg-white px-6 py-10 text-center shadow-sm sm:mb-20 sm:px-12">
        <span class="text-3xl">🌱</span>
        <h2 class="mt-4 text-2xl font-black text-slate-900">برای مدیریت بهتر، از همین‌جا شروع کنید</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-8 text-slate-500">اگر پزشک هستید، صفحه اختصاصی خود را بسازید و مسیر استفاده از ابزارهای پرونیکس را آغاز کنید.</p>
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
          <Link href="/register/doctor" class="rounded-2xl bg-emerald-800 px-8 py-4 text-sm font-black text-white transition hover:bg-emerald-900">ثبت‌نام پزشک</Link>
          <Link href="/portal" class="rounded-2xl border border-emerald-200 px-8 py-4 text-sm font-black text-emerald-900 transition hover:bg-emerald-50">ورود به پنل</Link>
        </div>
      </section>
    </main>

    <footer class="bg-[#103f32] px-5 py-8 text-center text-emerald-50 sm:px-8">
      <div class="font-black">پرونیکس</div>
      <p class="mt-2 text-xs text-emerald-100/70">همراه مدیریت خدمات سلامت</p>
      <p class="mt-4 text-[11px] text-emerald-100/50">© ۲۰۲۶ پرونیکس — تمامی حقوق محفوظ است.</p>
    </footer>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";

import teamImg from "../../../assets/team.png";
import plantImg from "../../../assets/plant.jpg";

import SiteDrawer from "./SiteDrawer.vue";
import Features from "./Features.vue";
import MainMenu from "./MainMenu.vue";

import AboutDoctor from "@/Components/HomePage/AboutDoctor.vue";
import BookingSection from "@/Components/HomePage/Booking/BookingSection.vue";
import WorkingHoursPanel from "@/Components/HomePage/Booking/WorkingHoursPanel.vue";
import ClinicLocation from "@/Components/HomePage/ClinicLocation.vue";
import GallerySection from "@/Components/HomePage/GallerySection.vue";
import ReviewsSection from "@/Components/HomePage/Reviews/ReviewsSection.vue";
import SocialsSection from "@/Components/HomePage/SocialsSection.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  related_doctors: {
    type: Array,
    default: () => [],
  },
});

const doctorFullName = computed(() =>
  [props.doctor.caption, props.doctor.name, props.doctor.family]
    .filter(Boolean)
    .join(" ")
);

const pageTitle = computed(() =>
  props.doctor.meta_title ||
  `${doctorFullName.value} | ${props.doctor.specialty_title || "پزشک"} | پرونیکس`
);

const pageDescription = computed(() =>
  props.doctor.meta_description ||
  (props.doctor.description || `دریافت نوبت آنلاین از ${doctorFullName.value} در پرونیکس.`).slice(0, 160)
);

function formatPersianDate(date) {
  if (!date) return "";

  const [year, month, day] = date.split("-").map(Number);
  return new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(new Date(year, month - 1, day, 12));
}

const reviewStatsFallback = {
  average: 0,
  count: 0,
  distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
};
</script>

<template>
  <div class="page" dir="rtl">
    <div
      v-if="doctor.is_preview"
      role="status"
      class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm font-bold text-amber-900"
    >
      پیش‌نمایش خصوصی صفحه پزشک؛ این صفحه برای عموم منتشر نشده است.
    </div>
    <section class="hero">
      <!-- Background -->
      <div
        class="hero__bg"
        :style="{ backgroundImage: `url(${teamImg})` }"
        aria-hidden="true"
      ></div>

      <!-- Team Image -->
      <div class="team" aria-hidden="true">
        <img :src="teamImg" class="team__img" alt="" />
      </div>

      <!-- Hamburger -->
      <SiteDrawer :doctor="doctor" />

      <!-- Doctor Heading -->
      <div class="doctor-heading">
        <!-- Snow Background -->
        <div class="doctor-heading__snow"></div>

        <h1 class="doctor-heading__name">
          {{ doctor.caption || "" }}
          {{ doctor.name || "" }}
          {{ doctor.family || "" }}
        </h1>

        <div class="doctor-heading__specialty">
          <span class="doctor-heading__line"></span>

          <span>
            {{ doctor.specialty_title || "متخصص" }}
          </span>

          <span class="doctor-heading__line"></span>
        </div>
      </div>

      <!-- Main Content -->
      <div class="hero__inner">
        <MainMenu :doctor="doctor" />
      </div>

      <!-- Wave -->
      <svg
        class="hero__wave"
        viewBox="0 0 1440 170"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <defs>
          <linearGradient id="waveblue" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#0b5d3f" />
            <stop offset=".55" stop-color="#2fae6f" />
            <stop offset="1" stop-color="#bfe8cf" />
          </linearGradient>
        </defs>

        <path
          d="M0 70 C 260 150, 520 120, 800 95 S 1250 40, 1440 70 L1440 170 L0 170 Z"
          fill="url(#waveblue)"
          opacity=".9"
        />

        <path
          d="M0 100 C 300 170, 600 140, 900 118 S 1300 80, 1440 100 L1440 170 L0 170 Z"
          fill="#f1f8f3"
        />
      </svg>
    </section>

    <!-- Public doctor profile -->
    <main
      dir="rtl"
      class="relative mx-auto w-full max-w-7xl space-y-7 px-4 pb-24 pt-8 sm:space-y-9 sm:px-6 sm:pt-12 lg:px-8"
    >
      <Head :title="pageTitle">
        <meta name="description" :content="pageDescription" />
        <meta property="og:type" content="profile" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="pageDescription" />
        <meta v-if="doctor.canonical_url && !doctor.is_preview" property="og:url" :content="doctor.canonical_url" />
        <meta v-if="doctor.is_preview" name="robots" content="noindex,nofollow" />
        <meta v-if="doctor.photo_url" property="og:image" :content="doctor.photo_url" />
        <meta name="twitter:card" content="summary_large_image" />
        <link v-if="doctor.canonical_url && !doctor.is_preview" rel="canonical" :href="doctor.canonical_url" />
      </Head>

      <section class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
        <div id="about-doctor">
          <AboutDoctor :doctor="doctor" />
        </div>
        <div v-if="doctor.working_hours?.clinics?.length || doctor.working_hours?.holidays?.length">
          <WorkingHoursPanel :working_hours="doctor.working_hours" />
        </div>
      </section>

      <section
        v-if="doctor.honor_badges?.length"
        class="rounded-3xl border border-amber-100 bg-white p-5 shadow-sm sm:p-6"
        aria-labelledby="doctor-honors-title"
      >
        <div class="mb-4">
          <span class="text-xs font-bold text-amber-700">اعتبار حرفه‌ای</span>
          <h2 id="doctor-honors-title" class="mt-1 text-lg font-black text-slate-900">سوابق و افتخارات حرفه‌ای</h2>
        </div>
        <div class="flex flex-wrap gap-3">
          <div
            v-for="badge in doctor.honor_badges"
            :key="badge.id"
            class="inline-flex items-center gap-2 rounded-xl border border-amber-100 bg-amber-50/70 px-3 py-2 text-sm font-bold text-amber-900"
          >
            <span aria-hidden="true">{{ badge.icon || "✦" }}</span>
            <span>{{ badge.title }}</span>
          </div>
        </div>
      </section>

      <section
        v-if="doctor.services?.length"
        id="doctor-services"
        class="scroll-mt-24 rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6"
      >
        <div class="mb-5">
          <span class="text-xs font-bold text-emerald-700">خدمات مطب</span>
          <h2 class="mt-1 text-xl font-black text-slate-900">خدمات پزشکی قابل ارائه</h2>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="service in doctor.services"
            :key="service.id"
            class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
          >
            <span class="text-xs font-bold text-emerald-700">{{ service.category || "خدمات پزشکی" }}</span>
            <h3 class="mt-2 font-extrabold text-slate-800">{{ service.title }}</h3>
            <p v-if="service.short_description" class="mt-2 text-sm leading-7 text-slate-600">
              {{ service.short_description }}
            </p>
            <a
              href="#booking-section"
              class="mt-3 inline-flex text-xs font-bold text-emerald-700 hover:text-emerald-900"
            >
              دریافت نوبت
            </a>
          </article>
        </div>
      </section>

      <section id="booking-section" class="scroll-mt-24">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
          <div>
            <span class="text-xs font-bold text-emerald-700">رزرو آنلاین</span>
            <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">دریافت نوبت ویزیت</h2>
            <p class="mt-1 text-sm text-slate-500">مطب و زمان مناسب خود را انتخاب کنید.</p>
          </div>
          <span class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
            نوبت‌دهی مستقیم
          </span>
        </div>
        <BookingSection
          v-if="doctor.clinics_with_slots?.length"
          :doctor="doctor"
        />
        <div
          v-else
          class="rounded-3xl border border-dashed border-emerald-200 bg-white px-5 py-10 text-center shadow-sm"
        >
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">📅</div>
          <h3 class="mt-4 text-base font-black text-slate-800">در حال حاضر نوبت آزادی ثبت نشده است</h3>
          <p class="mx-auto mt-2 max-w-lg text-sm leading-7 text-slate-500">
            برای اطلاع از زمان‌های مراجعه، از راه‌های ارتباطی پزشک استفاده کنید یا در روزهای آینده دوباره بررسی کنید.
          </p>
        </div>

        <div
          v-if="related_doctors.length"
          class="mt-6 rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6"
        >
          <div class="mb-5">
            <span class="text-xs font-bold text-emerald-700">گزینه‌های جایگزین</span>
            <h3 class="mt-1 text-lg font-black text-slate-900">پزشکان مرتبط دارای نوبت آزاد</h3>
            <p class="mt-2 text-xs leading-6 text-slate-500">
              این گزینه‌ها بر اساس تخصص یا شهر برای دسترسی آسان‌تر نمایش داده شده‌اند و توصیه یا تشخیص پزشکی نیستند.
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article
              v-for="alternative in related_doctors"
              :key="alternative.id"
              class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
            >
              <h4 class="font-black text-slate-900">
                {{ alternative.caption || [alternative.name, alternative.family].filter(Boolean).join(" ") }}
              </h4>
              <p class="mt-1 text-xs text-slate-500">{{ alternative.specialty_title || "پزشک" }}</p>
              <span class="mt-3 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800">
                پیشنهاد بر اساس {{ alternative.match_reason }}
              </span>
              <p v-if="alternative.next_available" class="mt-3 text-sm font-bold text-slate-800">
                نزدیک‌ترین نوبت: {{ formatPersianDate(alternative.next_available.date) }}
                · {{ alternative.next_available.start_time }}
              </p>
              <p v-if="alternative.next_clinic" class="mt-1 text-xs text-slate-500">
                {{ alternative.next_clinic.name }}
                <span v-if="alternative.next_clinic.city_name"> · {{ alternative.next_clinic.city_name }}</span>
              </p>
              <Link
                :href="route('home1', { doctor: alternative.slug })"
                class="mt-4 inline-flex items-center gap-2 text-xs font-black text-emerald-700 hover:text-emerald-900"
              >
                مشاهده نوبت‌ها
                <span aria-hidden="true">←</span>
              </Link>
            </article>
          </div>
        </div>
      </section>

      <section v-if="doctor.address?.length" id="clinic-locations" class="scroll-mt-24">
        <ClinicLocation :doctor="doctor" />
      </section>

      <section v-if="doctor.gallery_images?.length" id="doctor-gallery" class="scroll-mt-24">
        <GallerySection
          :images="doctor.gallery_images"
          :categories="doctor.gallery_categories || []"
        />
      </section>

      <section id="patient-reviews" class="scroll-mt-24">
        <div class="mb-4">
          <span class="text-xs font-bold text-amber-700">تجربه مراجعان</span>
          <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">نظرات بیماران</h2>
        </div>
        <ReviewsSection
          :doctor="doctor"
          :reviews="doctor.reviews || []"
          :stats="doctor.reviews_stats || reviewStatsFallback"
        />
      </section>

      <section v-if="doctor.socials?.length" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-3">
          <h2 class="text-base font-black text-slate-900">راه‌های ارتباطی</h2>
          <p class="mt-1 text-xs text-slate-500">شبکه‌های اجتماعی و مسیرهای ارتباط با پزشک</p>
        </div>
        <SocialsSection :doctor="doctor" />
      </section>
    </main>

    <a
      href="#booking-section"
      class="fixed bottom-4 left-4 right-4 z-40 flex items-center justify-center gap-2 rounded-2xl bg-emerald-700 px-5 py-3.5 text-sm font-extrabold text-white shadow-xl shadow-emerald-900/20 transition hover:bg-emerald-800 sm:left-auto sm:right-6 sm:w-auto md:hidden"
    >
      <span>📅</span>
      دریافت نوبت آنلاین
    </a>

    <!-- Trust footer -->
    <Features :plantImg="plantImg" />
  </div>
</template>

<style scoped>
/* =========================================
   PAGE
========================================= */

.page {
  --blue-900: #0b5d3f;
  --blue-700: #0e7a4f;
  --blue-500: #1fa463;

  --ink: #1f2d3a;
  --muted: #6b7a88;
  --card: #ffffff;
  --bg-soft: #eef7f1;

  font-family: "Vazirmatn", "IRANSans", Tahoma, sans-serif;
  color: var(--ink);

  background: linear-gradient(180deg, #fff 0%, var(--bg-soft) 60%, #e3f2e8 100%);

  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

.page * {
  box-sizing: border-box;
}

/* =========================================
   HERO
========================================= */

.hero {
  position: relative;
  isolation: isolate;
  overflow: hidden;

  background: #074881;

  height: 100vh;
  min-height: 650px;
  padding: 0;
}

/* =========================================
   BACKGROUND
========================================= */

.hero__bg {
  position: absolute;
  top: 0;
  left: 0;

  width: 100%;
  height: 100vh;

  z-index: -2;

  background-position: center center;
  background-size: cover;
  background-repeat: no-repeat;

  filter: blur(10px) saturate(1.1);

  pointer-events: none;
}

.hero::before {
  content: "";

  position: absolute;
  inset: 0;

  z-index: -1;

  background: linear-gradient(180deg, rgba(10, 60, 110, 0.12), rgba(10, 60, 110, 0.3));
}

/* =========================================
   TEAM IMAGE
========================================= */

.team {
  position: absolute;
  top: 0;
  left: 0;

  width: 75%;
  height: 100vh;

  overflow: hidden;

  z-index: 1;
  pointer-events: none;
}

.team__img {
  position: absolute;
  inset: 0;

  display: block;

  width: 100%;
  height: 100%;

  object-fit: cover;
  object-position: center top;

  /*
    تصویر در لبه راست و پایین
    به تدریج محو می‌شود.
  */

  -webkit-mask-image: linear-gradient(
      to right,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    ),
    linear-gradient(
      to bottom,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    );

  -webkit-mask-composite: source-in;

  mask-image: linear-gradient(
      to right,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    ),
    linear-gradient(
      to bottom,
      #000 0%,
      #000 88%,
      rgba(0, 0, 0, 0.85) 94%,
      rgba(0, 0, 0, 0.35) 98%,
      transparent 100%
    );

  mask-composite: intersect;
}

/* =========================================
   DOCTOR HEADING
========================================= */

.doctor-heading {
  position: absolute;
  top: 24px;
  left: 50%;

  transform: translateX(-50%);

  z-index: 5;

  width: min(80%, 500px);

  align-items: center;
  text-align: center;

  isolation: isolate;
}

/* Snow Background */

.doctor-heading__snow {
  position: absolute;

  z-index: -1;

  left: -2px;
  right: -2px;

  top: -5px;
  bottom: -15px;

  background: radial-gradient(
    ellipse at 50% 55%,
    rgba(255, 255, 255, 0.98) 0%,
    rgba(255, 255, 255, 0.88) 35%,
    rgba(255, 255, 255, 0.58) 62%,
    rgba(255, 255, 255, 0) 100%
  );

  filter: blur(25px);

  pointer-events: none;
}

/* Doctor Name */

.doctor-heading__name {
  position: relative;

  margin: 0;

  color: #0b5d3f;

  font-size: clamp(23px, 2.2vw, 31px);
  font-weight: 900;
  line-height: 1.7;

  text-shadow: 0 1px 3px rgba(255, 255, 255, 0.95);
}

/* Specialty */

.doctor-heading__specialty {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 12px;
  margin-top: 0;

  color: #0e7a4f;

  font-size: 15px;
  font-weight: 700;
}

.doctor-heading__line {
  width: 30px;
  height: 1px;

  background: #1fa463;
  opacity: 0.65;
}

/* =========================================
   MAIN CONTENT
========================================= */

.hero__inner {
  position: relative;

  z-index: 10;

  display: flex;
  flex-direction: row;

  align-items: flex-start;
  justify-content: space-between;

  gap: 16px;

  padding: 120px 35px 0;

  min-height: 0;
}

.hero__inner > :first-child {
  position: relative;
  z-index: 20;
}

/* =========================================
   HERO WAVE
========================================= */

.hero__wave {
  position: absolute;

  left: 0;
  bottom: 0;

  width: 100%;
  height: 130px;

  display: block;

  z-index: 5;
  pointer-events: none;
}

/* =========================================
   LARGE DESKTOP
========================================= */

@media (min-width: 1201px) {
  .hero__inner {
    padding-top: 140px;
    padding-right: 45px;
    padding-left: 35px;
  }

  .hero__inner > :first-child {
    margin-top: 20px;
  }

  .team {
    width: 75%;
    height: 100vh;
  }

  /*
    محوشدن بیشتر در نمایشگر بزرگ
    تا خط مرزی تصویر دیده نشود.
  */

  .team__img {
    -webkit-mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      );

    -webkit-mask-composite: source-in;

    mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 86%,
        rgba(0, 0, 0, 0.85) 93%,
        rgba(0, 0, 0, 0.3) 98%,
        transparent 100%
      );

    mask-composite: intersect;
  }

  .hero__wave {
    height: 160px;
    bottom: 0;
    top: auto;
  }
}

/* =========================================
   TABLET / MEDIUM
========================================= */

@media (min-width: 768px) and (max-width: 1200px) {
  .hero {
    height: 100vh;
    min-height: 650px;
    padding: 0;
  }

  .doctor-heading {
    top: 15px;
    width: 100%;
  }

  .doctor-heading__name {
    font-size: 25px;
  }

  .hero__inner {
    padding: 110px 25px 0;

    align-items: flex-start;
    gap: 15px;
  }

  .team {
    width: 75%;
    height: 100vh;
  }

  .team__img {
    object-position: center top;

    -webkit-mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      );

    -webkit-mask-composite: source-in;

    mask-image: linear-gradient(
        to right,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      ),
      linear-gradient(
        to bottom,
        #000 0%,
        #000 91%,
        rgba(0, 0, 0, 0.8) 96%,
        transparent 100%
      );

    mask-composite: intersect;
  }

  .hero__wave {
    top: auto;
    bottom: 0;
    height: 100px;
  }
}

/* =========================================
   MOBILE
========================================= */

@media (max-width: 767px) {
  .hero {
    height: auto;
    min-height: 650px;
    padding: 0;
  }

  .hero__bg {
    top: 0;
    left: 0;

    width: 100%;
    height: 100%;

    background-position: center;
    background-size: cover;

    filter: blur(2px) saturate(1.1);
    transform: scale(1.04);
  }

  .hero::before {
    background: linear-gradient(
      180deg,
      rgba(255, 255, 255, 0.15),
      rgba(10, 60, 50, 0.15)
    );
  }

  .doctor-heading {
    top: 24px;
    width: calc(100% - 110px);
  }

  .doctor-heading__name {
    font-size: 25px;
    color: #0b5d3f;

    text-shadow: 0 2px 12px rgba(255, 255, 255, 0.85);
  }

  .doctor-heading__specialty {
    font-size: 14px;
  }

  .doctor-heading__snow {
    left: -20px;
    right: -20px;

    top: -10px;
    bottom: -18px;

    filter: blur(10px);
  }

  .hero__inner {
    display: block;

    padding: 100px 18px 100px;

    min-height: 0;
  }

  .team {
    display: none;
  }

  .hero__wave {
    top: auto;
    bottom: 0;

    height: 70px;
  }
}

/* =========================================
   SMALL MOBILE
========================================= */

/* =========================================
   SMALL MOBILE - iPhone SE
========================================= */

@media (max-width: 380px) {
  .hero__inner {
    padding-inline: 13px;
    padding-top: 200px;
    padding-bottom: 100px;
  }

  .doctor-heading {
    top: 25px;
    width: calc(100% - 70px);
  }

  .doctor-heading__name {
    font-size: 21px;
    line-height: 1.6;
  }

  .doctor-heading__specialty {
    font-size: 13px;
    margin-top: 2px;
  }

  .doctor-heading__snow {
    left: -15px;
    right: -15px;
    top: -8px;
    bottom: -12px;
  }
}
</style>

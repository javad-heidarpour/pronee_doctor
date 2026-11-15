<template>
  <!-- SEO -->
  <Head>
    <title>{{ seoTitle }}</title>
    <meta name="description" :content="seoDescription" />
    <meta property="og:type" content="profile" />
    <meta property="og:title" :content="seoTitle" />
    <meta property="og:description" :content="seoDescription" />
    <meta property="og:image" :content="doctor.photo || defaultOgImage" />
    <meta property="og:url" :content="canonicalUrl" />
    <link rel="canonical" :href="canonicalUrl" />
  </Head>
  <!-- منوی ساندویچی روی بنر -->
  <div class="absolute top-4 right-4 z-[100]">
    <slot />
  </div>

  <section class="relative overflow-hidden pb-20 pt-6 md:pt-10">
    <!-- تصویر پس‌زمینه -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <picture class="block h-full w-full">
        <source
          media="(max-width: 767px)"
          :srcset="'\\storage\\doctors\\' + 'Blue-Medical-Team-Hero-Banner-Mobily.jpg'"
        />

        <img
          :src="'\\storage\\doctors\\' + 'Blue-Medical-Team-Hero-Banner-width.jpg'"
          :alt="`عکس ${fullName}`"
          class="hero-bg-image"
        />
      </picture>

      <div class="absolute inset-0 bg-white/35"></div>

      <div
        class="absolute inset-0 bg-gradient-to-br from-teal-50/25 via-white/20 to-cyan-50/25"
      ></div>
    </div>

    <!-- جلوه‌های پس‌زمینه -->
    <div
      class="absolute -right-20 -top-20 z-[1] h-96 w-96 rounded-full bg-teal-200/15 blur-3xl"
    ></div>

    <div
      class="absolute -left-20 top-40 z-[1] h-80 w-80 rounded-full bg-cyan-200/20 blur-3xl"
    ></div>

    <!-- محتوای هدر -->
    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- منوی ساندویچی -->

      <!-- فاصله از منو -->
      <div class="mb-8 mt-8"></div>

      <!-- مشخصات پزشک -->
      <div class="flex flex-col items-center gap-8 md:flex-row md:items-end">
        <!-- اطلاعات پزشک -->
        <div class="flex-1 pb-2 text-center md:text-right">
          <div
            class="mb-3 inline-flex items-center gap-2 rounded-full bg-teal-100 px-3 py-1 text-xs font-bold text-teal-700"
          >
            <span class="h-2 w-2 rounded-full bg-teal-500 animate-pulse"></span>

            {{ doctor.specialty_title || "متخصص" }}
          </div>

          <h1
            class="mb-2 lg:text-8xl font-black text-blue-500 md:text-5xl text-5xl"
            style="
              text-shadow: -2px -2px 0 blue, 2px -2px 0 blue, -2px 2px 0 blue,
                2px 2px 0 blue, -2px 0 0 blue, 2px 0 0 blue, 0 -2px 0 blue, 0 2px 0 blue;
            "
          >
            {{ fullName }}
          </h1>

          <p
            v-if="doctor.banner"
            class="mb-4 text-lg italic text-white"
            style="
              text-shadow: -1px -1px 0 blue, 1px -1px 0 blue, -1px 1px 0 blue,
                1px 1px 0 blue;
            "
          >
            « {{ doctor.banner }} »
          </p>

          <!-- آمار پزشک -->
          <div class="flex flex-wrap justify-center gap-4 md:justify-start">
            <!-- سابقه -->
            <div
              class="flex items-center gap-2 rounded-xl bg-white/60 px-4 py-2 text-sm text-gray-600 shadow-sm backdrop-blur"
            >
              <svg
                class="h-5 w-5 text-teal-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>

              <span>
                <b class="text-gray-900">
                  {{ doctor.years_of_experience ?? 0 }}
                </b>
                سال سابقه
              </span>
            </div>

            <!-- رضایت بیماران -->
            <div
              class="flex items-center gap-2 rounded-xl bg-white/60 px-4 py-2 text-sm text-gray-600 shadow-sm backdrop-blur"
            >
              <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                />
              </svg>

              <span>
                <b class="text-gray-900"> {{ formattedSatisfaction }}% </b>
                رضایت بیماران
              </span>
            </div>

            <!-- شماره نظام پزشکی -->
            <div
              class="flex items-center gap-2 rounded-xl bg-white/60 px-4 py-2 text-sm text-gray-600 shadow-sm backdrop-blur"
            >
              <svg
                class="h-5 w-5 text-blue-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>

              <span>
                نظام پزشکی:
                <b class="text-gray-900">
                  {{ doctor.Medical_system_number || "—" }}
                </b>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
    default: () => ({}),
  },

  ImgPath: {
    type: String,
    default: "",
  },
});

const fullName = computed(() => {
  return [props.doctor.caption, props.doctor.name, props.doctor.family]
    .filter(Boolean)
    .join(" ");
});

const baseUrl = typeof window !== "undefined" ? window.location.origin : "";

const canonicalUrl = computed(() => {
  return `${baseUrl}/doctors/${props.doctor.slug || ""}`;
});

const seoTitle = computed(() => {
  const title =
    props.doctor.meta_title ||
    `${fullName.value} | ${props.doctor.specialty_title || "پزشک"}`;

  return `${title} - نوبت دهی اینترنتی`;
});

const seoDescription = computed(() => {
  if (props.doctor.meta_description) {
    return props.doctor.meta_description;
  }

  const description = props.doctor.description || "";
  const truncated =
    description.length > 155 ? `${description.substring(0, 155)}...` : description;

  return `${truncated} | رزرو نوبت آنلاین از ${fullName.value} با ${
    props.doctor.years_of_experience || 0
  } سال سابقه.`;
});

const defaultOgImage = "https://via.placeholder.com/1200x630.png?text=Doctor+Profile";

const formattedSatisfaction = computed(() => {
  const rate = Number.parseFloat(props.doctor.satisfaction_rate) || 0;
  return rate.toFixed(0);
});
</script>

<style scoped>
.hero-bg-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 15%;
  filter: blur(0.5px);
  opacity: 0.88;
  transform: scale(1.03);
  animation: hero-bg-move 18s ease-in-out infinite;
}

@keyframes hero-bg-move {
  0%,
  100% {
    transform: scale(1.03) translate3d(0, 0, 0);
  }

  50% {
    transform: scale(1.06) translate3d(0, -5px, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-bg-image {
    animation: none;
  }
}
</style>

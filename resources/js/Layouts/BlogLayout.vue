<!-- resources/js/Layouts/BlogLayout.vue -->
<template>
  <div
    dir="rtl"
    :class="{ dark: isDark }"
    class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors"
  >
    <!-- ✅ SEO Meta Tags -->
    <Head>
      <title>{{ seoTitle }}</title>
      <meta name="description" :content="seoDescription" />
      <meta name="keywords" :content="seoKeywords" />
      <meta name="author" :content="doctor?.name + ' ' + doctor?.family" />

      <!-- Open Graph -->
      <meta property="og:type" content="article" />
      <meta property="og:title" :content="seoTitle" />
      <meta property="og:description" :content="seoDescription" />
      <meta property="og:image" :content="ogImage" />
      <meta property="og:url" :content="currentUrl" />
      <meta property="og:site_name" :content="doctor?.name + ' ' + doctor?.family" />
      <meta property="og:locale" content="fa_IR" />

      <!-- Twitter Card -->
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" :content="seoTitle" />
      <meta name="twitter:description" :content="seoDescription" />
      <meta name="twitter:image" :content="ogImage" />

      <!-- Canonical URL -->
      <link rel="canonical" :href="canonicalUrl" />

      <!-- Schema.org Article JSON-LD -->
      <script type="application/ld+json" v-html="articleSchema"></script>

      <!-- Schema.org BreadcrumbList -->
      <script type="application/ld+json" v-html="breadcrumbSchema"></script>

      <!-- FAQ Schema (اگر موجود باشد) -->
      <script v-if="faqSchema" type="application/ld+json" v-html="faqSchema"></script>
    </Head>

    <!-- ✅ Header -->
    <header
      class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50 border-b border-gray-200 dark:border-gray-700"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo & Doctor Info -->
          <div class="flex items-center gap-4">
            <Link
              :href="route('portal.HomePage', doctor.slug)"
              class="flex items-center gap-3"
            >
              <div
                class="w-10 h-10 rounded-full overflow-hidden border-2 border-teal-500"
              >
                <img
                  v-if="doctor.photo"
                  :src="doctor.photo"
                  :alt="doctor.name"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center text-white font-bold"
                >
                  {{ doctor.name?.charAt(0) }}{{ doctor.family?.charAt(0) }}
                </div>
              </div>
              <div class="hidden md:block">
                <h1 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                  {{ doctor.caption }} {{ doctor.name }} {{ doctor.family }}
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ doctor.specialty_title }}
                </p>
              </div>
            </Link>
          </div>

          <!-- Navigation -->
          <nav class="hidden md:flex items-center gap-6">
            <Link
              :href="route('portal.HomePage', doctor.slug)"
              class="text-sm text-gray-700 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400 transition-colors"
            >
              صفحه اصلی
            </Link>
            <Link
              :href="route('blog.index')"
              class="text-sm font-bold text-teal-600 dark:text-teal-400"
            >
              بلاگ
            </Link>
            <a
              :href="`/doctors/${doctor.slug}#BookingSection`"
              class="text-sm text-gray-700 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400 transition-colors"
            >
              نوبت‌دهی
            </a>
          </nav>

          <!-- Actions -->
          <div class="flex items-center gap-3">
            <!-- Dark Mode Toggle -->
            <button
              @click="toggleDark"
              class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              <svg
                v-if="!isDark"
                class="w-5 h-5 text-gray-700"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                />
              </svg>
              <svg
                v-else
                class="w-5 h-5 text-yellow-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </button>

            <!-- Mobile Menu Button -->
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="md:hidden p-2 rounded-lg bg-gray-100 dark:bg-gray-700"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Mobile Menu -->
        <Transition name="slide-down">
          <div
            v-if="mobileMenuOpen"
            class="md:hidden py-4 border-t border-gray-200 dark:border-gray-700"
          >
            <nav class="flex flex-col gap-3">
              <Link
                :href="route('portal.HomePage', doctor.slug)"
                class="text-sm text-gray-700 dark:text-gray-300 hover:text-teal-600"
              >
                صفحه اصلی
              </Link>
              <Link :href="route('blog.index')" class="text-sm font-bold text-teal-600">
                بلاگ
              </Link>
              <a
                :href="`/doctors/${doctor.slug}#BookingSection`"
                class="text-sm text-gray-700 dark:text-gray-300"
              >
                نوبت‌دهی
              </a>
            </nav>
          </div>
        </Transition>
      </div>
    </header>

    <!-- ✅ Main Content -->
    <main class="min-h-[calc(100vh-200px)]">
      <slot />
    </main>

    <!-- ✅ Footer -->
    <footer class="bg-gray-900 text-white mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Doctor Info -->
          <div>
            <h3 class="text-lg font-bold mb-4">{{ doctor.name }} {{ doctor.family }}</h3>
            <p class="text-gray-400 text-sm mb-4">
              {{ doctor.specialty_title }}
            </p>
            <p class="text-gray-400 text-sm leading-7">
              {{ doctor.description?.substring(0, 150) }}...
            </p>
          </div>

          <!-- Quick Links -->
          <div>
            <h3 class="text-lg font-bold mb-4">دسترسی سریع</h3>
            <ul class="space-y-2">
              <li>
                <Link
                  :href="route('blog.index')"
                  class="text-gray-400 hover:text-teal-400 text-sm transition-colors"
                >
                  مقالات پزشکی
                </Link>
              </li>
              <li>
                <a
                  :href="route('booking')"
                  class="text-gray-400 hover:text-teal-400 text-sm transition-colors"
                >
                  رزرو نوبت
                </a>
              </li>
              <li>
                <a
                  :href="`/doctors/${doctor.slug}#location`"
                  class="text-gray-400 hover:text-teal-400 text-sm transition-colors"
                >
                  آدرس مطب
                </a>
              </li>
            </ul>
          </div>

          <!-- Contact Info -->
          <div>
            <h3 class="text-lg font-bold mb-4">تماس با ما</h3>
            <ul class="space-y-3">
              <li class="flex items-center gap-2 text-gray-400 text-sm">
                <svg
                  class="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                  />
                </svg>
                <span dir="ltr">{{ doctor.phone || "021-12345678" }}</span>
              </li>
              <li class="flex items-center gap-2 text-gray-400 text-sm">
                <svg
                  class="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
                <span>{{ doctor.email }}</span>
              </li>
            </ul>

            <!-- Social Links -->
            <div class="flex gap-3 mt-4">
              <a
                v-if="doctor.instagram"
                :href="doctor.instagram"
                target="_blank"
                class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                  />
                </svg>
              </a>
              <a
                v-if="doctor.telegram"
                :href="doctor.telegram"
                target="_blank"
                class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                  />
                </svg>
              </a>
              <a
                v-if="doctor.whatsapp"
                :href="`https://wa.me/${doctor.whatsapp}`"
                target="_blank"
                class="w-9 h-9 bg-green-500 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.74-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"
                  />
                </svg>
              </a>
              <a
                v-if="doctor.linkedin"
                :href="doctor.linkedin"
                target="_blank"
                class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"
                  />
                </svg>
              </a>
              <a
                v-if="doctor.youtube"
                :href="doctor.youtube"
                target="_blank"
                class="w-9 h-9 bg-red-600 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                  />
                </svg>
              </a>
              <a
                v-if="doctor.aparat"
                :href="doctor.aparat"
                target="_blank"
                class="w-9 h-9 bg-pink-600 rounded-lg flex items-center justify-center hover:scale-110 transition-transform"
              >
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10zm-1-15v10l7-5-7-5z"
                  />
                </svg>
              </a>
            </div>
          </div>
        </div>

        <!-- Copyright -->
        <PorneexWidget />
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import PorneexWidget from "@/Components/PorneexWidget.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  post: {
    type: Object,
    default: null,
  },
  breadcrumbs: {
    type: Array,
    default: () => [],
  },
  faq: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const mobileMenuOpen = ref(false);
const isDark = ref(false);

// ✅ Dark Mode Management
const toggleDark = () => {
  isDark.value = !isDark.value;
  localStorage.setItem("theme", isDark.value ? "dark" : "light");
  document.documentElement.classList.toggle("dark", isDark.value);
};

onMounted(() => {
  const savedTheme = localStorage.getItem("theme");
  if (
    savedTheme === "dark" ||
    (!savedTheme && window.matchMedia("(prefers-color-scheme: dark)").matches)
  ) {
    isDark.value = true;
    document.documentElement.classList.add("dark");
  }
});

// ✅ SEO Meta
const seoTitle = computed(() => {
  if (props.post?.meta_title) return props.post.meta_title;
  if (props.post?.title)
    return `${props.post.title} | ${props.doctor.name} ${props.doctor.family}`;
  return `بلاگ پزشکی |${post.doctor.caption} ${props.doctor.name} ${props.doctor.family}`;
});

const seoDescription = computed(() => {
  if (props.post?.meta_description) return props.post.meta_description;
  if (props.post?.excerpt) return props.post.excerpt;
  return `مقالات تخصصی پزشکی توسط ${props.doctor.caption} ${props.doctor.name} ${props.doctor.family} - ${props.doctor.specialty_title}`;
});

const seoKeywords = computed(() => {
  if (props.post?.keywords?.length) {
    return props.post.keywords.join(", ");
  }
  const defaultKeywords = [
    props.doctor.specialty_title,
    `${post.doctor.caption} ${props.doctor.name} ${props.doctor.family}`,
    "مقالات پزشکی",
    "سلامت",
    props.doctor.city?.name,
  ].filter(Boolean);
  return defaultKeywords.join(", ");
});

const ogImage = computed(() => {
  if (props.post?.featured_image) {
    return props.post.featured_image.startsWith("http")
      ? props.post.featured_image
      : window.location.origin + "/storage/" + props.post.featured_image;
  }
  return props.doctor.photo
    ? window.location.origin + props.doctor.photo
    : window.location.origin + "/images/default-og.jpg";
});

const currentUrl = computed(() => window.location.href);
const canonicalUrl = computed(() => props.post?.canonical_url || currentUrl.value);
const currentYear = computed(() => new Date().getFullYear());

// ✅ Schema.org Article
const articleSchema = computed(() => {
  if (!props.post) return "";

  const schema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: props.post.title,
    description: props.post.excerpt,
    image: ogImage.value,
    author: {
      "@type": "Person",
      name: `${props.doctor.caption} ${props.doctor.name} ${props.doctor.family}`,
      jobTitle: props.doctor.specialty_title,
      url: `${window.location.origin}/doctors/${props.doctor.slug}`,
    },
    publisher: {
      "@type": "Person",
      name: `${props.doctor.caption} ${props.doctor.name} ${props.doctor.family}`,
      logo: {
        "@type": "ImageObject",
        url: ogImage.value,
      },
    },
    datePublished: props.post.published_at,
    dateModified: props.post.updated_at,
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": currentUrl.value,
    },
    keywords: props.post.keywords?.join(", ") || props.doctor.specialty_title,
  };

  return JSON.stringify(schema);
});

// ✅ Schema.org BreadcrumbList
const breadcrumbSchema = computed(() => {
  const items = [
    {
      "@type": "ListItem",
      position: 1,
      name: "خانه",
      item: window.location.origin,
    },
    {
      "@type": "ListItem",
      position: 2,
      name: "بلاگ",
      item: `${window.location.origin}/blog`,
    },
    ...props.breadcrumbs.map((crumb, index) => ({
      "@type": "ListItem",
      position: index + 3,
      name: crumb.name,
      item: crumb.url,
    })),
  ];

  return JSON.stringify({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items,
  });
});

// ✅ FAQ Schema
const faqSchema = computed(() => {
  if (!props.faq?.length) return null;

  const schema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: props.faq.map((item) => ({
      "@type": "Question",
      name: item.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: item.answer,
      },
    })),
  };

  return JSON.stringify(schema);
});
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

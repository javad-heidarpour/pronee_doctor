<!-- resources/js/Pages/Blog/Show.vue -->

<template>
  <Head>
    <title>
      {{ post.title }} | دکتر {{ post.doctor?.name }}
      {{ post.doctor?.family }}
    </title>

    <meta name="description" :content="post.excerpt" />
    <meta name="keywords" :content="keywords" />
  </Head>

  <div class="min-h-screen bg-slate-50">
    <!-- Reading Progress -->

    <div class="fixed top-0 left-0 right-0 z-[999] h-1 bg-transparent">
      <div
        class="h-full bg-gradient-to-r from-teal-500 via-cyan-500 to-indigo-500 transition-all duration-150"
        :style="{ width: scrollProgress + '%' }"
      />
    </div>

    <!-- Hero -->
    <!-- Hero -->
    <Hero :post="post" :STORAGE="STORAGE" :doctorAvatar="doctorAvatar" />

    <!-- Content -->

    <div class="max-w-7xl mx-auto grid lg:grid-cols-[280px_1fr] gap-12 px-6 py-16">
      <!-- Sidebar -->

      <aside class="hidden lg:block">
        <div class="sticky top-10 space-y-6">
          <div v-if="headings.length" class="bg-white rounded-3xl shadow-sm p-6">
            <div class="font-black mb-6">فهرست مطالب</div>

            <nav class="space-y-3">
              <a
                v-for="heading in headings"
                :key="heading.id"
                :href="'#' + heading.id"
                class="block border-r-4 pr-4 transition"
                :class="[
                  activeHeading === heading.id
                    ? 'border-teal-600 text-teal-600 font-black'
                    : 'border-transparent text-gray-500 hover:text-black',
                ]"
              >
                {{ heading.text }}
              </a>
            </nav>
          </div>

          <div class="bg-white rounded-3xl shadow-sm p-6">
            <div class="font-black mb-5">اشتراک گذاری</div>

            <div class="grid grid-cols-2 gap-3">
              <button
                @click="shareTelegram"
                class="rounded-xl py-3 bg-slate-100 hover:bg-teal-500 hover:text-white transition"
              >
                Telegram
              </button>

              <button
                @click="shareLinkedin"
                class="rounded-xl py-3 bg-slate-100 hover:bg-teal-500 hover:text-white transition"
              >
                Linkedin
              </button>

              <button
                @click="shareTwitter"
                class="rounded-xl py-3 bg-slate-100 hover:bg-teal-500 hover:text-white transition"
              >
                X
              </button>

              <button
                @click="copyLink"
                class="rounded-xl py-3 bg-slate-100 hover:bg-teal-500 hover:text-white transition"
              >
                Copy
              </button>
            </div>
          </div>

          <div
            class="bg-gradient-to-br from-teal-600 to-cyan-600 rounded-3xl p-8 text-white"
          >
            <div class="text-2xl font-black">سوال پزشکی دارید؟</div>

            <p class="mt-4 leading-8 text-white/90">
              برای دریافت مشاوره و رزرو نوبت از طریق پرونیکس اقدام کنید.
            </p>

            <Link
              :href="route('booking')"
              class="mt-8 inline-flex bg-white text-teal-700 rounded-xl px-6 py-3 font-black"
            >
              رزرو نوبت
            </Link>
          </div>
        </div>
      </aside>

      <!-- Article -->

      <article>
        <div class="bg-white rounded-[40px] shadow-sm p-8 md:p-12">
          <div
            class="mb-10 border-r-4 border-amber-500 bg-amber-50 rounded-2xl p-6 leading-9"
          >
            <div class="font-black text-amber-700 mb-3">هشدار پزشکی</div>

            <div>
              مطالب این مقاله صرفاً جهت افزایش آگاهی است و جایگزین ویزیت پزشک نیست.
            </div>
          </div>

          <div
            class="post-content prose prose-lg max-w-none prose-img:rounded-3xl prose-img:shadow-xl prose-p:leading-9 prose-headings:font-black"
            v-html="post.content"
          />

          <div v-if="post.tags?.length" class="mt-14 pt-10 border-t">
            <div class="font-black mb-5">برچسب ها</div>

            <div class="flex flex-wrap gap-3">
              <Link
                v-for="tag in post.tags"
                :key="tag.id"
                :href="`/${post.doctor.slug}/tag/${tag.slug}`"
                class="rounded-full px-5 py-3 bg-slate-100 hover:bg-teal-600 hover:text-white transition"
              >
                #{{ tag.name }}
              </Link>
            </div>
          </div>
          <!-- Doctor Card -->

          <div
            class="mt-16 rounded-[36px] overflow-hidden bg-gradient-to-br from-teal-600 via-cyan-600 to-sky-700 text-white"
          >
            <div class="grid lg:grid-cols-[220px_1fr]">
              <div class="flex items-center justify-center p-10">
                <img
                  :src="doctorAvatar"
                  class="w-40 h-40 rounded-full border-4 border-white object-cover shadow-2xl"
                />
              </div>

              <div class="p-10">
                <div class="text-sm opacity-80">نویسنده مقاله</div>

                <h2 class="text-3xl font-black mt-2">
                  {{ post.doctor?.caption }}

                  {{ post.doctor?.name }}

                  {{ post.doctor?.family }}
                </h2>

                <div class="mt-3 opacity-90">
                  {{ post.doctor?.speciality }}
                </div>

                <p class="mt-8 leading-9 text-white/90">
                  {{ post.doctor?.bio }}
                </p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mt-10">
                  <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                    <div class="text-3xl font-black">
                      {{ doctorPosts.length }}
                    </div>

                    <div class="text-sm mt-2">مقاله</div>
                  </div>

                  <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                    <div class="text-3xl font-black">
                      {{ commentsStats.count }}
                    </div>

                    <div class="text-sm mt-2">نظر</div>
                  </div>

                  <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                    <div class="text-3xl font-black">
                      {{ post.average_rating || 5 }}
                    </div>

                    <div class="text-sm mt-2">امتیاز</div>
                  </div>

                  <div class="bg-white/10 backdrop-blur rounded-2xl p-5 text-center">
                    <div class="text-3xl font-black">
                      {{ post.views_count }}
                    </div>

                    <div class="text-sm mt-2">بازدید</div>
                  </div>
                </div>

                <div class="flex flex-wrap gap-4 mt-10">
                  <Link
              :href="route('booking')"

                    class="px-8 py-4 rounded-2xl bg-white text-teal-700 font-black hover:scale-105 transition"
                  >
                    رزرو نوبت
                  </Link>

                  <Link
                    :href="`/${post.doctor.slug}/blog`"
                    class="px-8 py-4 rounded-2xl border border-white text-white font-black hover:bg-white hover:text-teal-700 transition"
                  >
                    همه مقالات پزشک
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Rating -->

          <div class="mt-16 bg-white border rounded-[32px] p-10">
            <div class="text-center">
              <div class="text-3xl font-black">این مقاله برای شما مفید بود؟</div>

              <div class="mt-8 flex justify-center gap-3">
                <button v-for="i in 5" :key="i" class="transition hover:scale-125">
                  <Star class="w-10 h-10 text-yellow-400 fill-yellow-400" />
                </button>
              </div>
            </div>
          </div>

          <!-- Comments -->

          <section class="mt-20">
            <CommentSection
              :post="post"
              :comments="post.approved_comments || []"
              :stats="commentsStats"
            />
          </section>

          <!-- Related -->

          <section v-if="doctorPosts.length" class="mt-24">
            <div class="flex items-center justify-between mb-10">
              <h2 class="text-4xl font-black">مقالات بیشتر</h2>

              <Link :href="`/${post.doctor.slug}/blog`" class="text-teal-600 font-black">
                مشاهده همه
              </Link>
            </div>

            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-8">
              <Link
                v-for="article in doctorPosts"
                :key="article.id"
                :href="`/${post.doctor.slug}/blog/${article.slug}`"
                class="group overflow-hidden rounded-[32px] bg-white shadow-sm hover:shadow-2xl transition duration-500 hover:-translate-y-2"
              >
                <div class="overflow-hidden h-56">
                  <img
                    :src="article.featured_image || DEFAULT_POST_IMAGE"
                    class="w-full h-full object-cover group-hover:scale-110 transition duration-700"
                  />
                </div>

                <div class="p-7">
                  <h3 class="font-black text-xl line-clamp-2">
                    {{ article.title }}
                  </h3>

                  <p class="mt-5 text-gray-500 line-clamp-3 leading-8">
                    {{ article.excerpt }}
                  </p>

                  <div class="flex justify-between mt-8 text-sm text-gray-400">
                    <span> 👁 {{ article.views_count }} </span>

                    <span>
                      🕒 {{ article.reading_time }}

                      دقیقه
                    </span>
                  </div>
                </div>
              </Link>
            </div>
          </section>
          <!-- Bottom CTA -->

          <section class="mt-24">
            <div
              class="relative overflow-hidden rounded-[40px] bg-gradient-to-br from-teal-700 via-cyan-700 to-sky-800 text-white"
            >
              <div
                class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-white/10 blur-3xl"
              />

              <div
                class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full bg-cyan-300/10 blur-3xl"
              />

              <div class="relative px-12 py-16 text-center">
                <div class="text-4xl font-black">هنوز سوالی دارید؟</div>

                <p class="mt-8 text-lg leading-9 text-white/90 max-w-3xl mx-auto">
                  برای دریافت مشاوره تخصصی، رزرو نوبت آنلاین و ارتباط مستقیم با پزشک از
                  طریق پرونیکس اقدام نمایید.
                </p>

                <div class="flex flex-wrap justify-center gap-5 mt-12">
                  <Link
              :href="route('booking')"

                    class="px-10 py-5 rounded-2xl bg-white text-teal-700 font-black hover:scale-105 transition"
                  >
                    رزرو نوبت آنلاین
                  </Link>

                  <a
                    :href="`tel:${post.doctor?.phone}`"
                    class="px-10 py-5 rounded-2xl border border-white text-white font-black hover:bg-white hover:text-teal-700 transition"
                  >
                    تماس با پزشک
                  </a>
                </div>
              </div>
            </div>
          </section>
        </div>
      </article>
    </div>

    <BlogFooter :STORAGE="STORAGE" :post="post" />

    <PorneexWidget />

    <Transition name="fade">
      <button
        v-if="showBackToTop"
        @click="scrollToTop"
        class="fixed bottom-8 left-8 z-50 w-14 h-14 rounded-full bg-teal-600 text-white shadow-2xl hover:scale-110 transition flex items-center justify-center"
      >
        <ChevronUp class="w-7 h-7" />
      </button>
    </Transition>
  </div>

  <div class="h-24" />
</template>

<script setup>
import Hero from "./Hero.vue";
import { computed, ref, onMounted, onUnmounted, nextTick } from "vue";

import { Head, Link } from "@inertiajs/vue3";

import { Eye, Star, Clock3, CalendarDays, ChevronUp } from "lucide-vue-next";

import BlogFooter from "@/Components/Blog/BlogFooter.vue";

import CommentSection from "@/Components/Blog/Comments/CommentSection.vue";

import PorneexWidget from "@/Components/PorneexWidget.vue";

const STORAGE = "/storage/";

const DEFAULT_POST_IMAGE = "/storage/doctor-gallery/defaultImage.jpg";

const DEFAULT_AVATAR = "/storage/doctors/default-avatar.jpg";

const props = defineProps({
  post: Object,

  doctorPosts: {
    type: Array,
    default: () => [],
  },

  commentsStats: {
    type: Object,
    default: () => ({
      average: 0,
      count: 0,
    }),
  },
});

const scrollProgress = ref(0);

const showBackToTop = ref(false);

const headings = ref([]);

const activeHeading = ref("");

const keywords = computed(() => {
  if (!props.post.tags) return "";

  return props.post.tags.map((i) => i.name).join(",");
});

const doctorAvatar = computed(() => {
  const avatar = props.post.doctor?.photo;

  if (!avatar) return DEFAULT_AVATAR;

  if (avatar.startsWith("http") || avatar.startsWith("/")) return avatar;

  return STORAGE + "doctors/" + avatar;
});

const updateReadingProgress = () => {
  const scrollTop = document.documentElement.scrollTop;

  const height =
    document.documentElement.scrollHeight - document.documentElement.clientHeight;

  scrollProgress.value = (scrollTop / height) * 100;

  showBackToTop.value = scrollTop > 500;
};

const extractHeadings = () => {
  const article = document.querySelector(".post-content");

  if (!article) return;

  const nodes = article.querySelectorAll("h2,h3");

  headings.value = [];

  nodes.forEach((el, index) => {
    const id = `heading-${index}`;

    el.id = id;

    headings.value.push({
      id,

      text: el.innerText,

      level: el.tagName,
    });
  });
};

const scrollSpy = () => {
  for (const heading of headings.value) {
    const element = document.getElementById(heading.id);

    if (!element) continue;

    const top = element.getBoundingClientRect().top;

    if (top >= 0 && top <= 180) {
      activeHeading.value = heading.id;

      break;
    }
  }
};

const handleScroll = () => {
  updateReadingProgress();

  scrollSpy();
};

const scrollToTop = () => {
  window.scrollTo({
    top: 0,

    behavior: "smooth",
  });
};

const shareUrl = computed(() => window.location.href);

const shareTelegram = () => {
  window.open(
    `https://t.me/share/url?url=${encodeURIComponent(
      shareUrl.value
    )}&text=${encodeURIComponent(props.post.title)}`,

    "_blank"
  );
};

const shareLinkedin = () => {
  window.open(
    `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(
      shareUrl.value
    )}`,

    "_blank"
  );
};

const shareTwitter = () => {
  window.open(
    `https://twitter.com/intent/tweet?text=${encodeURIComponent(
      props.post.title
    )}&url=${encodeURIComponent(shareUrl.value)}`,

    "_blank"
  );
};

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(shareUrl.value);
  } catch (e) {}
};

onMounted(async () => {
  await nextTick();

  extractHeadings();

  updateReadingProgress();

  window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<style scoped>
.post-content :deep(img) {
  border-radius: 28px;

  margin: 2rem auto;

  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.post-content :deep(h2) {
  font-size: 2rem;

  margin-top: 3rem;

  margin-bottom: 1.25rem;

  font-weight: 900;

  color: #0f172a;
}

.post-content :deep(h3) {
  font-size: 1.5rem;

  margin-top: 2rem;

  margin-bottom: 1rem;

  font-weight: 800;

  color: #1e293b;
}

.post-content :deep(p) {
  line-height: 2.3;

  color: #334155;

  margin: 1.2rem 0;
}

.post-content :deep(blockquote) {
  border-right: 6px solid #14b8a6;

  background: #ecfeff;

  padding: 1.5rem;

  border-radius: 1rem;

  margin: 2rem 0;
}

.post-content :deep(table) {
  width: 100%;

  overflow: hidden;

  border-radius: 20px;
}

.post-content :deep(th) {
  background: #0f766e;

  color: #fff;
}

.post-content :deep(th),
.post-content :deep(td) {
  padding: 1rem;

  border: 1px solid #e5e7eb;
}

.fade-enter-active,
.fade-leave-active {
  transition: 0.25s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;

  transform: translateY(20px);
}
</style>

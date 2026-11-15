<script setup>
import { computed, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const themeColor = ref("sage");

const themes = {
  sage: {
    primary: "#789681",
    deep: "#29483D",
    soft: "#E4EBE2",
    pale: "#F0F3EC",
    accent: "#D5A58C",
  },
  olive: {
    primary: "#8B9165",
    deep: "#454B2E",
    soft: "#E8E9D9",
    pale: "#F3F1E7",
    accent: "#D2B28B",
  },
  teal: {
    primary: "#60938D",
    deep: "#254C49",
    soft: "#DCEBE7",
    pale: "#EDF4F0",
    accent: "#D7A88F",
  },
};

const palette = computed(() => themes[themeColor.value]);

const cssVars = computed(() => ({
  "--sage": palette.value.primary,
  "--forest": palette.value.deep,
  "--sage-soft": palette.value.soft,
  "--sage-pale": palette.value.pale,
  "--clay": palette.value.accent,
}));

const mobileMenu = ref(false);
const activeQuestion = ref(null);

const doctor = {
  name: "دکتر فرهاد وفایی",
  specialty: "متخصص جراحی",
  description:
    "مراقبت پزشکی، با نگاهی دقیق‌تر و تجربه‌ای آرام‌تر؛ از اولین پرسش تا پیگیری درمان.",
  image: "/storage/doctors/Dr.Farhad.Vafaei.jpg",
};

const services = [
  {
    number: "۰۱",
    title: "مشاوره و معاینه",
    description: "ارزیابی دقیق شرایط و انتخاب مسیر مناسب درمان",
    icon: "◌",
  },
  {
    number: "۰۲",
    title: "پیگیری درمان",
    description: "همراهی در مسیر درمان و بررسی روند بهبودی",
    icon: "↗",
  },
  {
    number: "۰۳",
    title: "پاسخ به پرسش‌ها",
    description: "دریافت راهنمایی برای تصمیم‌گیری آگاهانه‌تر",
    icon: "⌁",
  },
];

const testimonials = [
  {
    quote: "از توضیحات دقیق و برخورد آرام پزشک رضایت داشتم. روند مراجعه برایم روشن بود.",
    name: "مراجعه‌کننده",
    detail: "نظر ثبت‌شده",
  },
  {
    quote: "نوبت‌گیری ساده بود و اطلاعات لازم را به‌راحتی پیدا کردم.",
    name: "مراجعه‌کننده",
    detail: "نظر ثبت‌شده",
  },
  {
    quote: "در طول ویزیت، فرصت کافی برای مطرح کردن پرسش‌هایم داشتم.",
    name: "مراجعه‌کننده",
    detail: "نظر ثبت‌شده",
  },
];

const articles = [
  {
    category: "سلامت و پیشگیری",
    title: "چه زمانی برای یک مشکل پزشکی باید مراجعه کنیم؟",
    date: "راهنمای سلامت",
    image:
      "https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=800&q=85",
  },
  {
    category: "آمادگی برای ویزیت",
    title: "چطور برای مراجعه پزشکی آماده شویم؟",
    date: "نکات کاربردی",
    image:
      "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=85",
  },
];

const faqs = [
  {
    question: "چطور می‌توانم نوبت دریافت کنم؟",
    answer: "از دکمه «دریافت نوبت» استفاده کنید و مراحل رزرو را در سامانه دنبال کنید.",
  },
  {
    question: "برای مراجعه چه اطلاعاتی لازم است؟",
    answer:
      "اطلاعات موردنیاز در زمان رزرو نمایش داده می‌شود. در صورت لزوم، مدارک پزشکی قبلی خود را نیز همراه داشته باشید.",
  },
];

const bookingUrl = "/#booking";
const appointmentUrl = "/appointment";
</script>

<template>
  <Head title="دکتر فرهاد وفایی | مراقبت پزشکی با آرامش بیشتر" />

  <div dir="rtl" class="site-shell" :style="cssVars">
    <!-- Header -->
    <header class="site-header">
      <a href="#home" class="brand" aria-label="صفحه نخست">
        <span class="brand-symbol">
          <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
            <path
              d="M24 5C12 13 9 24 15 32C19 37 27 38 33 32C40 24 34 12 24 5Z"
              stroke="currentColor"
              stroke-width="1.5"
            />
            <path
              d="M13 38C21 29 25 22 29 12"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
            />
            <path
              d="M20 27C15 25 12 22 11 18M25 20C30 21 33 20 36 17"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
            />
          </svg>
        </span>

        <span class="brand-copy">
          <strong>دکتر فرهاد وفایی</strong>
          <small>مراقبت، آگاهی، آرامش</small>
        </span>
      </a>

      <nav class="desktop-nav">
        <a href="#about">درباره پزشک</a>
        <a href="#services">خدمات</a>
        <a href="#articles">مجله سلامت</a>
        <a href="#location">تماس و نشانی</a>
      </nav>

      <div class="header-actions">
        <button
          class="theme-button"
          type="button"
          aria-label="تغییر رنگ سایت"
          title="تغییر رنگ سایت"
          @click="
            themeColor =
              themeColor === 'sage' ? 'olive' : themeColor === 'olive' ? 'teal' : 'sage'
          "
        >
          <span class="theme-dot"></span>
          <span>رنگ‌بندی</span>
        </button>

        <a :href="appointmentUrl" class="header-booking">
          دریافت نوبت
          <span>↗</span>
        </a>

        <button
          class="menu-toggle"
          type="button"
          :aria-expanded="mobileMenu"
          aria-label="باز کردن منو"
          @click="mobileMenu = !mobileMenu"
        >
          <span></span>
          <span></span>
        </button>
      </div>
    </header>

    <nav v-if="mobileMenu" class="mobile-nav">
      <a href="#about" @click="mobileMenu = false">درباره پزشک</a>
      <a href="#services" @click="mobileMenu = false">خدمات</a>
      <a href="#articles" @click="mobileMenu = false">مجله سلامت</a>
      <a href="#location" @click="mobileMenu = false">تماس و نشانی</a>
      <a :href="appointmentUrl" class="mobile-booking">دریافت نوبت ↗</a>
    </nav>

    <main>
      <!-- Hero -->
      <section id="home" class="hero">
        <div class="hero-background-shape"></div>
        <div class="hero-grain"></div>

        <div class="hero-copy">
          <div class="eyebrow">
            <span class="eyebrow-line"></span>
            مراقبت پزشکی، انسانی‌تر
          </div>

          <h1>
            سلامت شما،
            <br />
            <span class="heading-highlight">در مرکز توجه.</span>
          </h1>

          <p class="hero-description">
            مسیر درمان لازم نیست پیچیده باشد. اطلاعات روشن، ارتباط مؤثر و مراقبتی که با
            شما آغاز می‌شود.
          </p>

          <div class="hero-actions">
            <a :href="appointmentUrl" class="primary-button">
              <span>همین حالا نوبت بگیرید</span>
              <span class="button-arrow">↗</span>
            </a>

            <a href="#about" class="text-link">
              آشنایی با پزشک
              <span>←</span>
            </a>
          </div>

          <div class="hero-note">
            <span class="note-icon">✳</span>
            <span>یک قدم ساده برای رسیدگی بهتر به سلامت شما</span>
          </div>
        </div>

        <div class="hero-visual">
          <div class="portrait-backdrop"></div>
          <div class="portrait-ring"></div>

          <div class="portrait-frame">
            <img :src="doctor.image" :alt="doctor.name" fetchpriority="high" />
          </div>

          <div class="portrait-label">
            <span class="label-overline">پزشک شما</span>
            <strong>{{ doctor.name }}</strong>
            <span>{{ doctor.specialty }}</span>
          </div>

          <div class="floating-leaf leaf-one">✳</div>
          <div class="floating-leaf leaf-two">✦</div>

          <div class="organic-stamp">
            <svg viewBox="0 0 100 100" aria-hidden="true">
              <defs>
                <path
                  id="stamp-circle"
                  d="M50,50 m-35,0 a35,35 0 1,1 70,0 a35,35 0 1,1 -70,0"
                />
              </defs>
              <text>
                <textPath href="#stamp-circle">CARE • TRUST • WELLNESS •</textPath>
              </text>
            </svg>
            <span>+</span>
          </div>

          <div class="hero-index">
            <span>01</span>
            <span class="index-line"></span>
            <span>04</span>
          </div>
        </div>

        <div class="hero-bottom">
          <span class="scroll-label">کمی پایین‌تر را ببینید</span>
          <a href="#booking" class="scroll-indicator" aria-label="رفتن به بخش نوبت">
            ↓
          </a>
          <span class="hero-bottom-caption">سلامت، سفری پیوسته است.</span>
        </div>
      </section>

      <!-- Booking path -->
      <section id="booking" class="booking-strip">
        <div class="booking-intro">
          <span class="booking-number">01 / شروع مسیر</span>
          <h2>برای شروع آماده‌اید؟</h2>
          <p>دریافت نوبت را بدون سردرگمی آغاز کنید.</p>
        </div>

        <a :href="appointmentUrl" class="booking-main-action">
          <span class="booking-action-icon">↗</span>
          <span>
            <strong>دریافت نوبت اینترنتی</strong>
            <small>مشاهده مراحل رزرو و انتخاب زمان</small>
          </span>
          <span class="booking-action-arrow">←</span>
        </a>

        <div class="booking-steps">
          <div class="booking-step">
            <span>۱</span>
            <small>انتخاب زمان</small>
          </div>
          <div class="step-connector"></div>
          <div class="booking-step">
            <span>۲</span>
            <small>ثبت اطلاعات</small>
          </div>
          <div class="step-connector"></div>
          <div class="booking-step">
            <span>۳</span>
            <small>تأیید نوبت</small>
          </div>
        </div>
      </section>

      <!-- About -->
      <section id="about" class="about-section section-pad">
        <div class="section-aside">
          <span class="section-number">02 — درباره پزشک</span>
          <span class="vertical-word">ABOUT</span>
        </div>

        <div class="about-content">
          <div class="about-heading">
            <span class="mini-label">رویکرد ما</span>
            <h2>
              پزشکی فقط
              <span>درمان نیست.</span>
            </h2>
          </div>

          <div class="about-body">
            <p class="about-lead">
              شنیدن، توضیح دادن و تصمیم‌گیری آگاهانه، بخشی از تجربه خوب مراقبت پزشکی است.
            </p>

            <p>
              هدف این وب‌سایت آن است که پیش از مراجعه، اطلاعات موردنیاز را ساده‌تر پیدا
              کنید و برای دریافت نوبت، مسیر مشخصی پیش روی شما باشد.
            </p>

            <a href="#services" class="underlined-link">
              آشنایی با خدمات
              <span>↙</span>
            </a>
          </div>

          <div class="about-decoration">
            <svg viewBox="0 0 220 180" fill="none" aria-hidden="true">
              <path
                d="M12 145C45 111 58 67 104 49C143 34 174 53 207 13"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
              />
              <path
                d="M44 162C69 123 96 102 129 99C164 95 185 73 207 44"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
              />
              <path
                d="M101 51C95 31 103 15 121 10C130 32 124 44 101 51Z"
                fill="currentColor"
                opacity=".25"
              />
              <path
                d="M144 92C146 70 160 59 181 64C176 84 162 94 144 92Z"
                fill="currentColor"
                opacity=".25"
              />
            </svg>
          </div>
        </div>
      </section>

      <!-- Services -->
      <section id="services" class="services-section">
        <div class="services-top">
          <div>
            <span class="section-number">03 — مسیر مراقبت</span>
            <h2>هر مراجعه، با یک <em>پرسش</em> آغاز می‌شود.</h2>
          </div>
          <p>برای پیدا کردن مسیر مناسب، اطلاعات روشن و ارتباط مستقیم اهمیت دارند.</p>
        </div>

        <div class="services-list">
          <a
            v-for="service in services"
            :key="service.number"
            :href="appointmentUrl"
            class="service-row"
          >
            <span class="service-number">{{ service.number }}</span>
            <span class="service-icon">{{ service.icon }}</span>
            <span class="service-content">
              <strong>{{ service.title }}</strong>
              <small>{{ service.description }}</small>
            </span>
            <span class="service-arrow">↙</span>
          </a>
        </div>

        <div class="services-footer">
          <span>برای انتخاب نوع مراجعه، از سامانه نوبت‌گیری استفاده کنید.</span>
          <a :href="appointmentUrl">شروع رزرو <span>←</span></a>
        </div>
      </section>

      <!-- Visual pause -->
      <section class="manifesto-section">
        <div class="manifesto-shape"></div>
        <div class="manifesto-content">
          <span class="manifesto-star">✳</span>
          <p class="manifesto-overline">A DIFFERENT KIND OF CARE</p>
          <h2>
            آرامش از جایی آغاز می‌شود
            <br />
            که <span>همه‌چیز روشن است.</span>
          </h2>
          <a href="#booking" class="manifesto-link">
            قدم اول را بردارید
            <span>↗</span>
          </a>
        </div>
        <div class="manifesto-side-text">WELLNESS / 2026</div>
      </section>

      <!-- Testimonials -->
      <section class="testimonials-section section-pad">
        <div class="testimonials-heading">
          <span class="section-number">04 — تجربه مراجعه</span>
          <h2>تجربه‌ای که <em>به یاد می‌ماند.</em></h2>
          <p>
            بازخورد مراجعه‌کنندگان می‌تواند به دیگران برای آشنایی بهتر با روند مراجعه کمک
            کند.
          </p>
        </div>

        <div class="testimonial-layout">
          <div class="testimonial-feature">
            <span class="quote-mark">“</span>
            <p>تجربه خوب درمان، از ارتباط روشن و توجه به پرسش‌های بیمار آغاز می‌شود.</p>
            <div class="quote-footer">
              <span class="quote-line"></span>
              <span>اهمیت ارتباط در مراقبت پزشکی</span>
            </div>
          </div>

          <div class="testimonial-list">
            <article
              v-for="(review, index) in testimonials"
              :key="index"
              class="testimonial-item"
            >
              <div class="review-stars" aria-label="بازخورد مراجعه‌کننده">
                <span>✳</span>
                <span>✳</span>
                <span>✳</span>
              </div>
              <p>{{ review.quote }}</p>
              <div class="review-person">
                <span class="review-avatar">{{ index  }}</span>
                <span>
                  <strong>{{ review.name }}</strong>
                  <small>{{ review.detail }}</small>
                </span>
              </div>
            </article>
          </div>
        </div>
      </section>

      <!-- Clinic hours and location -->
      <section id="location" class="visit-section">
        <div class="visit-info">
          <span class="section-number">05 — برنامه مراجعه</span>
          <h2>برای مراجعه، <em>برنامه‌ریزی کنید.</em></h2>
          <p>
            زمان‌های مراجعه، نشانی مطب و اطلاعات تماس را از بخش نوبت‌گیری و اطلاعات رسمی
            مطب بررسی کنید.
          </p>

          <div class="visit-detail">
            <span class="detail-symbol">◷</span>
            <span>
              <strong>ساعات کاری</strong>
              <small>زمان‌های در دسترس در سامانه نوبت‌گیری</small>
            </span>
          </div>

          <div class="visit-detail">
            <span class="detail-symbol">⌖</span>
            <span>
              <strong>نشانی مطب</strong>
              <small>نشانی و راهنمای مراجعه را بررسی کنید.</small>
            </span>
          </div>

          <a :href="appointmentUrl" class="primary-button visit-button">
            بررسی زمان‌های نوبت
            <span class="button-arrow">↗</span>
          </a>
        </div>

        <div class="visit-art">
          <div class="map-abstract">
            <svg viewBox="0 0 500 560" preserveAspectRatio="xMidYMid slice">
              <g fill="none" stroke="#9AAA9B" stroke-width="1.2">
                <path d="M-20 70L150 150L310 60L530 180" />
                <path d="M-30 130L170 220L340 120L530 240" />
                <path d="M-20 200L130 290L300 200L530 310" />
                <path d="M-20 280L150 370L340 290L530 390" />
                <path d="M-20 360L130 450L310 370L530 470" />
                <path d="M50 -30L110 150L60 330L170 590" />
                <path d="M150 -30L210 140L170 320L270 590" />
                <path d="M280 -30L330 160L290 340L390 590" />
                <path d="M410 -30L450 160L410 340L500 590" />
              </g>
              <g fill="none" stroke="#D1A78E" stroke-width="3" stroke-linecap="round">
                <path d="M0 430C120 400 140 310 240 290S360 220 410 120" />
              </g>
              <circle cx="240" cy="290" r="35" fill="#789681" opacity=".14" />
              <circle cx="240" cy="290" r="19" fill="#789681" opacity=".28" />
              <circle cx="240" cy="290" r="8" fill="#29483D" />
            </svg>
          </div>

          <div class="map-caption">
            <span class="map-caption-icon">⌖</span>
            <div>
              <strong>پیش از حرکت، بررسی کنید</strong>
              <small>نشانی و زمان مراجعه را تأیید کنید.</small>
            </div>
          </div>

          <div class="map-side-label">YOUR VISIT / MADE SIMPLE</div>
        </div>
      </section>

      <!-- FAQ -->
      <section class="faq-section section-pad">
        <div class="faq-intro">
          <span class="section-number">پیش از مراجعه</span>
          <h2>پرسش‌های <em>متداول</em></h2>
          <p>چند پاسخ کوتاه برای شروع راحت‌تر.</p>
        </div>

        <div class="faq-list">
          <article
            v-for="(faq, index) in faqs"
            :key="faq.question"
            class="faq-item"
            :class="{ 'faq-open': activeQuestion === index }"
          >
            <button
              type="button"
              class="faq-question"
              :aria-expanded="activeQuestion === index"
              @click="activeQuestion = activeQuestion === index ? null : index"
            >
              <span>{{ faq.question }}</span>
              <span class="faq-toggle">
                {{ activeQuestion === index ? "−" : "+" }}
              </span>
            </button>
            <div v-if="activeQuestion === index" class="faq-answer">
              {{ faq.answer }}
            </div>
          </article>
        </div>
      </section>

      <!-- Articles -->
      <section id="articles" class="articles-section">
        <div class="articles-header">
          <div>
            <span class="section-number">06 — مجله سلامت</span>
            <h2>دانستن، بخشی از <em>مراقبت است.</em></h2>
          </div>
          <a href="/blog" class="all-articles">
            همه مطالب
            <span>↗</span>
          </a>
        </div>

        <div class="article-grid">
          <a
            v-for="(article, index) in articles"
            :key="article.title"
            href="/blog"
            class="article-item"
          >
            <div class="article-image" :class="{ 'article-image-tall': index === 0 }">
              <img :src="article.image" :alt="article.title" loading="lazy" />
              <span class="article-image-arrow">↗</span>
            </div>

            <div class="article-meta">
              <span>{{ article.category }}</span>
              <span>{{ article.date }}</span>
            </div>

            <h3>{{ article.title }}</h3>
            <span class="article-read">مطالعه مطلب <span>←</span></span>
          </a>
        </div>
      </section>

      <!-- Final CTA -->
      <section class="final-cta">
        <div class="cta-orbit orbit-one"></div>
        <div class="cta-orbit orbit-two"></div>
        <div class="cta-content">
          <span class="cta-kicker">YOUR HEALTH, YOUR NEXT STEP</span>
          <h2>
            برای سلامت خود،
            <br />
            <span>وقت بگذارید.</span>
          </h2>
          <p>
            مسیر دریافت نوبت را آغاز کنید و اطلاعات موردنیاز برای مراجعه را بررسی کنید.
          </p>
          <a :href="appointmentUrl" class="cta-button">
            دریافت نوبت اینترنتی
            <span>↗</span>
          </a>
        </div>
        <div class="cta-flower">✳</div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
      <div class="footer-top">
        <a href="#home" class="brand footer-brand">
          <span class="brand-symbol">
            <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
              <path
                d="M24 5C12 13 9 24 15 32C19 37 27 38 33 32C40 24 34 12 24 5Z"
                stroke="currentColor"
                stroke-width="1.5"
              />
              <path
                d="M13 38C21 29 25 22 29 12"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
              />
            </svg>
          </span>
          <span class="brand-copy">
            <strong>دکتر فرهاد وفایی</strong>
            <small>مراقبت، آگاهی، آرامش</small>
          </span>
        </a>

        <p class="footer-note">تجربه‌ای روشن‌تر برای مراقبت از سلامت.</p>

        <a href="#home" class="back-top">بازگشت به بالا ↑</a>
      </div>

      <div class="footer-bottom">
        <span>© {{ new Date().getFullYear() }} دکتر فرهاد وفایی</span>
        <span>طراحی‌شده برای تجربه‌ای ساده‌تر</span>
      </div>
    </footer>

    <!-- Persistent appointment button -->
    <a :href="appointmentUrl" class="floating-booking" aria-label="دریافت نوبت اینترنتی">
      <span class="floating-booking-orbit"></span>
      <span class="floating-booking-icon">↗</span>
      <span class="floating-booking-text">نوبت</span>
    </a>
  </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap");

:root {
  font-family: "Vazirmatn", Tahoma, sans-serif;
  font-synthesis: none;
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  scroll-behavior: smooth;
}

* {
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
  scroll-padding-top: 90px;
}

body {
  margin: 0;
  background: #f7f4ec;
}

button,
a {
  font: inherit;
}

a {
  color: inherit;
  text-decoration: none;
}

button {
  cursor: pointer;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid var(--sage, #789681);
  outline-offset: 5px;
}

.site-shell {
  --sage: #789681;
  --forest: #29483d;
  --sage-soft: #e4ebe2;
  --sage-pale: #f0f3ec;
  --clay: #d5a58c;

  overflow: clip;
  color: var(--forest);
  background: #f7f4ec;
  transition: background-color 0.35s ease;
}

.site-header {
  position: relative;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 88px;
  padding: 0 6.5%;
  border-bottom: 1px solid rgb(41 72 61 / 9%);
  background: #f7f4ec;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.brand-symbol {
  display: grid;
  width: 43px;
  height: 43px;
  place-items: center;
  color: var(--sage);
}

.brand-symbol svg {
  width: 100%;
  height: 100%;
}

.brand-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.brand-copy strong {
  font-size: 14px;
  font-weight: 700;
}

.brand-copy small {
  color: #879187;
  font-size: 10px;
  font-weight: 400;
}

.desktop-nav {
  display: flex;
  align-items: center;
  gap: clamp(18px, 3vw, 43px);
}

.desktop-nav a {
  position: relative;
  color: #56675c;
  font-size: 12px;
  transition: color 0.2s ease;
}

.desktop-nav a::after {
  position: absolute;
  right: 0;
  bottom: -8px;
  width: 0;
  height: 1px;
  background: var(--sage);
  content: "";
  transition: width 0.25s ease;
}

.desktop-nav a:hover {
  color: var(--forest);
}

.desktop-nav a:hover::after {
  width: 100%;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 17px;
}

.theme-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 0;
  border: 0;
  background: transparent;
  color: #67776b;
  font-size: 11px;
}

.theme-dot {
  width: 12px;
  height: 12px;
  border: 3px solid var(--sage);
  border-radius: 50%;
  background: var(--sage-soft);
}

.header-booking {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 12px 18px;
  border-radius: 3px;
  background: var(--forest);
  color: white;
  font-size: 11px;
  transition: background 0.25s ease, transform 0.25s ease;
}

.header-booking:hover {
  transform: translateY(-2px);
  background: var(--sage);
}

.header-booking span {
  font-size: 15px;
}

.menu-toggle,
.mobile-nav {
  display: none;
}

/* Hero */
.hero {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 0.9fr;
  align-items: center;
  min-height: 620px;
  padding: 58px 10% 85px;
  isolation: isolate;
}

.hero-background-shape {
  position: absolute;
  z-index: -2;
  top: -80px;
  right: 39%;
  width: 58%;
  height: 760px;
  border-radius: 45% 55% 59% 41% / 37% 43% 57% 63%;
  background: var(--sage-pale);
  transform: rotate(-7deg);
  transition: background 0.3s ease;
}

.hero-grain {
  position: absolute;
  z-index: -1;
  inset: 0;
  pointer-events: none;
  background-image: radial-gradient(rgb(41 72 61 / 9%) 0.55px, transparent 0.55px);
  background-size: 7px 7px;
  opacity: 0.13;
}

.hero-copy {
  position: relative;
  z-index: 2;
  max-width: 510px;
  padding: 25px 0 20px;
}

.eyebrow {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
  color: var(--sage);
  font-size: 12px;
  font-weight: 600;
}

.eyebrow-line {
  width: 32px;
  height: 1px;
  background: var(--sage);
}

.hero h1 {
  margin: 0;
  font-size: clamp(42px, 5vw, 72px);
  font-weight: 500;
  line-height: 1.48;
  letter-spacing: -2.8px;
}

.heading-highlight {
  position: relative;
  z-index: 0;
  color: var(--sage);
}

.heading-highlight::after {
  position: absolute;
  z-index: -1;
  right: 0;
  bottom: 6px;
  left: 0;
  height: 13px;
  border-radius: 50%;
  background: var(--sage-soft);
  content: "";
  transform: rotate(-1.5deg);
}

.hero-description {
  max-width: 390px;
  margin: 22px 0 28px;
  color: #6e786f;
  font-size: 14px;
  font-weight: 300;
  line-height: 2.2;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 25px;
}

.primary-button {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  min-height: 54px;
  padding: 0 20px;
  background: var(--forest);
  color: white;
  font-size: 12px;
  transition: background 0.25s ease, transform 0.25s ease;
}

.primary-button:hover {
  transform: translateY(-3px);
  background: var(--sage);
}

.button-arrow {
  display: grid;
  width: 29px;
  height: 29px;
  place-items: center;
  border: 1px solid rgb(255 255 255 / 35%);
  border-radius: 50%;
  font-size: 15px;
}

.text-link {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #68766a;
  font-size: 12px;
}

.text-link span {
  transition: transform 0.2s ease;
}

.text-link:hover span {
  transform: translateX(-4px);
}

.hero-note {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 42px;
  color: #8b9489;
  font-size: 10px;
}

.note-icon {
  display: grid;
  width: 25px;
  height: 25px;
  place-items: center;
  border: 1px solid #cbd6c9;
  border-radius: 50%;
  color: var(--sage);
  font-size: 14px;
}

.hero-visual {
  position: relative;
  width: 100%;
  max-width: 440px;
  height: 465px;
  justify-self: end;
}

.portrait-backdrop {
  position: absolute;
  inset: 24px 20px 27px 35px;
  border-radius: 48% 52% 45% 55% / 38% 39% 61% 62%;
  background: var(--sage-soft);
  transform: rotate(6deg);
  transition: background 0.35s ease;
}

.portrait-ring {
  position: absolute;
  top: 0;
  right: 10px;
  width: 88%;
  height: 88%;
  border: 1px solid rgb(120 150 129 / 55%);
  border-radius: 50% 50% 42% 58% / 42% 37% 63% 58%;
  transform: rotate(-8deg);
}

.portrait-frame {
  position: absolute;
  inset: 18px 32px 30px 45px;
  overflow: hidden;
  border-radius: 48% 52% 44% 56% / 38% 40% 60% 62%;
  background: #d8dfd5;
}

.portrait-frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  mix-blend-mode: multiply;
  transition: transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.portrait-frame:hover img {
  transform: scale(1.045);
}

.portrait-label {
  position: absolute;
  right: -4px;
  bottom: 60px;
  display: flex;
  min-width: 185px;
  flex-direction: column;
  gap: 6px;
  padding: 17px 20px;
  border: 1px solid rgb(41 72 61 / 8%);
  background: #fbf9f2;
  box-shadow: 0 12px 40px rgb(41 72 61 / 8%);
}

.label-overline {
  color: var(--sage);
  font-size: 10px;
}

.portrait-label strong {
  font-size: 15px;
  font-weight: 700;
}

.portrait-label > span:last-child {
  color: #879187;
  font-size: 10px;
}

.floating-leaf {
  position: absolute;
  color: var(--clay);
}

.leaf-one {
  top: 18px;
  left: 15px;
  font-size: 35px;
  animation: drift 6s ease-in-out infinite;
}

.leaf-two {
  top: 55%;
  left: -8px;
  color: var(--sage);
  font-size: 23px;
  animation: drift 5s ease-in-out infinite reverse;
}

.organic-stamp {
  position: absolute;
  top: 17px;
  left: 0;
  display: grid;
  width: 92px;
  height: 92px;
  place-items: center;
  border-radius: 50%;
  background: var(--forest);
  color: white;
  transform: rotate(-14deg);
}

.organic-stamp svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  fill: white;
  font-size: 8px;
  letter-spacing: 1.4px;
}

.organic-stamp > span {
  font-size: 28px;
  font-weight: 300;
}

.hero-index {
  position: absolute;
  right: 4px;
  bottom: -15px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #8b9489;
  font-size: 10px;
}

.index-line {
  width: 38px;
  height: 1px;
  background: var(--sage);
}

.hero-bottom {
  position: absolute;
  right: 6.5%;
  bottom: 24px;
  left: 6.5%;
  display: flex;
  align-items: center;
  gap: 13px;
  color: #899287;
  font-size: 10px;
}

.scroll-indicator {
  display: grid;
  width: 32px;
  height: 32px;
  place-items: center;
  border: 1px solid #cdd7cb;
  border-radius: 50%;
  color: var(--forest);
  transition: background 0.25s ease, color 0.25s ease;
}

.scroll-indicator:hover {
  background: var(--forest);
  color: white;
}

.hero-bottom-caption {
  margin-right: auto;
}

/* Booking strip */
.booking-strip {
  position: relative;
  display: grid;
  grid-template-columns: 0.95fr 1.1fr 0.9fr;
  align-items: center;
  gap: 35px;
  padding: 36px 8%;
  background: var(--forest);
  color: #fffdf5;
}

.booking-number {
  color: #b9cbb8;
  font-size: 10px;
  letter-spacing: 0.3px;
}

.booking-intro h2 {
  margin: 10px 0 5px;
  font-size: 20px;
  font-weight: 500;
}

.booking-intro p {
  margin: 0;
  color: #c1cdc1;
  font-size: 11px;
  line-height: 2;
}

.booking-main-action {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 84px;
  padding: 17px;
  background: var(--sage);
  transition: background 0.25s ease;
}

.booking-main-action:hover {
  background: #8ca996;
}

.booking-action-icon {
  display: grid;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  place-items: center;
  border: 1px solid rgb(255 255 255 / 40%);
  border-radius: 50%;
  font-size: 18px;
}

.booking-main-action > span:nth-child(2) {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 7px;
}

.booking-main-action strong {
  font-size: 13px;
  font-weight: 600;
}

.booking-main-action small {
  color: #edf2e9;
  font-size: 10px;
}

.booking-action-arrow {
  font-size: 19px;
}

.booking-steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.booking-step {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 9px;
  white-space: nowrap;
}

.booking-step > span {
  display: grid;
  width: 31px;
  height: 31px;
  place-items: center;
  border: 1px solid rgb(255 255 255 / 35%);
  border-radius: 50%;
  color: #e7eee4;
  font-size: 11px;
}

.booking-step small {
  color: #c1cdc1;
  font-size: 9px;
}

.step-connector {
  width: 18px;
  height: 1px;
  margin-bottom: 23px;
  background: rgb(255 255 255 / 30%);
}

/* About */
.section-pad {
  padding: 110px 10%;
}

.about-section {
  position: relative;
  display: grid;
  grid-template-columns: 0.28fr 1fr;
  gap: 55px;
  overflow: hidden;
  background: #f7f4ec;
}

.section-aside {
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  gap: 60px;
}

.section-number {
  color: var(--sage);
  font-size: 11px;
  font-weight: 600;
}

.vertical-word {
  color: #d9dfd5;
  font-family: Arial, sans-serif;
  font-size: 42px;
  font-weight: 700;
  letter-spacing: 5px;
  writing-mode: vertical-rl;
  transform: rotate(180deg);
}

.about-content {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 0.9fr;
  gap: 50px;
}

.mini-label {
  display: inline-block;
  margin-bottom: 18px;
  color: #9aa394;
  font-size: 11px;
}

.about-heading h2 {
  margin: 0;
  font-size: clamp(34px, 4.2vw, 55px);
  font-weight: 500;
  line-height: 1.6;
  letter-spacing: -1.8px;
}

.about-heading h2 span {
  display: block;
  color: var(--sage);
}

.about-body {
  padding-top: 44px;
}

.about-lead {
  margin: 0 0 18px;
  color: var(--forest);
  font-size: 17px;
  font-weight: 500;
  line-height: 2;
}

.about-body > p:nth-child(2) {
  margin: 0;
  color: #7b847a;
  font-size: 12px;
  font-weight: 300;
  line-height: 2.5;
}

.underlined-link {
  display: inline-flex;
  align-items: center;
  gap: 15px;
  margin-top: 28px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--sage);
  font-size: 12px;
}

.underlined-link span {
  color: var(--sage);
  transition: transform 0.2s ease;
}

.underlined-link:hover span {
  transform: translate(-3px, 3px);
}

.about-decoration {
  position: absolute;
  right: -65px;
  bottom: -100px;
  width: 180px;
  color: var(--sage);
  opacity: 0.2;
  pointer-events: none;
}

/* Services */
.services-section {
  padding: 95px 10% 65px;
  background: var(--sage-pale);
  transition: background 0.3s ease;
}

.services-top {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 35px;
  margin-bottom: 45px;
}

.services-top h2,
.articles-header h2,
.testimonials-heading h2,
.visit-info h2,
.faq-intro h2 {
  margin: 17px 0 0;
  font-size: clamp(28px, 3.5vw, 43px);
  font-weight: 500;
  line-height: 1.7;
  letter-spacing: -1.4px;
}

.services-top h2 em,
.articles-header h2 em,
.testimonials-heading h2 em,
.visit-info h2 em,
.faq-intro h2 em {
  color: var(--sage);
  font-style: normal;
}

.services-top > p {
  max-width: 280px;
  margin: 0 0 7px;
  color: #798578;
  font-size: 12px;
  line-height: 2.2;
}

.services-list {
  border-top: 1px solid #d5ded3;
}

.service-row {
  display: grid;
  grid-template-columns: 60px 60px 1fr 35px;
  align-items: center;
  gap: 20px;
  min-height: 105px;
  border-bottom: 1px solid #d5ded3;
  transition: padding 0.3s ease;
}

.service-row:hover {
  padding-right: 12px;
}

.service-number {
  color: #98a596;
  font-family: Arial, sans-serif;
  font-size: 12px;
}

.service-icon {
  display: grid;
  width: 48px;
  height: 48px;
  place-items: center;
  border: 1px solid #c8d5c6;
  border-radius: 50%;
  color: var(--sage);
  font-size: 25px;
  transition: background 0.25s ease, color 0.25s ease;
}

.service-row:hover .service-icon {
  background: var(--sage);
  color: white;
}

.service-content {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.service-content strong {
  font-size: 16px;
  font-weight: 600;
}

.service-content small {
  color: #798578;
  font-size: 11px;
  font-weight: 300;
}

.service-arrow {
  color: var(--sage);
  font-size: 21px;
  transition: transform 0.25s ease;
}

.service-row:hover .service-arrow {
  transform: translate(-4px, 4px);
}

.services-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding-top: 26px;
  color: #899287;
  font-size: 10px;
}

.services-footer a {
  display: inline-flex;
  align-items: center;
  gap: 13px;
  color: var(--forest);
  font-size: 11px;
  font-weight: 600;
}

/* Manifesto */
.manifesto-section {
  position: relative;
  display: grid;
  min-height: 390px;
  place-items: center;
  overflow: hidden;
  background: var(--sage);
  color: #fffdf5;
}

.manifesto-shape {
  position: absolute;
  top: -140px;
  right: -40px;
  width: 440px;
  height: 440px;
  border: 1px solid rgb(255 255 255 / 22%);
  border-radius: 43% 57% 60% 40% / 46% 35% 65% 54%;
  transform: rotate(20deg);
}

.manifesto-shape::before,
.manifesto-shape::after {
  position: absolute;
  border: 1px solid rgb(255 255 255 / 20%);
  border-radius: inherit;
  content: "";
}

.manifesto-shape::before {
  inset: 25px;
}

.manifesto-shape::after {
  inset: 52px;
}

.manifesto-content {
  position: relative;
  z-index: 1;
  padding: 70px 20px;
  text-align: center;
}

.manifesto-star {
  display: inline-block;
  margin-bottom: 16px;
  font-size: 26px;
}

.manifesto-overline {
  margin: 0;
  color: #e5ece2;
  font-family: Arial, sans-serif;
  font-size: 9px;
  letter-spacing: 3px;
}

.manifesto-content h2 {
  margin: 24px 0;
  font-size: clamp(27px, 4vw, 43px);
  font-weight: 400;
  line-height: 1.8;
}

.manifesto-content h2 span {
  color: #e7d8c6;
}

.manifesto-link {
  display: inline-flex;
  align-items: center;
  gap: 17px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgb(255 255 255 / 55%);
  font-size: 12px;
}

.manifesto-link span {
  font-size: 16px;
}

.manifesto-side-text {
  position: absolute;
  bottom: 30px;
  left: 6%;
  color: rgb(255 255 255 / 55%);
  font-family: Arial, sans-serif;
  font-size: 9px;
  letter-spacing: 2px;
  writing-mode: vertical-rl;
}

/* Testimonials */
.testimonials-section {
  background: #f7f4ec;
}

.testimonials-heading {
  max-width: 580px;
  margin-bottom: 45px;
}

.testimonials-heading > p {
  max-width: 400px;
  margin: 13px 0 0;
  color: #7b847a;
  font-size: 12px;
  line-height: 2.2;
}

.testimonial-layout {
  display: grid;
  grid-template-columns: 0.8fr 1.2fr;
  gap: 55px;
}

.testimonial-feature {
  position: relative;
  display: flex;
  min-height: 310px;
  flex-direction: column;
  justify-content: space-between;
  padding: 35px;
  overflow: hidden;
  background: var(--sage-soft);
  transition: background 0.3s ease;
}

.quote-mark {
  color: var(--sage);
  font-family: Georgia, serif;
  font-size: 78px;
  line-height: 0.8;
}

.testimonial-feature > p {
  margin: 20px 0;
  font-size: 22px;
  font-weight: 400;
  line-height: 2;
}

.quote-footer {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #788678;
  font-size: 10px;
}

.quote-line {
  width: 28px;
  height: 1px;
  background: var(--sage);
}

.testimonial-list {
  display: flex;
  flex-direction: column;
}

.testimonial-item {
  padding: 19px 0 21px;
  border-bottom: 1px solid #e1e3da;
}

.testimonial-item:first-child {
  padding-top: 0;
}

.review-stars {
  display: flex;
  gap: 5px;
  color: var(--sage);
  font-size: 12px;
}

.testimonial-item > p {
  margin: 12px 0 16px;
  color: #59695e;
  font-size: 12px;
  line-height: 2.1;
}

.review-person {
  display: flex;
  align-items: center;
  gap: 10px;
}

.review-avatar {
  display: grid;
  width: 33px;
  height: 33px;
  place-items: center;
  border-radius: 50%;
  background: var(--sage-soft);
  color: var(--forest);
  font-size: 12px;
}

.review-person > span:last-child {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.review-person strong {
  font-size: 11px;
  font-weight: 600;
}

.review-person small {
  color: #969c91;
  font-size: 9px;
}

/* Visit */
.visit-section {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  min-height: 580px;
  background: #eeeee4;
}

.visit-info {
  align-self: center;
  padding: 75px 12%;
}

.visit-info h2 {
  margin-bottom: 18px;
}

.visit-info > p {
  margin: 0 0 30px;
  color: #788175;
  font-size: 12px;
  line-height: 2.3;
}

.visit-detail {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 16px 0;
  border-top: 1px solid #d9ded3;
}

.detail-symbol {
  display: grid;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  place-items: center;
  border: 1px solid #c6d1c4;
  border-radius: 50%;
  color: var(--sage);
  font-size: 21px;
}

.visit-detail > span:last-child {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.visit-detail strong {
  font-size: 12px;
  font-weight: 600;
}

.visit-detail small {
  color: #879084;
  font-size: 10px;
}

.visit-button {
  margin-top: 25px;
}

.visit-art {
  position: relative;
  min-height: 480px;
  overflow: hidden;
  background: var(--sage-soft);
  transition: background 0.3s ease;
}

.map-abstract {
  position: absolute;
  inset: 0;
}

.map-abstract svg {
  width: 100%;
  height: 100%;
}

.map-caption {
  position: absolute;
  right: 7%;
  bottom: 9%;
  display: flex;
  align-items: center;
  gap: 13px;
  min-width: 245px;
  padding: 17px 20px;
  background: #f7f4ec;
  box-shadow: 0 15px 45px rgb(41 72 61 / 9%);
}

.map-caption-icon {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 50%;
  background: var(--sage-soft);
  color: var(--forest);
  font-size: 22px;
}

.map-caption > div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.map-caption strong {
  font-size: 11px;
  font-weight: 600;
}

.map-caption small {
  color: #8b9489;
  font-size: 9px;
}

.map-side-label {
  position: absolute;
  top: 25px;
  left: 25px;
  color: #7c9580;
  font-family: Arial, sans-serif;
  font-size: 9px;
  letter-spacing: 2px;
  writing-mode: vertical-rl;
}

/* FAQ */
.faq-section {
  display: grid;
  grid-template-columns: 0.75fr 1.25fr;
  gap: 65px;
  background: #f7f4ec;
}

.faq-intro h2 {
  margin-bottom: 12px;
}

.faq-intro > p {
  color: #7b847a;
  font-size: 12px;
}

.faq-list {
  border-top: 1px solid #e0e2d9;
}

.faq-item {
  border-bottom: 1px solid #e0e2d9;
}

.faq-question {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 22px 0;
  border: 0;
  background: transparent;
  color: var(--forest);
  text-align: right;
  font-size: 13px;
}

.faq-toggle {
  display: grid;
  width: 29px;
  height: 29px;
  flex-shrink: 0;
  place-items: center;
  border: 1px solid #d7dfd3;
  border-radius: 50%;
  color: var(--sage);
  font-size: 18px;
  transition: background 0.2s ease, color 0.2s ease;
}

.faq-open .faq-toggle {
  background: var(--sage);
  color: white;
}

.faq-answer {
  padding: 0 0 22px;
  color: #7b847a;
  font-size: 12px;
  line-height: 2.3;
  animation: reveal 0.25s ease both;
}

/* Articles */
.articles-section {
  padding: 90px 10%;
  background: var(--sage-pale);
  transition: background 0.3s ease;
}

.articles-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 25px;
  margin-bottom: 35px;
}

.articles-header h2 {
  margin-top: 12px;
}

.all-articles {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #b6c5b3;
  white-space: nowrap;
  font-size: 11px;
}

.all-articles span {
  color: var(--sage);
}

.article-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  align-items: start;
  gap: 35px;
}

.article-item {
  display: block;
  min-width: 0;
}

.article-item:nth-child(2) {
  margin-top: 70px;
}

.article-image {
  position: relative;
  height: 235px;
  overflow: hidden;
  background: #dce3d9;
}

.article-image-tall {
  height: 305px;
}

.article-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.article-item:hover .article-image img {
  transform: scale(1.05);
}

.article-image-arrow {
  position: absolute;
  right: 17px;
  bottom: 17px;
  display: grid;
  width: 37px;
  height: 37px;
  place-items: center;
  border-radius: 50%;
  background: #f7f4ec;
  color: var(--forest);
  transition: background 0.2s ease, color 0.2s ease;
}

.article-item:hover .article-image-arrow {
  background: var(--forest);
  color: white;
}

.article-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin-top: 20px;
  color: #849080;
  font-size: 10px;
}

.article-meta span:first-child {
  color: var(--sage);
}

.article-item h3 {
  max-width: 450px;
  margin: 12px 0;
  font-size: 19px;
  font-weight: 500;
  line-height: 1.9;
  transition: color 0.2s ease;
}

.article-item:hover h3 {
  color: var(--sage);
}

.article-read {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  color: #778476;
  font-size: 10px;
}

.article-read span {
  color: var(--sage);
}

/* Final CTA */
.final-cta {
  position: relative;
  display: grid;
  min-height: 410px;
  place-items: center;
  overflow: hidden;
  background: var(--forest);
  color: #fffdf5;
  text-align: center;
}

.cta-content {
  position: relative;
  z-index: 2;
  padding: 65px 20px;
}

.cta-kicker {
  color: #b5c9b5;
  font-family: Arial, sans-serif;
  font-size: 9px;
  letter-spacing: 2.5px;
}

.cta-content h2 {
  margin: 20px 0 10px;
  font-size: clamp(32px, 4vw, 49px);
  font-weight: 400;
  line-height: 1.7;
  letter-spacing: -1px;
}

.cta-content h2 span {
  color: #c9d9c6;
}

.cta-content > p {
  margin: 0 auto;
  color: #bdcbbd;
  font-size: 12px;
  line-height: 2.2;
}

.cta-button {
  display: inline-flex;
  align-items: center;
  gap: 35px;
  margin-top: 27px;
  padding: 15px 21px;
  background: #f7f4ec;
  color: var(--forest);
  font-size: 12px;
  transition: background 0.25s ease, transform 0.25s ease;
}

.cta-button:hover {
  transform: translateY(-3px);
  background: var(--sage-soft);
}

.cta-button span {
  font-size: 17px;
}

.cta-orbit {
  position: absolute;
  width: 360px;
  height: 360px;
  border: 1px solid rgb(255 255 255 / 11%);
  border-radius: 42% 58% 53% 47% / 48% 38% 62% 52%;
  pointer-events: none;
}

.orbit-one {
  top: -170px;
  right: -90px;
  transform: rotate(30deg);
}

.orbit-two {
  bottom: -240px;
  left: -70px;
  width: 470px;
  height: 470px;
  transform: rotate(-20deg);
}

.cta-flower {
  position: absolute;
  top: 42px;
  left: 10%;
  color: #90a995;
  font-size: 54px;
  animation: drift 7s ease-in-out infinite;
}

/* Footer */
.site-footer {
  padding: 35px 6.5% 20px;
  background: #f7f4ec;
}

.footer-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  padding-bottom: 30px;
}

.footer-note {
  color: #879187;
  font-size: 11px;
}

.back-top {
  color: var(--sage);
  font-size: 11px;
}

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding-top: 18px;
  border-top: 1px solid #e4e4da;
  color: #92998d;
  font-size: 9px;
}

/* Floating appointment */
.floating-booking {
  position: fixed;
  z-index: 50;
  right: 25px;
  bottom: 25px;
  display: flex;
  width: 76px;
  height: 76px;
  align-items: center;
  flex-direction: column;
  justify-content: center;
  gap: 1px;
  overflow: visible;
  border: 1px solid rgb(255 255 255 / 65%);
  border-radius: 50%;
  background: var(--forest);
  box-shadow: 0 7px 25px rgb(41 72 61 / 22%);
  color: white;
  transition: transform 0.3s ease, background 0.3s ease;
}

.floating-booking:hover {
  transform: translateY(-5px) rotate(-4deg);
  background: var(--sage);
}

.floating-booking-orbit {
  position: absolute;
  inset: -6px;
  border: 1px solid var(--sage);
  border-radius: 50%;
  opacity: 0.65;
  animation: orbitPulse 3s ease-out infinite;
  pointer-events: none;
}

.floating-booking-icon {
  font-size: 19px;
  line-height: 1.1;
}

.floating-booking-text {
  font-size: 12px;
  font-weight: 600;
}

@keyframes drift {
  0%,
  100% {
    transform: translateY(0) rotate(0);
  }
  50% {
    transform: translateY(-9px) rotate(7deg);
  }
}

@keyframes orbitPulse {
  0% {
    transform: scale(0.93);
    opacity: 0.6;
  }
  75%,
  100% {
    transform: scale(1.22);
    opacity: 0;
  }
}

@keyframes reveal {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (min-width: 1500px) {
  .hero {
    padding-right: max(10%, calc((100vw - 1250px) / 2));
    padding-left: max(10%, calc((100vw - 1250px) / 2));
  }

  .site-header,
  .footer-top,
  .footer-bottom {
    padding-right: max(6.5%, calc((100vw - 1350px) / 2));
    padding-left: max(6.5%, calc((100vw - 1350px) / 2));
  }
}

@media (max-width: 1100px) {
  .site-header {
    padding-right: 4%;
    padding-left: 4%;
  }

  .hero {
    padding-right: 7%;
    padding-left: 7%;
  }

  .hero h1 {
    font-size: 54px;
  }

  .hero-visual {
    height: 410px;
  }

  .booking-strip {
    grid-template-columns: 0.8fr 1.1fr;
    gap: 25px;
    padding-right: 6%;
    padding-left: 6%;
  }

  .booking-steps {
    grid-column: 1 / -1;
    justify-content: center;
    gap: 18px;
  }

  .booking-step {
    flex-direction: row;
  }

  .step-connector {
    margin: 0;
  }

  .section-pad,
  .services-section,
  .articles-section {
    padding-right: 7%;
    padding-left: 7%;
  }

  .about-section {
    gap: 30px;
  }

  .about-content {
    gap: 25px;
  }

  .visit-info {
    padding-right: 8%;
    padding-left: 8%;
  }
}

@media (max-width: 760px) {
  html {
    scroll-padding-top: 70px;
  }

  .site-header {
    min-height: 72px;
    padding: 0 5%;
  }

  .brand-symbol {
    width: 36px;
    height: 36px;
  }

  .brand-copy strong {
    font-size: 12px;
  }

  .brand-copy small {
    font-size: 9px;
  }

  .desktop-nav,
  .header-booking,
  .theme-button > span:last-child {
    display: none;
  }

  .header-actions {
    gap: 14px;
  }

  .theme-button {
    padding: 9px;
  }

  .menu-toggle {
    display: flex;
    width: 39px;
    height: 39px;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    border: 1px solid #dce1d8;
    background: transparent;
  }

  .menu-toggle span {
    width: 16px;
    height: 1px;
    background: var(--forest);
  }

  .mobile-nav {
    position: absolute;
    z-index: 19;
    top: 72px;
    right: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    padding: 12px 5% 20px;
    border-bottom: 1px solid #dce1d8;
    background: #f7f4ec;
    box-shadow: 0 15px 25px rgb(41 72 61 / 5%);
  }

  .mobile-nav a {
    padding: 13px 0;
    border-bottom: 1px solid #e9e9e0;
    color: var(--forest);
    font-size: 13px;
  }

  .mobile-nav .mobile-booking {
    margin-top: 10px;
    padding: 14px 17px;
    border: 0;
    background: var(--forest);
    color: white;
    text-align: center;
  }

  .hero {
    display: flex;
    min-height: auto;
    flex-direction: column;
    align-items: stretch;
    padding: 36px 7% 65px;
  }

  .hero-background-shape {
    top: 290px;
    right: 16%;
    width: 90%;
    height: 470px;
  }

  .hero-copy {
    padding: 0;
  }

  .eyebrow {
    margin-bottom: 17px;
    font-size: 11px;
  }

  .hero h1 {
    font-size: clamp(38px, 10vw, 54px);
    line-height: 1.5;
    letter-spacing: -1.8px;
  }

  .hero-description {
    max-width: 430px;
    margin: 16px 0 21px;
    font-size: 12px;
  }

  .hero-actions {
    gap: 18px;
  }

  .primary-button {
    min-height: 49px;
    gap: 14px;
    padding: 0 14px;
    font-size: 11px;
  }

  .button-arrow {
    width: 25px;
    height: 25px;
  }

  .text-link {
    font-size: 11px;
  }

  .hero-note {
    margin-top: 22px;
    font-size: 9px;
  }

  .hero-visual {
    width: min(100%, 410px);
    height: min(105vw, 440px);
    margin: 28px auto 0;
    align-self: center;
  }

  .portrait-frame {
    inset: 14px 25px 22px 35px;
  }

  .portrait-backdrop {
    inset: 20px 16px 20px 25px;
  }

  .portrait-ring {
    right: 8px;
  }

  .portrait-label {
    right: -4px;
    bottom: 50px;
    min-width: 150px;
    padding: 13px 15px;
  }

  .portrait-label strong {
    font-size: 13px;
  }

  .organic-stamp {
    top: 10px;
    left: -1px;
    width: 73px;
    height: 73px;
  }

  .organic-stamp > span {
    font-size: 24px;
  }

  .hero-index {
    right: 0;
    bottom: -12px;
  }

  .hero-bottom {
    right: 5%;
    bottom: 13px;
    left: 5%;
    font-size: 9px;
  }

  .hero-bottom-caption {
    max-width: 135px;
    text-align: left;
  }

  .booking-strip {
    grid-template-columns: 1fr;
    gap: 22px;
    padding: 29px 7%;
  }

  .booking-intro h2 {
    font-size: 19px;
  }

  .booking-main-action {
    min-height: 77px;
  }

  .booking-steps {
    grid-column: auto;
    justify-content: space-between;
    gap: 7px;
    padding-top: 3px;
  }

  .booking-step {
    flex-direction: column;
    gap: 7px;
  }

  .booking-step small {
    font-size: 9px;
  }

  .step-connector {
    width: 24px;
    margin-bottom: 22px;
  }

  .section-pad {
    padding: 68px 7%;
  }

  .about-section {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .section-aside {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
  }

  .vertical-word {
    font-size: 20px;
    writing-mode: horizontal-tb;
    transform: none;
  }

  .about-content {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .mini-label {
    margin-bottom: 9px;
  }

  .about-heading h2 {
    font-size: 38px;
    line-height: 1.5;
  }

  .about-heading h2 span {
    display: inline;
  }

  .about-body {
    padding-top: 8px;
  }

  .about-lead {
    font-size: 15px;
  }

  .about-body > p:nth-child(2) {
    font-size: 12px;
  }

  .about-decoration {
    right: auto;
    bottom: -75px;
    left: -30px;
    width: 120px;
  }

  .services-section {
    padding: 65px 7% 40px;
  }

  .services-top {
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 25px;
  }

  .services-top h2,
  .articles-header h2,
  .testimonials-heading h2,
  .visit-info h2,
  .faq-intro h2 {
    margin-top: 11px;
    font-size: 29px;
    line-height: 1.7;
  }

  .services-top > p {
    max-width: 100%;
    font-size: 11px;
  }

  .service-row {
    grid-template-columns: 28px 42px 1fr 18px;
    gap: 10px;
    min-height: 94px;
  }

  .service-number {
    font-size: 10px;
  }

  .service-icon {
    width: 38px;
    height: 38px;
    font-size: 21px;
  }

  .service-content strong {
    font-size: 13px;
  }

  .service-content small {
    font-size: 10px;
    line-height: 1.9;
  }

  .service-arrow {
    font-size: 17px;
  }

  .services-footer {
    align-items: flex-start;
    flex-direction: column;
    gap: 12px;
    font-size: 9px;
    line-height: 2;
  }

  .manifesto-section {
    min-height: 320px;
  }

  .manifesto-shape {
    top: -90px;
    right: -180px;
    width: 350px;
    height: 350px;
  }

  .manifesto-content {
    padding: 55px 7%;
  }

  .manifesto-content h2 {
    font-size: 27px;
  }

  .manifesto-side-text {
    bottom: 15px;
    left: 12px;
    font-size: 7px;
  }

  .testimonials-heading {
    margin-bottom: 28px;
  }

  .testimonials-heading > p {
    font-size: 11px;
  }

  .testimonial-layout {
    grid-template-columns: 1fr;
    gap: 25px;
  }

  .testimonial-feature {
    min-height: 250px;
    padding: 27px;
  }

  .testimonial-feature > p {
    font-size: 19px;
  }

  .testimonial-item {
    padding: 20px 0;
  }

  .testimonial-item > p {
    font-size: 11px;
  }

  .visit-section {
    grid-template-columns: 1fr;
  }

  .visit-info {
    padding: 65px 7%;
  }

  .visit-info > p {
    font-size: 11px;
  }

  .visit-art {
    min-height: 330px;
  }

  .map-caption {
    right: 5%;
    bottom: 6%;
    min-width: 225px;
    padding: 13px 15px;
  }

  .map-side-label {
    top: 15px;
    left: 15px;
  }

  .faq-section {
    grid-template-columns: 1fr;
    gap: 25px;
  }

  .faq-intro > p {
    font-size: 11px;
  }

  .faq-question {
    gap: 15px;
    padding: 18px 0;
    font-size: 12px;
    line-height: 1.9;
  }

  .faq-answer {
    font-size: 11px;
  }

  .articles-section {
    padding: 65px 7%;
  }

  .articles-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
  }

  .all-articles {
    font-size: 10px;
  }

  .article-grid {
    grid-template-columns: 1fr;
    gap: 35px;
  }

  .article-item:nth-child(2) {
    margin-top: 0;
  }

  .article-image,
  .article-image-tall {
    height: 225px;
  }

  .article-item h3 {
    font-size: 17px;
  }

  .final-cta {
    min-height: 350px;
  }

  .cta-content {
    padding: 50px 7%;
  }

  .cta-content h2 {
    font-size: 34px;
  }

  .cta-content > p {
    max-width: 350px;
    font-size: 11px;
  }

  .cta-flower {
    top: 18px;
    left: 6%;
    font-size: 34px;
  }

  .site-footer {
    padding: 28px 6% 18px;
  }

  .footer-top {
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
  }

  .footer-note {
    width: 100%;
    order: 3;
    margin: 0;
    font-size: 10px;
  }

  .back-top {
    font-size: 10px;
  }

  .footer-bottom {
    align-items: flex-start;
    flex-direction: column;
    gap: 9px;
    font-size: 9px;
  }

  .floating-booking {
    right: 15px;
    bottom: 15px;
    width: 64px;
    height: 64px;
  }

  .floating-booking-icon {
    font-size: 17px;
  }

  .floating-booking-text {
    font-size: 11px;
  }
}

@media (max-width: 380px) {
  .hero h1 {
    font-size: 35px;
  }

  .hero-actions {
    align-items: flex-start;
    flex-direction: column;
    gap: 13px;
  }

  .hero-visual {
    height: 340px;
  }

  .portrait-label {
    bottom: 36px;
    min-width: 138px;
  }

  .booking-step small {
    font-size: 8px;
  }

  .step-connector {
    width: 13px;
  }

  .service-row {
    grid-template-columns: 23px 36px 1fr 14px;
    gap: 7px;
  }

  .service-icon {
    width: 34px;
    height: 34px;
  }

  .service-content strong {
    font-size: 12px;
  }

  .service-content small {
    font-size: 9px;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>

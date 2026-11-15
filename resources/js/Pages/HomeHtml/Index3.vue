<script setup>
import { computed, ref } from "vue";
import { Head } from "@inertiajs/vue3";

// تنها متغیری که برای تغییر تم نیاز داری
const themeColor = ref("rose");

// رنگ‌های قابل انتخاب
const themes = {
  rose: {
    primary: "#c65f79",
    dark: "#87384f",
    light: "#fcecf1",
    soft: "#fff6f8",
  },
  teal: {
    primary: "#168b86",
    dark: "#075e5a",
    light: "#e0f5f2",
    soft: "#f3fbfa",
  },
  blue: {
    primary: "#4778bc",
    dark: "#274d82",
    light: "#e9f1ff",
    soft: "#f5f8ff",
  },
  violet: {
    primary: "#8665c6",
    dark: "#563c8d",
    light: "#f0eaff",
    soft: "#faf7ff",
  },
  green: {
    primary: "#54876b",
    dark: "#315940",
    light: "#e8f4eb",
    soft: "#f5faf6",
  },
};

const theme = computed(() => themes[themeColor.value] || themes.rose);

const cssVars = computed(() => ({
  "--primary": theme.value.primary,
  "--primary-dark": theme.value.dark,
  "--primary-light": theme.value.light,
  "--primary-soft": theme.value.soft,
}));

// مسیرها را متناسب با Route های واقعی پروژه تنظیم کن
const bookingUrl = "/booking";
const doctorImage = "/storage/doctors/Dr.Farhad.Vafaei.jpg";

const services = [
  {
    title: "جراحی عمومی",
    description: "بررسی و درمان مشکلات نیازمند ارزیابی جراحی",
    icon: "✳",
  },
  {
    title: "جراحی چاقی",
    description: "ارزیابی و مشاوره درباره روش‌های درمان چاقی",
    icon: "◈",
  },
  {
    title: "جراحی پستان",
    description: "ارزیابی تخصصی مشکلات و بیماری‌های پستان",
    icon: "♡",
  },
  {
    title: "مشاوره جراحی",
    description: "دریافت راهنمایی درباره روند درمان و پیگیری",
    icon: "✚",
  },
];

const gallery = [
  {
    title: "محیط درمانی",
    image:
      "https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=700&q=80",
  },
  {
    title: "تجهیزات پزشکی",
    image:
      "https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=700&q=80",
  },
  {
    title: "مراقبت و درمان",
    image:
      "https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=700&q=80",
  },
];

const articles = [
  {
    title: "قبل از مراجعه به پزشک چه نکاتی را بدانیم؟",
    category: "راهنمای مراجعه",
    image:
      "https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=700&q=80",
  },
  {
    title: "اهمیت پیگیری و مراقبت بعد از درمان",
    category: "مراقبت‌های سلامت",
    image:
      "https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=700&q=80",
  },
  {
    title: "چگونه برای جلسه مشاوره آماده شویم؟",
    category: "آموزش سلامت",
    image:
      "https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=700&q=80",
  },
];

// اطلاعات نمایشی نیستند؛ برای درج اطلاعات واقعی تکمیل شوند.
const testimonials = ref([]);

const clinics = [
  {
    city: "بجنورد",
    address:
      "خیابان طالقانی شرقی، کوچه جاجرمی، طالقانی ۲۶، ساختمان پزشکان فرهاد، طبقه دوم",
    phone: "09151882237",
    map: "https://www.google.com/maps/search/?api=1&query=ساختمان+پزشکان+فرهاد+بجنورد",
  },
  {
    city: "مشهد",
    address: "بلوار هاشمیه، نبش هاشمیه ۲۲، طبقه فوقانی چرم آلدو، طبقه اول",
    phone: "09151882237",
    map: "https://www.google.com/maps/search/?api=1&query=هاشمیه+۲۲+مشهد",
  },
];

const workingHours = [
  { day: "یکشنبه", time: "نیازمند تأیید برنامه مطب" },
  { day: "دوشنبه", time: "نیازمند تأیید برنامه مطب" },
];

const activeClinic = ref(0);
</script>

<template>
  <Head title="دکتر فرهاد وفایی | دریافت نوبت و اطلاعات مطب" />

  <div class="site" dir="rtl" :style="cssVars">
    <!-- Header -->
    <header class="header">
      <div class="container nav">
        <a href="#home" class="brand">
          <span class="brand-icon">+</span>
          <span>
            <strong>دکتر فرهاد وفایی</strong>
            <small>متخصص جراحی عمومی</small>
          </span>
        </a>

        <nav class="nav-links">
          <a href="#about">درباره پزشک</a>
          <a href="#services">خدمات</a>
          <a href="#gallery">گالری</a>
          <a href="#articles">مطالب آموزشی</a>
          <a href="#contact">تماس با ما</a>
        </nav>

        <a :href="bookingUrl" class="btn btn-primary header-cta">
          دریافت نوبت <span>←</span>
        </a>
      </div>
    </header>

    <main>
      <!-- Hero -->
      <section id="home" class="hero">
        <div class="container hero-grid">
          <div class="hero-content">
            <div class="eyebrow">
              <span class="pulse"></span>
              همراه شما در مسیر سلامت
            </div>

            <h1>
              سلامتی شما،
              <span>اولویت ماست.</span>
            </h1>

            <p class="hero-description">
              برای آشنایی با خدمات پزشکی، دریافت مشاوره و رزرو نوبت، از طریق سامانه
              نوبت‌دهی اقدام کنید.
            </p>

            <div class="hero-actions">
              <a :href="bookingUrl" class="btn btn-primary btn-large">
                <span>رزرو نوبت آنلاین</span>
                <span class="arrow">←</span>
              </a>

              <a href="#about" class="btn btn-outline btn-large"> آشنایی با پزشک </a>
            </div>

            <div class="hero-note">
              <span class="note-icon">✓</span>
              <span>مشاهده اطلاعات مطب و انتخاب زمان مراجعه</span>
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-orb"></div>

            <div class="doctor-photo-wrap">
              <img
                :src="doctorImage"
                alt="تصویر دکتر فرهاد وفایی"
                class="doctor-photo"
                @error="$event.target.style.visibility = 'hidden'"
              />

              <div class="photo-fallback">
                <span>دکتر</span>
                <strong>فرهاد وفایی</strong>
              </div>
            </div>

            <div class="floating-card floating-card-top">
              <span class="float-icon">✚</span>
              <span>
                <strong>مراقبت آگاهانه</strong>
                <small>با تمرکز بر نیاز بیمار</small>
              </span>
            </div>

            <div class="floating-card floating-card-bottom">
              <span class="float-icon">◷</span>
              <span>
                <strong>نوبت‌دهی آنلاین</strong>
                <small>برای انتخاب زمان مراجعه</small>
              </span>
            </div>

            <div class="decorative-circle circle-one"></div>
            <div class="decorative-circle circle-two"></div>
          </div>
        </div>

        <div class="hero-bottom container">
          <a href="#about" class="scroll-link">
            <span class="scroll-icon">↓</span>
            برای آشنایی بیشتر ادامه دهید
          </a>
          <span class="hero-caption">اطلاعات پزشک · خدمات · نوبت‌دهی</span>
        </div>
      </section>

      <!-- Quick access -->
      <section class="quick-section">
        <div class="container quick-grid">
          <a href="#about" class="quick-item">
            <span class="quick-icon">♧</span>
            <span><strong>درباره پزشک</strong><small>معرفی و اطلاعات</small></span>
            <span class="quick-arrow">←</span>
          </a>

          <a href="#hours" class="quick-item">
            <span class="quick-icon">◷</span>
            <span><strong>ساعات کاری</strong><small>برنامه مراجعه</small></span>
            <span class="quick-arrow">←</span>
          </a>

          <a href="#location" class="quick-item">
            <span class="quick-icon">⌖</span>
            <span><strong>آدرس مطب</strong><small>مسیریابی و نقشه</small></span>
            <span class="quick-arrow">←</span>
          </a>

          <a :href="bookingUrl" class="quick-item">
            <span class="quick-icon">▦</span>
            <span><strong>دریافت نوبت</strong><small>شروع رزرو اینترنتی</small></span>
            <span class="quick-arrow">←</span>
          </a>
        </div>
      </section>

      <!-- About -->
      <section id="about" class="section about-section">
        <div class="container about-grid">
          <div class="about-visual">
            <div class="about-image-frame">
              <img :src="doctorImage" alt="دکتر فرهاد وفایی" class="about-image" />
            </div>
            <div class="about-label">
              <span class="label-plus">+</span>
              <span>
                <strong>دکتر فرهاد وفایی</strong>
                <small>متخصص جراحی عمومی</small>
              </span>
            </div>
            <div class="about-decoration"></div>
          </div>

          <div class="about-content">
            <span class="section-kicker">ABOUT THE DOCTOR</span>
            <h2 class="section-title">
              آشنایی با
              <span>دکتر فرهاد وفایی</span>
            </h2>

            <p class="section-text">
              در این بخش با سوابق علمی، زمینه‌های فعالیت و خدمات پزشک آشنا شوید. انتخاب
              آگاهانه و دریافت اطلاعات روشن، بخشی از مسیر مناسب مراقبت از سلامت است.
            </p>

            <div class="about-facts">
              <div class="fact">
                <span class="fact-icon">✚</span>
                <div>
                  <strong>تخصص</strong>
                  <small>جراحی عمومی</small>
                </div>
              </div>

              <div class="fact">
                <span class="fact-icon">⌖</span>
                <div>
                  <strong>محل فعالیت</strong>
                  <small>بجنورد و مشهد</small>
                </div>
              </div>

              <div class="fact">
                <span class="fact-icon">▤</span>
                <div>
                  <strong>اطلاعات علمی</strong>
                  <small>سوابق و معرفی پزشک</small>
                </div>
              </div>

              <div class="fact">
                <span class="fact-icon">♡</span>
                <div>
                  <strong>راهنمای مراجعه</strong>
                  <small>اطلاعات پیش از نوبت</small>
                </div>
              </div>
            </div>

            <a href="#contact" class="text-link"> مشاهده اطلاعات مطب <span>←</span> </a>
          </div>
        </div>
      </section>

      <!-- Services -->
      <section id="services" class="section services-section">
        <div class="container">
          <div class="section-heading">
            <div>
              <span class="section-kicker">MEDICAL SERVICES</span>
              <h2 class="section-title">خدمات و زمینه‌های فعالیت</h2>
              <p class="section-text">برای آشنایی اولیه با زمینه‌های فعالیت پزشک.</p>
            </div>
            <a :href="bookingUrl" class="text-link"> دریافت نوبت <span>←</span> </a>
          </div>

          <div class="services-grid">
            <article
              v-for="(service, index) in services"
              :key="service.title"
              class="service-card"
            >
              <div class="service-top">
                <span class="service-icon">{{ service.icon }}</span>
                <span class="service-number"> 0{{ index + 1 }} </span>
              </div>
              <h3>{{ service.title }}</h3>
              <p>{{ service.description }}</p>
              <a :href="bookingUrl" class="service-link">
                اطلاعات و نوبت <span>←</span>
              </a>
            </article>
          </div>

          <p class="disclaimer">
            ارائه هر خدمت به ارزیابی پزشک و شرایط فردی بیمار بستگی دارد. فهرست خدمات باید
            با خدمات واقعی مطب تطبیق داده شود.
          </p>
        </div>
      </section>

      <!-- Gallery -->
      <section id="gallery" class="section gallery-section">
        <div class="container">
          <div class="section-heading">
            <div>
              <span class="section-kicker">GALLERY</span>
              <h2 class="section-title">گالری و فضای درمان</h2>
              <p class="section-text">
                تصاویر محیط مطب، امکانات و محتوای تصویری مورد تأیید پزشک.
              </p>
            </div>
          </div>

          <div class="gallery-grid">
            <article
              v-for="(item, index) in gallery"
              :key="item.title"
              class="gallery-card"
              :class="{ 'gallery-card-large': index === 0 }"
            >
              <img :src="item.image" :alt="item.title" loading="lazy" />
              <div class="gallery-overlay">
                <span>{{ item.title }}</span>
                <span class="gallery-plus">↗</span>
              </div>
            </article>
          </div>

          <p class="disclaimer">تصاویر نمونه</p>
        </div>
      </section>

      <!-- Testimonials -->
      <section id="reviews" class="section reviews-section">
        <div class="container reviews-layout">
          <div class="reviews-intro">
            <span class="section-kicker">PATIENT FEEDBACK</span>
            <h2 class="section-title">
              تجربه مراجعه‌کنندگان
              <span>برای ما ارزشمند است.</span>
            </h2>
            <p class="section-text">
              دیدگاه‌های ثبت‌شده می‌توانند به مراجعه‌کنندگان برای شناخت بهتر تجربه دیگران
              کمک کنند.
            </p>
            <a :href="bookingUrl" class="btn btn-primary"> دریافت نوبت <span>←</span> </a>
          </div>

          <div class="reviews-content">
            <article v-for="review in testimonials" :key="review.id" class="review-card">
              <div class="review-stars" aria-label="امتیاز بیمار">★★★★★</div>
              <p>{{ review.text }}</p>
              <strong>{{ review.name }}</strong>
            </article>

            <div v-if="!testimonials.length" class="reviews-empty">
              <span class="review-quote">“</span>
              <h3>صدای مراجعه‌کنندگان</h3>
              <p>
                دیدگاه‌های تأییدشده بیماران پس از اتصال این بخش به سامانه نظرات در این
                قسمت نمایش داده خواهند شد.
              </p>
              <span class="empty-line"></span>
              <small>نظرات واقعی، بدون امتیاز یا نقل‌قول ساختگی</small>
            </div>
          </div>
        </div>
      </section>

      <!-- Working hours -->
      <section id="hours" class="section hours-section">
        <div class="container hours-grid">
          <div class="hours-content">
            <span class="section-kicker">VISITING HOURS</span>
            <h2 class="section-title">برنامه مراجعه به مطب</h2>
            <p class="section-text">
              برای جلوگیری از مراجعه در زمان نامناسب، پیش از حرکت برنامه کاری و نوبت‌های
              فعال را بررسی کنید.
            </p>

            <div class="hours-list">
              <div v-for="item in workingHours" :key="item.day" class="hours-row">
                <span>{{ item.day }}</span>
                <strong>{{ item.time }}</strong>
              </div>
            </div>

            <p class="hours-note">
              <span>ⓘ</span>
              ساعات دقیق هر مطب باید با برنامه تأییدشده پزشک تکمیل شود.
            </p>

            <a :href="bookingUrl" class="btn btn-primary">
              بررسی نوبت‌های فعال <span>←</span>
            </a>
          </div>

          <div class="hours-visual">
            <div class="hours-circle circle-back"></div>
            <div class="hours-main-card">
              <span class="hours-symbol">◷</span>
              <span class="hours-small">YOUR APPOINTMENT</span>
              <h3>زمان مناسب خود را انتخاب کنید.</h3>
              <p>نوبت‌های قابل انتخاب را در سامانه مشاهده کنید.</p>
              <a :href="bookingUrl" class="hours-card-link">
                ورود به نوبت‌دهی <span>←</span>
              </a>
            </div>
            <span class="hours-deco deco-one">+</span>
            <span class="hours-deco deco-two">✳</span>
          </div>
        </div>
      </section>

      <!-- Location -->
      <section id="location" class="section location-section">
        <div class="container">
          <div class="section-heading">
            <div>
              <span class="section-kicker">CLINIC LOCATION</span>
              <h2 class="section-title">آدرس مطب و مسیر دسترسی</h2>
              <p class="section-text">
                مطب موردنظر خود را انتخاب کنید و مسیر را روی نقشه ببینید.
              </p>
            </div>
          </div>

          <div class="location-grid">
            <div class="clinic-list">
              <button
                v-for="(clinic, index) in clinics"
                :key="clinic.city"
                type="button"
                class="clinic-card"
                :class="{ 'clinic-active': activeClinic === index }"
                @click="activeClinic = index"
              >
                <span class="clinic-icon">⌖</span>
                <span class="clinic-info">
                  <strong>مطب {{ clinic.city }}</strong>
                  <small>{{ clinic.address }}</small>
                  <span class="clinic-phone">{{ clinic.phone }}</span>
                </span>
                <span class="clinic-arrow">←</span>
              </button>

              <a
                :href="clinics[activeClinic].map"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-primary map-button"
              >
                مسیریابی مطب {{ clinics[activeClinic].city }}
                <span>↗</span>
              </a>
            </div>

            <div class="map-frame">
              <iframe
                :src="`https://maps.google.com/maps?q=${encodeURIComponent(
                  clinics[activeClinic].address
                )}&output=embed`"
                :title="`نقشه مطب ${clinics[activeClinic].city}`"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
              ></iframe>
              <div class="map-caption">
                <span class="map-pin">⌖</span>
                <span>
                  <strong>مطب {{ clinics[activeClinic].city }}</strong>
                  <small>برای مسیریابی دقیق، موقعیت مطب را بررسی کنید.</small>
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Articles -->
      <section id="articles" class="section articles-section">
        <div class="container">
          <div class="section-heading">
            <div>
              <span class="section-kicker">HEALTH JOURNAL</span>
              <h2 class="section-title">دانش بیشتر، انتخاب آگاهانه‌تر</h2>
              <p class="section-text">
                مطالب آموزشی برای آشنایی بیشتر با مراقبت‌های سلامت.
              </p>
            </div>
            <a href="/blog" class="text-link"> همه مقالات <span>←</span> </a>
          </div>

          <div class="articles-grid">
            <article
              v-for="article in articles"
              :key="article.title"
              class="article-card"
            >
              <a href="/blog" class="article-image">
                <img :src="article.image" :alt="article.title" loading="lazy" />
                <span class="article-category">{{ article.category }}</span>
              </a>
              <div class="article-body">
                <h3>{{ article.title }}</h3>
                <a href="/blog" class="article-link"> مطالعه مطلب <span>←</span> </a>
              </div>
            </article>
          </div>

          <p class="disclaimer">
            مطالب این قسمت نمونه هستند و باید با مقالات واقعی و تأییدشده پزشک جایگزین
            شوند. محتوای آموزشی جایگزین معاینه و مشاوره پزشکی نیست.
          </p>
        </div>
      </section>

      <!-- Contact CTA -->
      <section id="contact" class="contact-section">
        <div class="container contact-box">
          <div>
            <span class="contact-kicker">TAKE THE NEXT STEP</span>
            <h2>برای مراجعه خود برنامه‌ریزی کنید.</h2>
            <p>
              اطلاعات مطب را بررسی کنید و برای انتخاب زمان مناسب به سامانه نوبت‌دهی بروید.
            </p>
          </div>
          <div class="contact-actions">
            <a :href="route('booking')" class="btn btn-white">
              دریافت نوبت آنلاین <span>←</span>
            </a>
            <a href="tel:09151882237" class="contact-phone"> تماس با مطب: 09151882237 </a>
          </div>
          <span class="contact-decoration">+</span>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container footer-main">
        <a href="#home" class="brand footer-brand">
          <span class="brand-icon">+</span>
          <span>
            <strong>دکتر فرهاد وفایی</strong>
            <small>متخصص جراحی عمومی</small>
          </span>
        </a>

        <p>اطلاع‌رسانی، آموزش سلامت و نوبت‌دهی مراجعه‌کنندگان</p>

        <div class="footer-links">
          <a href="#about">درباره پزشک</a>
          <a href="#location">آدرس مطب</a>
          <a href="#articles">مقالات</a>
          <a :href="bookingUrl">نوبت‌دهی</a>
        </div>
      </div>

      <div class="footer-bottom">
        <div class="container footer-bottom-inner">
          <span>© تمامی حقوق محفوظ است.</span>
          <a href="#home">بازگشت به بالا ↑</a>
        </div>
      </div>
    </footer>

    <!-- Floating booking button -->
    <a
                    :href="route('booking')"

      class="floating-booking"
      aria-label="دریافت نوبت آنلاین"
      title="دریافت نوبت"
    >
      <span class="floating-booking-icon">▦</span>
      <span>نوبت</span>
    </a>

    <!-- Theme preview: remove this panel in production if not needed -->
    <div class="theme-switcher" aria-label="انتخاب رنگ سایت">
      <button
        v-for="(value, key) in themes"
        :key="key"
        type="button"
        :style="{ backgroundColor: value.primary }"
        :class="{ selected: themeColor === key }"
        :aria-label="`انتخاب تم ${key}`"
        :title="key"
        @click="themeColor = key"
      ></button>
    </div>
  </div>
</template>

<style>
@import url("https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap");

.site {
  --primary: #c65f79;
  --primary-dark: #87384f;
  --primary-light: #fcecf1;
  --primary-soft: #fff6f8;

  font-family: "Vazirmatn", Tahoma, sans-serif;
  color: #29262b;
  background: #fff;
  line-height: 1.9;
  overflow: clip;
}

.site *,
.site *::before,
.site *::after {
  box-sizing: border-box;
}

.site a {
  color: inherit;
  text-decoration: none;
}

.site button {
  font: inherit;
}

.container {
  width: min(1160px, calc(100% - 48px));
  margin-inline: auto;
}

.header {
  background: rgba(255, 255, 255, 0.96);
  border-bottom: 1px solid #f0edef;
  position: relative;
  z-index: 10;
}

.nav {
  min-height: 82px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 28px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.brand-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  border-radius: 15px;
  color: white;
  background: var(--primary);
  font-size: 29px;
  font-weight: 400;
  line-height: 1;
}

.brand strong,
.brand small {
  display: block;
}

.brand strong {
  font-size: 14px;
  font-weight: 800;
}

.brand small {
  color: #888087;
  font-size: 11px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 23px;
  font-size: 12px;
  color: #686169;
}

.nav-links a,
.footer-links a {
  transition: color 0.2s ease;
}

.nav-links a:hover,
.footer-links a:hover {
  color: var(--primary);
}

.btn {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  border-radius: 13px;
  padding: 12px 21px;
  font-size: 13px;
  font-weight: 700;
  transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn-primary {
  color: #fff !important;
  background: var(--primary);
  box-shadow: 0 8px 22px color-mix(in srgb, var(--primary) 18%, transparent);
}

.btn-primary:hover {
  background: var(--primary-dark);
}

.btn-outline {
  border: 1px solid #e6dfe2;
  color: #514950;
  background: #fff;
}

.btn-large {
  min-height: 53px;
  padding-inline: 25px;
}

.hero {
  position: relative;
  background: radial-gradient(ellipse at 78% 38%, var(--primary-soft), transparent 39%),
    linear-gradient(120deg, #fff 45%, #fdf9fa 100%);
}

.hero-grid {
  display: grid;
  grid-template-columns: 1fr 0.95fr;
  align-items: center;
  gap: 70px;
  min-height: 555px;
  padding-top: 30px;
  padding-bottom: 35px;
}

.hero-content {
  padding-block: 30px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 7px 13px;
  border-radius: 30px;
  background: var(--primary-light);
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 600;
}

.pulse {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 0 5px color-mix(in srgb, var(--primary) 12%, transparent);
}

.hero h1 {
  font-size: clamp(35px, 4.2vw, 54px);
  font-weight: 800;
  line-height: 1.55;
  letter-spacing: -1.5px;
  margin: 23px 0 12px;
}

.hero h1 span,
.section-title span {
  color: var(--primary);
}

.hero-description {
  color: #777079;
  font-size: 14px;
  max-width: 475px;
  line-height: 2.2;
  margin-bottom: 27px;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-note {
  margin-top: 24px;
  display: flex;
  align-items: center;
  gap: 9px;
  color: #777079;
  font-size: 11px;
}

.note-icon {
  width: 21px;
  height: 21px;
  display: grid;
  place-items: center;
  color: var(--primary);
  background: var(--primary-light);
  border-radius: 50%;
  font-size: 12px;
}

.hero-visual {
  position: relative;
  height: 430px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-orb {
  position: absolute;
  width: min(390px, 90%);
  aspect-ratio: 1;
  border-radius: 50%;
  background: var(--primary-light);
  opacity: 0.8;
}

.doctor-photo-wrap {
  position: relative;
  z-index: 1;
  width: 310px;
  height: 375px;
  border-radius: 160px 160px 28px 28px;
  overflow: hidden;
  background: linear-gradient(160deg, var(--primary-light), #f5e4e9);
  border: 7px solid rgba(255, 255, 255, 0.88);
  box-shadow: 0 28px 65px rgba(80, 48, 58, 0.12);
}

.doctor-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  position: relative;
  z-index: 2;
}

.photo-fallback {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: var(--primary-dark);
}

.photo-fallback span {
  font-size: 16px;
}

.photo-fallback strong {
  font-size: 23px;
}

.floating-card {
  position: absolute;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 13px 15px;
  background: white;
  border: 1px solid #f3edf0;
  border-radius: 16px;
  box-shadow: 0 12px 35px rgba(65, 44, 52, 0.09);
}

.floating-card strong,
.floating-card small {
  display: block;
}

.floating-card strong {
  font-size: 11px;
}

.floating-card small {
  color: #8b8389;
  font-size: 9px;
}

.float-icon {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: var(--primary);
  background: var(--primary-light);
  font-size: 20px;
}

.floating-card-top {
  top: 85px;
  right: -8px;
}

.floating-card-bottom {
  bottom: 55px;
  left: -14px;
}

.decorative-circle {
  position: absolute;
  border-radius: 50%;
  border: 1px solid color-mix(in srgb, var(--primary) 25%, transparent);
}

.circle-one {
  width: 30px;
  height: 30px;
  top: 53px;
  left: 55px;
}

.circle-two {
  width: 13px;
  height: 13px;
  right: 30px;
  bottom: 55px;
  background: var(--primary);
}

.hero-bottom {
  border-top: 1px solid #f0e9ec;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 57px;
}

.scroll-link {
  display: flex;
  align-items: center;
  gap: 9px;
  color: #837982;
  font-size: 10px;
}

.scroll-icon {
  color: var(--primary);
  font-size: 19px;
}

.hero-caption {
  color: #a0989f;
  font-size: 10px;
}

.quick-section {
  padding-block: 28px;
  background: #fff;
}

.quick-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  border: 1px solid #f0e9ec;
  border-radius: 20px;
  overflow: hidden;
}

.quick-item {
  min-height: 92px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 17px;
  transition: background 0.2s;
}

.quick-item + .quick-item {
  border-right: 1px solid #f0e9ec;
}

.quick-item:hover {
  background: var(--primary-soft);
}

.quick-icon {
  flex-shrink: 0;
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  color: var(--primary);
  background: var(--primary-light);
  border-radius: 14px;
  font-size: 22px;
}

.quick-item strong,
.quick-item small {
  display: block;
}

.quick-item strong {
  font-size: 12px;
}

.quick-item small {
  color: #938a91;
  font-size: 10px;
  margin-top: 3px;
}

.quick-arrow {
  margin-right: auto;
  color: var(--primary);
}

.section {
  padding-block: 90px;
  scroll-margin-top: 30px;
}

.about-grid {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  align-items: center;
  gap: 100px;
}

.about-visual {
  position: relative;
  max-width: 400px;
  margin-inline: auto;
  width: 100%;
}

.about-image-frame {
  height: 430px;
  border-radius: 180px 180px 25px 25px;
  background: var(--primary-light);
  border: 8px solid #fff;
  outline: 1px solid #f1e3e8;
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.about-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: top center;
}

.about-label {
  position: absolute;
  bottom: 28px;
  right: -35px;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 13px 17px;
  border-radius: 15px;
  background: white;
  box-shadow: 0 12px 35px rgba(70, 45, 55, 0.12);
}

.label-plus {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: var(--primary);
  color: white;
  display: grid;
  place-items: center;
  font-size: 25px;
}

.about-label strong,
.about-label small {
  display: block;
}

.about-label strong {
  font-size: 12px;
}

.about-label small {
  color: #8a8188;
  font-size: 10px;
}

.about-decoration {
  position: absolute;
  width: 100px;
  height: 100px;
  border: 1px solid var(--primary);
  opacity: 0.25;
  bottom: -18px;
  left: -22px;
  border-radius: 28px;
}

.section-kicker {
  color: var(--primary);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1.5px;
  direction: ltr;
  display: inline-block;
}

.section-title {
  font-size: clamp(24px, 3vw, 34px);
  line-height: 1.8;
  font-weight: 800;
  margin: 8px 0 13px;
}

.section-text {
  font-size: 12px;
  color: #807780;
  line-height: 2.3;
  margin: 0;
}

.about-facts {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 22px 18px;
  margin-block: 28px;
}

.fact {
  display: flex;
  align-items: center;
  gap: 12px;
}

.fact-icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  color: var(--primary);
  background: var(--primary-light);
  border-radius: 13px;
  font-size: 20px;
}

.fact strong,
.fact small {
  display: block;
}

.fact strong {
  font-size: 12px;
}

.fact small {
  color: #8c838a;
  font-size: 10px;
  margin-top: 3px;
}

.text-link {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  font-size: 12px;
  font-weight: 700;
  color: var(--primary) !important;
}

.text-link span {
  font-size: 18px;
}

.services-section {
  background: #faf8f9;
}

.section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 35px;
}

.section-heading .section-title {
  margin-bottom: 4px;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
}

.service-card {
  background: #fff;
  border: 1px solid #f0e9ec;
  padding: 24px 20px;
  border-radius: 18px;
  transition: transform 0.25s, box-shadow 0.25s;
}

.service-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 18px 40px rgba(70, 45, 55, 0.07);
}

.service-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.service-icon {
  width: 53px;
  height: 53px;
  display: grid;
  place-items: center;
  background: var(--primary-light);
  color: var(--primary);
  border-radius: 16px;
  font-size: 26px;
}

.service-number {
  color: #d6cbd0;
  font-size: 12px;
  direction: ltr;
}

.service-card h3 {
  font-size: 15px;
  margin: 19px 0 7px;
}

.service-card p {
  color: #8c838a;
  font-size: 11px;
  line-height: 2.1;
  min-height: 47px;
}

.service-link {
  color: var(--primary);
  font-size: 11px;
  font-weight: 700;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #f2ecef;
  padding-top: 13px;
  margin-top: 16px;
}

.disclaimer {
  margin-top: 23px;
  color: #968d94;
  font-size: 10px;
  line-height: 2;
}

.gallery-grid {
  display: grid;
  grid-template-columns: 1.25fr 1fr 1fr;
  gap: 18px;
}

.gallery-card {
  height: 280px;
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  background: var(--primary-light);
}

.gallery-card-large {
  height: 320px;
}

.gallery-card img,
.article-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s;
}

.gallery-card:hover img,
.article-card:hover img {
  transform: scale(1.05);
}

.gallery-overlay {
  position: absolute;
  inset: auto 0 0;
  padding: 38px 20px 17px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: white;
  font-size: 13px;
  font-weight: 700;
  background: linear-gradient(transparent, rgba(25, 18, 22, 0.75));
}

.gallery-plus {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: white;
  color: var(--primary);
}

.reviews-section {
  background: var(--primary-soft);
}

.reviews-layout {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  gap: 75px;
  align-items: center;
}

.reviews-intro .section-title {
  max-width: 400px;
}

.reviews-intro .section-text {
  margin-bottom: 25px;
}

.reviews-content {
  min-width: 0;
}

.reviews-empty {
  background: #fff;
  border: 1px solid #f0e4e9;
  padding: 32px;
  border-radius: 22px;
  box-shadow: 0 14px 45px rgba(90, 50, 65, 0.04);
}

.review-quote {
  color: var(--primary);
  font-family: Georgia, serif;
  font-size: 70px;
  line-height: 1;
}

.reviews-empty h3 {
  margin: 5px 0;
  font-size: 18px;
}

.reviews-empty p {
  color: #847a82;
  font-size: 12px;
  line-height: 2.3;
}

.empty-line {
  display: block;
  width: 45px;
  height: 3px;
  border-radius: 5px;
  background: var(--primary);
  margin-block: 19px;
}

.reviews-empty small {
  color: #9a9097;
  font-size: 10px;
}

.review-card {
  background: white;
  border-radius: 18px;
  padding: 25px;
  margin-bottom: 15px;
}

.review-stars {
  color: #d4a044;
}

.review-card p {
  font-size: 12px;
  color: #786f76;
}

.review-card strong {
  font-size: 12px;
}

.hours-grid {
  display: grid;
  grid-template-columns: 1fr 0.9fr;
  gap: 80px;
  align-items: center;
}

.hours-list {
  margin-block: 25px;
  border-top: 1px solid #f0e9ec;
}

.hours-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding-block: 14px;
  border-bottom: 1px solid #f0e9ec;
  font-size: 12px;
}

.hours-row strong {
  font-size: 11px;
  color: var(--primary-dark);
}

.hours-note {
  display: flex;
  gap: 9px;
  color: #8c838a;
  font-size: 10px;
  margin-bottom: 20px;
}

.hours-note span {
  color: var(--primary);
}

.hours-visual {
  min-height: 390px;
  position: relative;
  display: grid;
  place-items: center;
}

.hours-circle {
  position: absolute;
  width: 340px;
  height: 340px;
  border-radius: 50%;
  background: var(--primary-light);
}

.hours-main-card {
  position: relative;
  width: min(340px, 100%);
  padding: 34px 29px;
  border-radius: 25px;
  background: white;
  border: 1px solid #f1e7eb;
  box-shadow: 0 22px 65px rgba(65, 45, 55, 0.09);
}

.hours-symbol {
  display: grid;
  place-items: center;
  width: 57px;
  height: 57px;
  background: var(--primary-light);
  border-radius: 18px;
  color: var(--primary);
  font-size: 29px;
}

.hours-small {
  display: block;
  color: var(--primary);
  font-size: 9px;
  letter-spacing: 1.4px;
  margin-top: 20px;
}

.hours-main-card h3 {
  font-size: 22px;
  line-height: 1.8;
  margin: 7px 0;
}

.hours-main-card p {
  font-size: 11px;
  color: #877d85;
}

.hours-card-link {
  color: var(--primary) !important;
  display: flex;
  justify-content: space-between;
  border-top: 1px solid #f1e9ed;
  padding-top: 16px;
  margin-top: 20px;
  font-size: 12px;
  font-weight: 700;
}

.hours-deco {
  position: absolute;
  color: var(--primary);
  z-index: 2;
}

.deco-one {
  top: 40px;
  right: 20px;
  font-size: 42px;
}

.deco-two {
  bottom: 35px;
  left: 20px;
  font-size: 29px;
}

.location-section {
  background: #faf8f9;
}

.location-grid {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  gap: 22px;
}

.clinic-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.clinic-card {
  width: 100%;
  display: flex;
  align-items: flex-start;
  gap: 13px;
  text-align: right;
  padding: 18px;
  background: white;
  border: 1px solid #eee6ea;
  border-radius: 16px;
  cursor: pointer;
  transition: border 0.2s, background 0.2s;
}

.clinic-active {
  border-color: var(--primary);
  background: var(--primary-soft);
}

.clinic-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 13px;
  color: var(--primary);
  background: var(--primary-light);
  font-size: 24px;
}

.clinic-info {
  flex: 1;
}

.clinic-info strong,
.clinic-info small,
.clinic-phone {
  display: block;
}

.clinic-info strong {
  font-size: 13px;
}

.clinic-info small {
  font-size: 10px;
  color: #8b8289;
  line-height: 2;
  margin-block: 5px;
}

.clinic-phone {
  font-size: 11px;
  direction: ltr;
  text-align: right;
  color: var(--primary-dark);
}

.clinic-arrow {
  color: var(--primary);
}

.map-button {
  align-self: flex-start;
  margin-top: 5px;
}

.map-frame {
  min-height: 360px;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  background: var(--primary-light);
  border: 1px solid #f0e5e9;
}

.map-frame iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
}

.map-caption {
  position: absolute;
  right: 15px;
  bottom: 15px;
  left: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 13px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
  pointer-events: none;
}

.map-pin {
  width: 37px;
  height: 37px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: var(--primary);
  background: var(--primary-light);
  font-size: 22px;
}

.map-caption strong,
.map-caption small {
  display: block;
}

.map-caption strong {
  font-size: 12px;
}

.map-caption small {
  color: #8b8289;
  font-size: 9px;
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.article-card {
  border: 1px solid #f0e9ec;
  border-radius: 17px;
  overflow: hidden;
  background: white;
  transition: box-shadow 0.25s, transform 0.25s;
}

.article-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 35px rgba(70, 45, 55, 0.07);
}

.article-image {
  display: block;
  height: 200px;
  position: relative;
  overflow: hidden;
  background: var(--primary-light);
}

.article-category {
  position: absolute;
  right: 13px;
  bottom: 13px;
  border-radius: 8px;
  padding: 5px 10px;
  color: var(--primary-dark);
  background: white;
  font-size: 9px;
  font-weight: 700;
}

.article-body {
  padding: 17px;
}

.article-body h3 {
  min-height: 52px;
  font-size: 13px;
  line-height: 2;
  margin: 0 0 13px;
}

.article-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #f1e9ed;
  padding-top: 12px;
  color: var(--primary) !important;
  font-size: 11px;
  font-weight: 700;
}

.contact-section {
  padding-block: 30px 75px;
}

.contact-box {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 35px;
  overflow: hidden;
  padding: 45px;
  border-radius: 25px;
  color: white;
  background: linear-gradient(115deg, var(--primary-dark), var(--primary));
}

.contact-kicker {
  font-size: 9px;
  letter-spacing: 1.4px;
  opacity: 0.75;
}

.contact-box h2 {
  font-size: 26px;
  line-height: 1.7;
  margin: 6px 0;
}

.contact-box p {
  max-width: 560px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 11px;
}

.contact-actions {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-white {
  background: white;
  color: var(--primary-dark) !important;
}

.contact-phone {
  font-size: 11px;
  color: white !important;
  direction: ltr;
}

.contact-decoration {
  position: absolute;
  left: 25%;
  top: -100px;
  color: rgba(255, 255, 255, 0.08);
  font-size: 260px;
  line-height: 1;
  pointer-events: none;
}

.footer {
  border-top: 1px solid #f0e9ec;
  background: #fff;
}

.footer-main {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 25px;
  padding-block: 28px;
}

.footer-main > p {
  color: #8d848b;
  font-size: 10px;
}

.footer-links {
  display: flex;
  flex-wrap: wrap;
  gap: 17px;
  color: #746b73;
  font-size: 10px;
}

.footer-bottom {
  border-top: 1px solid #f2edef;
}

.footer-bottom-inner {
  display: flex;
  justify-content: space-between;
  padding-block: 14px;
  color: #9b9299;
  font-size: 10px;
}

.footer-bottom-inner a {
  color: var(--primary);
}

/* Floating booking button */
.floating-booking {
  position: fixed;
  z-index: 100;
  right: 25px;
  bottom: 25px;
  width: 72px;
  height: 72px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  color: white !important;
  background: var(--primary);
  border: 4px solid rgba(255, 255, 255, 0.94);
  border-radius: 50%;
  box-shadow: 0 8px 30px color-mix(in srgb, var(--primary) 36%, transparent);
  font-size: 12px;
  font-weight: 800;
  transition: transform 0.2s, background 0.2s;
}

.floating-booking:hover {
  transform: translateY(-4px) scale(1.04);
  background: var(--primary-dark);
}

.floating-booking-icon {
  font-size: 21px;
  line-height: 1.2;
}

/* Live theme preview */
.theme-switcher {
  position: fixed;
  z-index: 101;
  bottom: 34px;
  left: 20px;
  display: flex;
  gap: 7px;
  padding: 9px;
  border: 1px solid #eee6ea;
  border-radius: 25px;
  background: white;
  box-shadow: 0 5px 20px rgba(30, 20, 25, 0.08);
}

.theme-switcher button {
  width: 17px;
  height: 17px;
  border: 2px solid white;
  border-radius: 50%;
  outline: 1px solid transparent;
  cursor: pointer;
}

.theme-switcher button.selected {
  outline-color: #63565d;
  transform: scale(1.15);
}

@media (max-width: 1050px) {
  .nav-links {
    gap: 12px;
  }

  .hero-grid {
    gap: 30px;
  }

  .about-grid {
    gap: 55px;
  }

  .quick-item {
    padding: 12px;
  }

  .quick-icon {
    width: 37px;
    height: 37px;
  }

  .services-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .reviews-layout,
  .hours-grid {
    gap: 40px;
  }
}

@media (max-width: 760px) {
  .container {
    width: calc(100% - 32px);
  }

  .nav {
    min-height: 70px;
    gap: 12px;
  }

  .nav-links {
    display: none;
  }

  .header-cta {
    padding: 10px 12px;
    font-size: 11px;
  }

  .hero-grid {
    grid-template-columns: 1fr;
    gap: 0;
    padding-top: 25px;
  }

  .hero-content {
    padding-block: 15px 5px;
  }

  .hero h1 {
    font-size: 38px;
  }

  .hero-description {
    font-size: 12px;
  }

  .hero-visual {
    height: 350px;
    margin-top: 5px;
  }

  .doctor-photo-wrap {
    height: 305px;
    width: 250px;
  }

  .hero-orb {
    width: 310px;
  }

  .floating-card-top {
    top: 45px;
    right: 0;
  }

  .floating-card-bottom {
    bottom: 25px;
    left: 0;
  }

  .hero-bottom {
    min-height: 49px;
  }

  .hero-caption {
    display: none;
  }

  .quick-section {
    padding-block: 16px;
  }

  .quick-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-item {
    min-height: 78px;
  }

  .quick-item + .quick-item {
    border-right: 0;
  }

  .quick-item:nth-child(even) {
    border-right: 1px solid #f0e9ec;
  }

  .quick-item:nth-child(n + 3) {
    border-top: 1px solid #f0e9ec;
  }

  .quick-item strong {
    font-size: 11px;
  }

  .quick-item small {
    font-size: 9px;
  }

  .section {
    padding-block: 62px;
  }

  .about-grid,
  .reviews-layout,
  .hours-grid,
  .location-grid {
    grid-template-columns: 1fr;
    gap: 35px;
  }

  .about-visual {
    max-width: 330px;
  }

  .about-image-frame {
    height: 350px;
  }

  .about-label {
    right: -5px;
  }

  .about-decoration {
    left: -5px;
  }

  .section-title {
    font-size: 26px;
  }

  .about-facts {
    gap: 18px 10px;
  }

  .fact {
    gap: 8px;
  }

  .fact small {
    font-size: 9px;
  }

  .section-heading {
    align-items: flex-start;
  }

  .section-heading > .text-link {
    flex-shrink: 0;
    margin-top: 22px;
    font-size: 10px;
  }

  .services-grid {
    gap: 12px;
  }

  .service-card {
    padding: 17px 13px;
  }

  .service-card h3 {
    font-size: 13px;
  }

  .service-card p {
    font-size: 10px;
  }

  .gallery-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .gallery-card,
  .gallery-card-large {
    height: 200px;
  }

  .gallery-card-large {
    grid-column: 1 / -1;
    height: 245px;
  }

  .reviews-empty {
    padding: 24px;
  }

  .hours-visual {
    min-height: 340px;
  }

  .hours-circle {
    width: 290px;
    height: 290px;
  }

  .hours-main-card {
    width: min(320px, 100%);
    padding: 25px;
  }

  .map-frame {
    min-height: 300px;
  }

  .articles-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .article-card {
    display: grid;
    grid-template-columns: 120px 1fr;
  }

  .article-image {
    height: 100%;
    min-height: 155px;
  }

  .article-category {
    right: 7px;
    bottom: 7px;
    font-size: 8px;
    padding: 3px 6px;
  }

  .article-body {
    padding: 14px;
  }

  .article-body h3 {
    font-size: 11px;
    min-height: auto;
  }

  .contact-box {
    padding: 27px 22px;
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .contact-box h2 {
    font-size: 22px;
  }

  .contact-actions {
    align-items: flex-start;
  }

  .footer-main {
    flex-wrap: wrap;
    gap: 18px;
  }

  .footer-main > p {
    width: 100%;
    margin: 0;
  }

  .footer-links {
    gap: 12px;
  }

  .floating-booking {
    width: 63px;
    height: 63px;
    right: 14px;
    bottom: 15px;
    font-size: 11px;
  }

  .theme-switcher {
    bottom: 22px;
    left: 10px;
    gap: 5px;
    padding: 7px;
  }

  .theme-switcher button {
    width: 14px;
    height: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .site *,
  .site *::before,
  .site *::after {
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
  }
}
</style>

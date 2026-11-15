<script setup>
import { computed, ref } from "vue";

/*
|--------------------------------------------------------------------------
| تنظیم رنگ اصلی سایت
|--------------------------------------------------------------------------
| فقط این مقدار را تغییر دهید:
| rose | teal | blue | violet | emerald
*/
const theme = ref("green");

const themes = {
  rose: {
    primary: "#e65378",
    dark: "#c9345d",
    soft: "#fff0f4",
    pale: "#fff8fa",
    rgb: "230, 83, 120",
  },
  teal: {
    primary: "#0d9488",
    dark: "#0f766e",
    soft: "#e6fffb",
    pale: "#f4fffd",
    rgb: "13, 148, 136",
  },
  blue: {
    primary: "#4779e8",
    dark: "#315bc0",
    soft: "#edf3ff",
    pale: "#f7f9ff",
    rgb: "71, 121, 232",
  },
  violet: {
    primary: "#8b5cf6",
    dark: "#7040d0",
    soft: "#f2ecff",
    pale: "#faf7ff",
    rgb: "139, 92, 246",
  },
  emerald: {
    primary: "#159b72",
    dark: "#087653",
    soft: "#e5fbf2",
    pale: "#f5fffa",
    rgb: "21, 155, 114",
  },
};

const cssVars = computed(() => {
  const c = themes[theme.value] || themes.rose;

  return {
    "--primary": c.primary,
    "--primary-dark": c.dark,
    "--primary-soft": c.soft,
    "--primary-pale": c.pale,
    "--primary-rgb": c.rgb,
  };
});

const mobileMenu = ref(false);

const services = [
  {
    icon: "✳",
    title: "مشاوره پزشکی",
    description: "بررسی شرایط و پاسخ به پرسش‌های شما",
  },
  {
    icon: "◈",
    title: "ویزیت و معاینه",
    description: "انتخاب زمان مناسب برای مراجعه",
  },
  {
    icon: "♡",
    title: "پیگیری و مراقبت",
    description: "دسترسی آسان‌تر به اطلاعات ویزیت",
  },
];

const appointmentUrl = "#booking";
</script>

<template>
  <main class="site" dir="rtl" :style="cssVars">
    <!-- Header -->
    <header class="header">
      <div class="container nav">
        <a href="#" class="brand" aria-label="صفحه اصلی">
          <span class="brand-symbol">
            <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
              <rect x="2" y="2" width="36" height="36" rx="13" fill="currentColor" />
              <path
                d="M20 11V29M11 20H29"
                stroke="white"
                stroke-width="3.5"
                stroke-linecap="round"
              />
            </svg>
          </span>

          <span class="brand-copy">
            <strong>دکتر فرهاد وفایی</strong>
            <small>همراه شما در مسیر سلامتی</small>
          </span>
        </a>

        <nav class="desktop-nav" aria-label="منوی اصلی">
          <a href="#home" class="nav-link active">صفحه اصلی</a>
          <a href="#about" class="nav-link">درباره پزشک</a>
          <a href="#services" class="nav-link">خدمات</a>
          <a href="#contact" class="nav-link">ارتباط با ما</a>
        </nav>

        <a :href="appointmentUrl" class="button button-primary header-cta">
          <span>دریافت نوبت</span>
          <span aria-hidden="true">←</span>
        </a>

        <button
          class="menu-toggle"
          type="button"
          :aria-expanded="mobileMenu"
          aria-label="نمایش منو"
          @click="mobileMenu = !mobileMenu"
        >
          <span></span>
          <span></span>
        </button>
      </div>

      <nav v-if="mobileMenu" class="mobile-nav container">
        <a href="#home" @click="mobileMenu = false">صفحه اصلی</a>
        <a href="#about" @click="mobileMenu = false">درباره پزشک</a>
        <a href="#services" @click="mobileMenu = false">خدمات</a>
        <a href="#contact" @click="mobileMenu = false">ارتباط با ما</a>
        <a :href="appointmentUrl" @click="mobileMenu = false">دریافت نوبت</a>
      </nav>
    </header>

    <!-- Hero -->
    <section id="home" class="hero">
      <div class="hero-orb orb-one"></div>
      <div class="hero-orb orb-two"></div>

      <div class="container hero-grid">
        <div class="hero-copy">
          <div class="eyebrow">
            <span class="status-dot"></span>
            نوبت‌گیری آسان، در چند قدم
          </div>

          <h1>
            برای سلامتی خود،
            <span class="accent-text">وقت بگذارید.</span>
          </h1>

          <p class="hero-description">
            برای دریافت نوبت، مشاهده اطلاعات پزشک و پیگیری خدمات، مسیر ساده‌تری پیش روی
            شماست. زمان مناسب خود را انتخاب کنید و با اطمینان بیشتری برای مراجعه
            برنامه‌ریزی کنید.
          </p>

          <div class="hero-actions">
            <a :href="appointmentUrl" class="button button-primary button-large">
              <span>همین حالا نوبت بگیرید</span>
              <span class="button-arrow" aria-hidden="true">←</span>
            </a>

            <a href="#about" class="text-link">
              آشنایی با پزشک
              <span aria-hidden="true">↙</span>
            </a>
          </div>

          <div class="hero-note">
            <span class="note-icon">✓</span>
            <span>فرایند ساده و آنلاین برای ثبت درخواست نوبت</span>
          </div>
        </div>

        <!-- تصویر نمونه؛ با تصویر واقعی پزشک جایگزین شود -->
        <div class="hero-visual">
          <div class="visual-backdrop"></div>

          <div class="portrait-frame">
            <img
              src="\storage\doctors\Dr.Farhad.Vafaei.jpg"
              alt="تصویر نمونه پزشک؛ قابل جایگزینی با عکس دکتر فرهاد وفایی"
              class="doctor-image"
            />

            <div class="portrait-overlay">
              <div class="portrait-icon">✚</div>
              <div>
                <strong>دکتر فرهاد وفایی</strong>
                <span>پزشک شما، همراه شما</span>
              </div>
            </div>
          </div>

          <div class="floating-card floating-top">
            <span class="floating-icon">♡</span>
            <div>
              <strong>سلامتی شما</strong>
              <span>اولویت ماست</span>
            </div>
          </div>

          <a :href="appointmentUrl" class="floating-card floating-bottom">
            <span class="calendar-icon">▦</span>
            <div>
              <strong>برای خودتان وقت بگیرید</strong>
              <span>شروع نوبت‌گیری آنلاین</span>
            </div>
            <span class="floating-arrow">←</span>
          </a>

          <div class="decorative-circle circle-one"></div>
          <div class="decorative-circle circle-two"></div>
        </div>
      </div>

      <div class="container hero-bottom">
        <div class="bottom-caption">
          <span class="bottom-line"></span>
          <span>شروع یک تجربه ساده‌تر برای دریافت خدمات پزشکی</span>
        </div>

        <a href="#services" class="scroll-link">
          آشنایی با خدمات
          <span>↓</span>
        </a>
      </div>
    </section>

    <!-- Quick access -->
    <section class="quick-access">
      <div class="container quick-grid">
        <a :href="appointmentUrl" class="quick-item">
          <span class="quick-icon">▦</span>
          <span class="quick-text">
            <strong>دریافت نوبت</strong>
            <small>انتخاب زمان مراجعه</small>
          </span>
          <span class="quick-arrow">←</span>
        </a>

        <a href="#about" class="quick-item">
          <span class="quick-icon">♙</span>
          <span class="quick-text">
            <strong>معرفی پزشک</strong>
            <small>اطلاعات و آشنایی بیشتر</small>
          </span>
          <span class="quick-arrow">←</span>
        </a>

        <a href="#contact" class="quick-item">
          <span class="quick-icon">⌕</span>
          <span class="quick-text">
            <strong>راه‌های ارتباطی</strong>
            <small>اطلاعات تماس و مراجعه</small>
          </span>
          <span class="quick-arrow">←</span>
        </a>
      </div>
    </section>

    <!-- About -->
    <section id="about" class="section about-section">
      <div class="container about-grid">
        <div class="section-heading">
          <span class="section-kicker">درباره پزشک</span>

          <h2>
            انتخاب آگاهانه،
            <span class="accent-text">شروع بهتر.</span>
          </h2>

          <p>
            پیش از مراجعه، با اطلاعات پزشک و خدمات ارائه‌شده آشنا شوید و سپس برای انتخاب
            زمان مناسب اقدام کنید.
          </p>

          <a href="#services" class="text-link">
            مشاهده خدمات
            <span>←</span>
          </a>
        </div>

        <div class="about-panel">
          <div class="about-panel-mark">✳</div>
          <div class="about-panel-content">
            <span class="section-kicker">تجربه‌ای ساده‌تر</span>
            <h3>اطلاعات موردنیاز، در دسترس شما</h3>
            <p>
              مسیر دسترسی به خدمات و ثبت نوبت را ساده نگه داشته‌ایم تا راحت‌تر به بخش
              موردنظر خود برسید.
            </p>
          </div>
          <div class="about-panel-line"></div>
        </div>
      </div>
    </section>

    <!-- Services -->
    <section id="services" class="section services-section">
      <div class="container">
        <div class="services-heading">
          <div>
            <span class="section-kicker">دسترسی سریع</span>
            <h2>چطور می‌توانیم کمک کنیم؟</h2>
          </div>

          <a :href="appointmentUrl" class="text-link">
            دریافت نوبت
            <span>←</span>
          </a>
        </div>

        <div class="services-grid">
          <article
            v-for="(service, index) in services"
            :key="service.title"
            class="service-card"
          >
            <div class="service-card-top">
              <span class="service-icon">{{ service.icon }}</span>
              <span class="service-number">۰{{ index }}</span>
            </div>

            <h3>{{ service.title }}</h3>
            <p>{{ service.description }}</p>

            <a :href="appointmentUrl" class="service-link">
              مشاهده و اقدام
              <span>←</span>
            </a>
          </article>
        </div>
      </div>
    </section>

    <!-- Main CTA -->
    <section id="booking" class="booking-section">
      <div class="container">
        <div class="booking-banner">
          <div class="booking-decoration"></div>

          <div class="booking-content">
            <span class="booking-kicker">یک قدم تا ثبت نوبت</span>
            <h2>زمان مناسب خود را انتخاب کنید.</h2>
            <p>
              برای مشاهده زمان‌های قابل رزرو و ثبت نوبت، از بخش نوبت‌گیری استفاده کنید.
            </p>
          </div>

          <a href="#booking" class="button booking-button">
            ورود به بخش نوبت‌گیری
            <span>←</span>
          </a>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer">
      <div class="container footer-main">
        <a href="#home" class="brand footer-brand">
          <span class="brand-symbol">
            <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
              <rect x="2" y="2" width="36" height="36" rx="13" fill="currentColor" />
              <path
                d="M20 11V29M11 20H29"
                stroke="white"
                stroke-width="3.5"
                stroke-linecap="round"
              />
            </svg>
          </span>
          <span class="brand-copy">
            <strong>دکتر فرهاد وفایی</strong>
            <small>همراه شما در مسیر سلامتی</small>
          </span>
        </a>

        <p class="footer-message">
          برای مشاهده اطلاعات و ثبت نوبت، از بخش‌های سایت استفاده کنید.
        </p>

        <a :href="appointmentUrl" class="button button-primary">
          دریافت نوبت
          <span>←</span>
        </a>
      </div>

      <div class="container footer-bottom">
        <span>© تمامی حقوق محفوظ است.</span>
        <a href="#home">بازگشت به بالا ↑</a>
      </div>
    </footer>


    <!-- دکمه شناور نوبت‌گیری -->
    <a href="#booking" class="floating-appointment" aria-label="دریافت نوبت">
      <span class="floating-appointment-icon">＋</span>
      <span>نوبت</span>
    </a>
  </main>
</template>

<style>
/* فونت را با فونت فارسی موجود در پروژه خود هماهنگ کنید */
.site {
  --primary: #e65378;
  --primary-dark: #c9345d;
  --primary-soft: #fff0f4;
  --primary-pale: #fff8fa;
  --primary-rgb: 230, 83, 120;

  --ink: #25232a;
  --muted: #777680;
  --border: #eeedf0;
  --surface: #ffffff;
  --page: #ffffff;

  font-family: inherit;
  color: var(--ink);
  background: var(--page);
  line-height: 1.9;
  overflow: hidden;
  -webkit-font-smoothing: antialiased;
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

.site .container {
  width: min(1160px, calc(100% - 48px));
  margin-inline: auto;
}

.site .header {
  position: relative;
  z-index: 10;
  background: rgba(255, 255, 255, 0.94);
  border-bottom: 1px solid rgba(30, 30, 40, 0.045);
}

.site .nav {
  min-height: 88px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.site .brand {
  display: inline-flex;
  align-items: center;
  gap: 11px;
  flex-shrink: 0;
}

.site .brand-symbol {
  display: flex;
  width: 43px;
  height: 43px;
  color: var(--primary);
}

.site .brand-symbol svg {
  width: 100%;
  height: 100%;
}

.site .brand-copy {
  display: flex;
  flex-direction: column;
  line-height: 1.8;
}

.site .brand-copy strong {
  font-size: 15px;
  font-weight: 800;
}

.site .brand-copy small {
  font-size: 10px;
  color: var(--muted);
}

.site .desktop-nav {
  display: flex;
  align-items: center;
  gap: 29px;
}

.site .nav-link {
  font-size: 12px;
  font-weight: 600;
  color: #777680;
  transition: color 0.2s ease;
}

.site .nav-link:hover,
.site .nav-link.active {
  color: var(--primary);
}

.site .button {
  min-height: 47px;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 10px 21px;
  border: 1px solid transparent;
  border-radius: 13px;
  font-size: 12px;
  font-weight: 750;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
}

.site .button:hover {
  transform: translateY(-2px);
}

.site .button-primary {
  color: white;
  background: var(--primary);
  box-shadow: 0 8px 22px rgba(var(--primary-rgb), 0.18);
}

.site .button-primary:hover {
  background: var(--primary-dark);
  box-shadow: 0 11px 27px rgba(var(--primary-rgb), 0.25);
}

.site .header-cta {
  min-height: 43px;
  padding-inline: 19px;
}

.site .menu-toggle,
.site .mobile-nav,
.site .mobile-booking {
  display: none;
}

/* Hero */
.site .hero {
  position: relative;
  background: radial-gradient(ellipse at 80% 35%, var(--primary-pale) 0, transparent 43%),
    linear-gradient(180deg, #fff 0%, #fff 80%, #fffdfd 100%);
}

.site .hero-orb {
  position: absolute;
  pointer-events: none;
  border-radius: 50%;
  background: var(--primary-soft);
  opacity: 0.75;
}

.site .orb-one {
  width: 150px;
  height: 150px;
  top: 70px;
  left: -85px;
}

.site .orb-two {
  width: 65px;
  height: 65px;
  top: 130px;
  right: 48%;
  opacity: 0.55;
}

.site .hero-grid {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  align-items: center;
  gap: 70px;
  min-height: 550px;
  padding-top: 48px;
  padding-bottom: 35px;
}

.site .hero-copy {
  padding-block: 25px;
}

.site .eyebrow {
  width: fit-content;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 6px 13px;
  border: 1px solid rgba(var(--primary-rgb), 0.13);
  border-radius: 100px;
  background: white;
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 700;
}

.site .status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 0 4px var(--primary-soft);
}

.site .hero h1 {
  margin: 23px 0 18px;
  font-size: clamp(33px, 4vw, 49px);
  line-height: 1.65;
  letter-spacing: -1.7px;
  font-weight: 900;
}

.site .accent-text {
  color: var(--primary);
}

.site .hero-description {
  max-width: 485px;
  color: var(--muted);
  font-size: 13px;
  line-height: 2.35;
}

.site .hero-actions {
  display: flex;
  align-items: center;
  gap: 25px;
  flex-wrap: wrap;
  margin-top: 27px;
}

.site .button-large {
  min-height: 55px;
  padding-inline: 23px;
}

.site .button-arrow {
  font-size: 18px;
}

.site .text-link {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  font-weight: 750;
  color: var(--ink);
  transition: color 0.2s ease;
}

.site .text-link span {
  color: var(--primary);
  font-size: 18px;
}

.site .text-link:hover {
  color: var(--primary);
}

.site .hero-note {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-top: 30px;
  color: var(--muted);
  font-size: 10px;
}

.site .note-icon {
  display: inline-grid;
  place-items: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary-soft);
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 800;
}

/* Hero visual */
.site .hero-visual {
  position: relative;
  width: 100%;
  max-width: 430px;
  justify-self: center;
  padding: 20px 25px 27px;
}

.site .visual-backdrop {
  position: absolute;
  inset: 32px 14px 12px 45px;
  border-radius: 170px 170px 30px 30px;
  background: var(--primary-soft);
  transform: rotate(-3deg);
}

.site .portrait-frame {
  position: relative;
  z-index: 1;
  height: 390px;
  overflow: hidden;
  border: 7px solid white;
  border-radius: 170px 170px 27px 27px;
  background: #f1ecee;
  box-shadow: 0 20px 60px rgba(40, 25, 35, 0.09);
}

.site .doctor-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
}

.site .portrait-overlay {
  position: absolute;
  inset-inline: 12px;
  bottom: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 15px;
  border: 1px solid rgba(255, 255, 255, 0.7);
  border-radius: 17px;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(12px);
}

.site .portrait-icon {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 12px;
  color: white;
  background: var(--primary);
  font-size: 20px;
}

.site .portrait-overlay div:last-child {
  display: flex;
  flex-direction: column;
  line-height: 1.9;
}

.site .portrait-overlay strong {
  font-size: 12px;
  font-weight: 850;
}

.site .portrait-overlay span {
  font-size: 10px;
  color: var(--muted);
}

.site .floating-card {
  position: absolute;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px;
  border: 1px solid rgba(35, 30, 40, 0.04);
  border-radius: 16px;
  background: white;
  box-shadow: 0 12px 40px rgba(35, 25, 35, 0.09);
}

.site .floating-top {
  top: 75px;
  right: -7px;
}

.site .floating-bottom {
  bottom: 64px;
  left: -23px;
  max-width: 280px;
}

.site .floating-icon,
.site .calendar-icon {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 12px;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 20px;
}

.site .floating-card div {
  display: flex;
  flex-direction: column;
  line-height: 1.9;
}

.site .floating-card strong {
  font-size: 11px;
  white-space: nowrap;
}

.site .floating-card div span {
  font-size: 10px;
  color: var(--muted);
}

.site .floating-arrow {
  margin-inline-start: 5px;
  color: var(--primary);
}

.site .decorative-circle {
  position: absolute;
  border: 1px solid rgba(var(--primary-rgb), 0.22);
  border-radius: 50%;
}

.site .circle-one {
  width: 38px;
  height: 38px;
  right: 4px;
  bottom: 16px;
}

.site .circle-two {
  width: 15px;
  height: 15px;
  left: 7px;
  top: 50px;
  background: var(--primary);
}

.site .hero-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  min-height: 58px;
  border-top: 1px solid var(--border);
}

.site .bottom-caption,
.site .scroll-link {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--muted);
  font-size: 10px;
}

.site .bottom-line {
  width: 25px;
  height: 2px;
  background: var(--primary);
}

.site .scroll-link span {
  color: var(--primary);
  font-size: 16px;
}

/* Quick access */
.site .quick-access {
  position: relative;
  z-index: 2;
  background: white;
}

.site .quick-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  padding-block: 24px;
  border-bottom: 1px solid var(--border);
}

.site .quick-item {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 10px 22px;
  border-left: 1px solid var(--border);
  transition: background 0.2s ease;
}

.site .quick-item:first-child {
  padding-right: 0;
}

.site .quick-item:last-child {
  border-left: 0;
}

.site .quick-item:hover {
  background: var(--primary-pale);
}

.site .quick-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  flex-shrink: 0;
  border-radius: 14px;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 21px;
}

.site .quick-text {
  display: flex;
  flex: 1;
  flex-direction: column;
  line-height: 1.9;
}

.site .quick-text strong {
  font-size: 12px;
  font-weight: 800;
}

.site .quick-text small {
  color: var(--muted);
  font-size: 10px;
}

.site .quick-arrow {
  color: var(--primary);
  font-size: 17px;
}

/* Sections */
.site .section {
  padding-block: 90px;
}

.site .section-kicker {
  display: inline-block;
  margin-bottom: 8px;
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 800;
}

.site .section h2 {
  margin: 0;
  font-size: clamp(24px, 3vw, 32px);
  font-weight: 900;
  line-height: 1.8;
  letter-spacing: -0.7px;
}

.site .about-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 80px;
}

.site .section-heading > p {
  max-width: 440px;
  margin: 15px 0 20px;
  color: var(--muted);
  font-size: 12px;
  line-height: 2.5;
}

.site .about-panel {
  position: relative;
  display: flex;
  gap: 20px;
  align-items: flex-start;
  overflow: hidden;
  padding: 32px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  border-radius: 24px;
  background: radial-gradient(circle at 90% 0, white 0, transparent 60%),
    var(--primary-pale);
}

.site .about-panel-mark {
  display: grid;
  place-items: center;
  width: 54px;
  height: 54px;
  flex-shrink: 0;
  border-radius: 17px;
  background: var(--primary);
  color: white;
  font-size: 27px;
}

.site .about-panel-content {
  position: relative;
  z-index: 1;
}

.site .about-panel-content h3 {
  margin: 0 0 9px;
  font-size: 17px;
  line-height: 1.9;
  font-weight: 850;
}

.site .about-panel-content p {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 2.4;
}

.site .about-panel-line {
  position: absolute;
  width: 130px;
  height: 130px;
  left: -45px;
  bottom: -65px;
  border: 1px solid rgba(var(--primary-rgb), 0.15);
  border-radius: 50%;
}

.site .services-section {
  background: #faf9fb;
}

.site .services-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 30px;
}

.site .services-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 19px;
}

.site .service-card {
  padding: 25px;
  border: 1px solid #eeedf0;
  border-radius: 20px;
  background: white;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.site .service-card:hover {
  transform: translateY(-5px);
  border-color: rgba(var(--primary-rgb), 0.3);
  box-shadow: 0 15px 40px rgba(var(--primary-rgb), 0.08);
}

.site .service-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.site .service-icon {
  display: grid;
  place-items: center;
  width: 50px;
  height: 50px;
  border-radius: 16px;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 25px;
}

.site .service-number {
  color: #b7b4bd;
  font-size: 12px;
  font-weight: 700;
}

.site .service-card h3 {
  margin: 22px 0 6px;
  font-size: 15px;
  font-weight: 850;
}

.site .service-card p {
  min-height: 48px;
  margin: 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 2.2;
}

.site .service-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 22px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 800;
}

.site .service-link span {
  font-size: 17px;
}

/* Booking banner */
.site .booking-section {
  padding-block: 65px;
  background: white;
}

.site .booking-banner {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
  overflow: hidden;
  padding: 38px 43px;
  border-radius: 25px;
  background: var(--primary-soft);
}

.site .booking-decoration {
  position: absolute;
  width: 230px;
  height: 230px;
  left: 20%;
  top: -155px;
  border: 1px solid rgba(var(--primary-rgb), 0.2);
  border-radius: 50%;
  pointer-events: none;
}

.site .booking-content {
  position: relative;
  z-index: 1;
}

.site .booking-kicker {
  color: var(--primary-dark);
  font-size: 11px;
  font-weight: 800;
}

.site .booking-content h2 {
  margin: 6px 0;
  font-size: clamp(21px, 2.5vw, 29px);
  line-height: 1.8;
  font-weight: 900;
}

.site .booking-content p {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 2.2;
}

.site .booking-button {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  background: var(--primary);
  color: white;
}

.site .booking-button:hover {
  background: var(--primary-dark);
}

/* Footer */
.site .footer {
  background: #faf9fb;
  border-top: 1px solid var(--border);
}

.site .footer-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding-block: 32px;
}

.site .footer-message {
  max-width: 360px;
  margin: 0;
  color: var(--muted);
  font-size: 11px;
}

.site .footer-bottom {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding-block: 17px;
  border-top: 1px solid var(--border);
  color: var(--muted);
  font-size: 10px;
}

.site .footer-bottom a {
  color: var(--primary-dark);
  font-weight: 700;
}

.site a:focus-visible,
.site button:focus-visible {
  outline: 3px solid rgba(var(--primary-rgb), 0.5);
  outline-offset: 4px;
}

/* Responsive */
@media (min-width: 1400px) {
  .site .hero-grid {
    min-height: 590px;
  }
}

@media (max-width: 1000px) {
  .site .desktop-nav {
    gap: 17px;
  }

  .site .hero-grid {
    gap: 30px;
  }

  .site .hero h1 {
    font-size: 38px;
  }

  .site .hero-visual {
    max-width: 380px;
  }

  .site .portrait-frame {
    height: 355px;
  }

  .site .about-grid {
    gap: 35px;
  }
}

@media (max-width: 760px) {
  .site .container {
    width: min(100% - 32px, 540px);
  }

  .site .nav {
    min-height: 72px;
    gap: 12px;
  }

  .site .desktop-nav {
    display: none;
  }

  .site .header-cta {
    margin-inline-start: auto;
    min-height: 39px;
    padding: 7px 13px;
    font-size: 11px;
  }

  .site .brand-symbol {
    width: 37px;
    height: 37px;
  }

  .site .brand-copy strong {
    font-size: 12px;
  }

  .site .brand-copy small {
    font-size: 9px;
  }

  .site .menu-toggle {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 39px;
    height: 39px;
    flex-shrink: 0;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: white;
    cursor: pointer;
  }

  .site .menu-toggle span {
    width: 17px;
    height: 2px;
    border-radius: 4px;
    background: var(--primary);
  }

  .site .mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding-block: 8px 14px;
  }

  .site .mobile-nav a {
    padding: 10px 5px;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
  }

  .site .mobile-nav a:last-child {
    color: var(--primary-dark);
    border-bottom: 0;
    font-weight: 800;
  }

  .site .hero-grid {
    grid-template-columns: 1fr;
    gap: 10px;
    padding-top: 35px;
    padding-bottom: 20px;
  }

  .site .hero-copy {
    padding-block: 0;
  }

  .site .hero h1 {
    max-width: 450px;
    margin-top: 17px;
    font-size: clamp(31px, 8vw, 43px);
    letter-spacing: -1px;
    line-height: 1.65;
  }

  .site .hero-description {
    font-size: 12px;
    line-height: 2.3;
  }

  .site .hero-actions {
    gap: 17px;
    margin-top: 22px;
  }

  .site .button-large {
    min-height: 50px;
    padding-inline: 16px;
    font-size: 11px;
  }

  .site .hero-note {
    margin-top: 20px;
    font-size: 10px;
  }

  .site .hero-visual {
    width: min(100%, 365px);
    padding: 24px 24px 27px;
    margin-top: 6px;
  }

  .site .portrait-frame {
    height: 340px;
    border-radius: 145px 145px 25px 25px;
  }

  .site .floating-top {
    top: 60px;
    right: -3px;
  }

  .site .floating-bottom {
    bottom: 48px;
    left: -5px;
    max-width: 265px;
  }

  .site .hero-bottom {
    min-height: 53px;
  }

  .site .bottom-caption {
    max-width: 75%;
    line-height: 1.9;
  }

  .site .scroll-link {
    white-space: nowrap;
  }

  .site .quick-grid {
    grid-template-columns: 1fr;
    padding-block: 10px;
  }

  .site .quick-item,
  .site .quick-item:first-child {
    padding: 12px 0;
    border-left: 0;
    border-bottom: 1px solid var(--border);
  }

  .site .quick-item:last-child {
    border-bottom: 0;
  }

  .site .quick-icon {
    width: 40px;
    height: 40px;
  }

  .site .section {
    padding-block: 58px;
  }

  .site .about-grid {
    grid-template-columns: 1fr;
    gap: 27px;
  }

  .site .section h2 {
    font-size: 26px;
  }

  .site .about-panel {
    padding: 23px;
    gap: 15px;
  }

  .site .about-panel-mark {
    width: 44px;
    height: 44px;
    border-radius: 14px;
  }

  .site .about-panel-content h3 {
    font-size: 15px;
  }

  .site .services-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 23px;
  }

  .site .services-grid {
    grid-template-columns: 1fr;
    gap: 13px;
  }

  .site .service-card {
    padding: 21px;
  }

  .site .service-card h3 {
    margin-top: 15px;
  }

  .site .service-card p {
    min-height: auto;
  }

  .site .service-link {
    margin-top: 14px;
  }

  .site .booking-section {
    padding-block: 35px;
  }

  .site .booking-banner {
    align-items: flex-start;
    flex-direction: column;
    padding: 27px 23px;
    gap: 19px;
    border-radius: 21px;
  }

  .site .booking-content h2 {
    font-size: 22px;
  }

  .site .booking-button {
    width: 100%;
  }

  .site .footer-main {
    align-items: flex-start;
    flex-direction: column;
    gap: 18px;
    padding-block: 27px;
  }

  .site .footer-message {
    max-width: 100%;
  }

  .site .footer-main > .button {
    width: 100%;
  }

  .site .footer-bottom {
    font-size: 9px;
  }

  .site .mobile-booking {
    position: fixed;
    z-index: 20;
    inset-inline: 0;
    bottom: 0;
    display: block;
    padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
    border-top: 1px solid var(--border);
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(12px);
  }

  .site .mobile-booking .button {
    width: 100%;
    min-height: 47px;
  }

  .site .footer {
    padding-bottom: 65px;
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
/* دکمه دایره‌ای ثابت نوبت‌گیری */
.site .floating-appointment {
  position: fixed;
  z-index: 100;
  bottom: 28px;
  right: 28px;

  width: 68px;
  height: 68px;
  border-radius: 50%;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0;

  color: #fff;
  background: var(--primary);
  border: 3px solid rgba(255, 255, 255, 0.9);

  box-shadow: 0 5px 18px rgba(var(--primary-rgb), 0.3),
    0 0 0 5px rgba(var(--primary-rgb), 0.08);

  font-size: 13px;
  font-weight: 800;
  line-height: 1.5;

  transition: transform 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
}

.site .floating-appointment:hover {
  background: var(--primary-dark);
  transform: scale(1.08);

  box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.38),
    0 0 0 7px rgba(var(--primary-rgb), 0.09);
}

.site .floating-appointment-icon {
  font-size: 20px;
  line-height: 1.2;
}

/* نمایش مناسب در موبایل */
@media (max-width: 760px) {
  .site .floating-appointment {
    width: 58px;
    height: 58px;
    right: 16px;
    bottom: 82px;
    font-size: 12px;
  }

  .site .floating-appointment-icon {
    font-size: 17px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .site .floating-appointment {
    transition: none;
  }
}
</style>

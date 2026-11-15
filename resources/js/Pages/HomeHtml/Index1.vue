<script setup>
import { ref } from "vue";

const menuOpen = ref(false);

const services = [
  {
    icon: "✧",
    title: "جراحی زیبایی",
    description: "بهبود ظاهر با رویکردی طبیعی و متناسب با ویژگی‌های فردی",
  },
  {
    icon: "◇",
    title: "جراحی ترمیمی",
    description: "بررسی و درمان مشکلات با توجه به شرایط و نیاز هر فرد",
  },
  {
    icon: "♡",
    title: "مشاوره تخصصی",
    description: "بررسی شرایط شما و انتخاب مسیر درمان مناسب",
  },
];

const benefits = [
  { icon: "✳", title: "مشاوره پیش از درمان" },
  { icon: "▤", title: "برنامه درمان متناسب با شما" },
  { icon: "♡", title: "پیگیری پس از مراجعه" },
];

const bookingUrl = "/booking";
</script>

<template>
  <div class="home" dir="rtl">
    <!-- Header -->
    <header class="header">
      <div class="container nav">
        <a href="/" class="brand">
          <span class="brand-icon">ف</span>
          <span>
            <strong>دکتر فرهاد وفایی</strong>
            <small>جراحی زیبایی و ترمیمی</small>
          </span>
        </a>

        <button
          class="menu-toggle"
          type="button"
          :aria-expanded="menuOpen"
          aria-label="نمایش منو"
          @click="menuOpen = !menuOpen"
        >
          {{ menuOpen ? "✕" : "☰" }}
        </button>

        <nav class="nav-links" :class="{ open: menuOpen }">
          <a class="active" href="/" @click="menuOpen = false"> صفحه اصلی </a>
          <a href="#services" @click="menuOpen = false">خدمات</a>
          <a href="#about" @click="menuOpen = false">درباره پزشک</a>
          <a href="#steps" @click="menuOpen = false">راهنمای نوبت</a>
          <a href="#contact" @click="menuOpen = false">تماس با ما</a>
          <a class="mobile-book" :href="route('booking')"> رزرو نوبت </a>
        </nav>

        <a class="button button-primary header-book" :href="bookingUrl">
          <span>رزرو نوبت</span>
          <span>↙</span>
        </a>
      </div>
    </header>

    <main>
      <!-- Hero -->
      <section class="hero" id="about">
        <div class="container hero-grid">
          <div class="hero-copy">
            <span class="eyebrow">
              <span class="eyebrow-dot"></span>
              همراه شما در مسیر انتخاب آگاهانه
            </span>

            <h1>
              زیبایی طبیعی،
              <span>با انتخابی آگاهانه</span>
            </h1>

            <p class="hero-description">
              برای شناخت بهتر شرایط خود، دریافت مشاوره و آشنایی با گزینه‌های درمانی، اولین
              قدم را با آگاهی بردارید.
            </p>

            <div class="hero-actions">
              <a class="button button-primary button-large" :href="bookingUrl">
                <span>دریافت نوبت</span>
                <span>←</span>
              </a>

              <a class="button button-outline button-large" href="#services">
                آشنایی با خدمات
              </a>
            </div>

            <div class="hero-note">
              <span class="note-star">✦</span>
              مشاوره تخصصی
              <span class="note-separator">•</span>
              توجه به نیازهای فردی
            </div>
          </div>

          <div class="hero-visual">
            <div class="hero-image">
              <img src="\storage\doctors\Dr.Farhad.Vafaei.jpg" alt="تصویر پزشک" />
            </div>

            <div class="floating-card">
              <span class="floating-icon">✧</span>
              <div>
                <strong>شروع با یک مشاوره</strong>
                <small>متناسب با شرایط شما</small>
              </div>
            </div>

            <div class="decor decor-one"></div>
            <div class="decor decor-two"></div>
          </div>
        </div>
      </section>

      <!-- Benefits -->
      <section class="benefits container">
        <div v-for="item in benefits" :key="item.title" class="benefit-item">
          <span class="benefit-icon">{{ item.icon }}</span>
          <span>{{ item.title }}</span>
        </div>
      </section>

      <!-- Services -->
      <section class="section services-section" id="services">
        <div class="container">
          <div class="section-heading">
            <span class="eyebrow">خدمات و راهنمای مراجعه</span>
            <h2>از آشنایی تا انتخاب مسیر مناسب</h2>
            <p>
              اطلاعات مورد نیازتان را پیدا کنید و در صورت تمایل، برای دریافت نوبت اقدام
              کنید.
            </p>
          </div>

          <div class="service-grid">
            <article
              v-for="(service, index) in services"
              :key="service.title"
              class="service-card"
            >
              <span class="service-number"> ۰{{ index }} </span>

              <div class="service-icon">{{ service.icon }}</div>
              <h3>{{ service.title }}</h3>
              <p>{{ service.description }}</p>

              <a href="#booking" class="service-link"> ادامه مطلب <span>←</span> </a>
            </article>
          </div>
        </div>
      </section>

      <!-- Booking steps -->
      <section class="steps-section" id="steps">
        <div class="container steps-inner">
          <div class="section-heading">
            <span class="eyebrow">ساده و شفاف</span>
            <h2>دریافت نوبت در سه مرحله</h2>
          </div>

          <div class="steps-grid">
            <div class="step">
              <span class="step-number">۱</span>
              <div>
                <h3>انتخاب زمان مراجعه</h3>
                <p>زمان مناسب خود را مشاهده و انتخاب کنید.</p>
              </div>
            </div>

            <div class="step">
              <span class="step-number">۲</span>
              <div>
                <h3>ثبت اطلاعات</h3>
                <p>اطلاعات ضروری را با دقت وارد کنید.</p>
              </div>
            </div>

            <div class="step">
              <span class="step-number">۳</span>
              <div>
                <h3>تأیید نوبت</h3>
                <p>وضعیت نوبت و جزئیات مراجعه را پیگیری کنید.</p>
              </div>
            </div>
          </div>

          <div class="booking-cta" id="booking">
            <div>
              <span class="eyebrow">قدم بعدی شما</span>
              <h2>برای دریافت نوبت آماده‌اید؟</h2>
              <p>زمان مراجعه را انتخاب کنید و مراحل ثبت نوبت را ادامه دهید.</p>
            </div>

            <a class="button button-light button-large" 
            
              :href="route('booking')"
            
            >
              شروع رزرو نوبت <span>←</span>
            </a>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="footer" id="contact">
      <div class="container footer-inner">
        <a href="/" class="brand footer-brand">
          <span class="brand-icon">ف</span>
          <span>
            <strong>دکتر فرهاد وفایی</strong>
            <small>جراحی زیبایی و ترمیمی</small>
          </span>
        </a>

        <p>برای اطلاعات بیشتر و هماهنگی مراجعه، از بخش رزرو نوبت استفاده کنید.</p>

        <a :href="bookingUrl">رزرو نوبت ←</a>
      </div>

      <div class="copyright">تمامی حقوق محفوظ است.</div>
    </footer>

    <!-- دکمه شناور نوبت‌گیری -->
    <a href="#booking" class="floating-appointment" aria-label="دریافت نوبت">
      <span class="floating-appointment-icon">＋</span>
      <span>نوبت</span>
    </a>
  </div>
</template>

<style scoped>
.home {
  --primary: #087f79;
  --primary-dark: #075e5a;
  --navy: #163653;
  --text: #53677a;
  --muted: #8393a2;
  --surface: #ffffff;
  --background: #f8faf9;
  --line: #e8eeeb;
  color: var(--navy);
  background: var(--surface);
  font-family: Vazirmatn, IRANSans, Tahoma, sans-serif;
  line-height: 1.9;
  overflow: hidden;
}

.home *,
.home *::before,
.home *::after {
  box-sizing: border-box;
}

.home a {
  text-decoration: none;
  color: inherit;
}

.container {
  width: min(1160px, calc(100% - 48px));
  margin-inline: auto;
}

.header {
  position: relative;
  z-index: 10;
  background: rgba(255, 255, 255, 0.96);
  border-bottom: 1px solid #edf1ef;
}

.nav {
  min-height: 82px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  flex-shrink: 0;
}

.brand-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  border: 1px solid #b8e0d9;
  color: var(--primary);
  background: #eff9f6;
  border-radius: 14px;
  font-size: 25px;
  font-weight: 800;
}

.brand strong,
.brand small {
  display: block;
}

.brand strong {
  font-size: 15px;
  font-weight: 800;
}

.brand small {
  color: var(--muted);
  font-size: 11px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 27px;
  font-size: 13px;
  color: #526579;
}

.nav-links > a:not(.mobile-book) {
  position: relative;
  padding-block: 8px;
  transition: color 0.2s;
}

.nav-links > a:hover,
.nav-links > a.active {
  color: var(--primary);
}

.nav-links > a.active::after {
  content: "";
  position: absolute;
  height: 2px;
  bottom: 0;
  right: 0;
  left: 0;
  background: var(--primary);
  border-radius: 4px;
}

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  border-radius: 13px;
  padding: 11px 22px;
  font-size: 13px;
  font-weight: 700;
  transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.button:hover {
  transform: translateY(-2px);
}

.button-primary {
  color: #fff !important;
  background: var(--primary);
  box-shadow: 0 7px 18px rgba(8, 127, 121, 0.15);
}

.button-primary:hover {
  background: var(--primary-dark);
}

.button-outline {
  color: var(--primary) !important;
  background: #fff;
  border: 1px solid #b8dcd6;
}

.button-large {
  min-height: 49px;
  padding-inline: 25px;
}

.menu-toggle,
.mobile-book {
  display: none;
}

/* Hero */

.hero {
  position: relative;
  background: radial-gradient(ellipse at 0% 15%, #e4f2ed 0%, transparent 44%),
    linear-gradient(115deg, #f7faf7 0%, #fff 58%, #f6faf8 100%);
}

.hero-grid {
  min-height: 470px;
  display: grid;
  grid-template-columns: 1fr 0.95fr;
  align-items: center;
  gap: 50px;
  padding-block: 50px 70px;
}

.hero-copy {
  position: relative;
  z-index: 2;
  max-width: 560px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: var(--primary);
  font-size: 12px;
  font-weight: 700;
}

.eyebrow-dot {
  width: 7px;
  height: 7px;
  background: #24a69a;
  border-radius: 50%;
  box-shadow: 0 0 0 5px #e2f4ef;
}

.hero h1 {
  margin: 18px 0 16px;
  font-size: clamp(32px, 3.6vw, 49px);
  line-height: 1.65;
  letter-spacing: -1.5px;
  font-weight: 900;
}

.hero h1 span {
  display: block;
  color: var(--primary);
}

.hero-description {
  max-width: 470px;
  color: var(--text);
  font-size: 14px;
  line-height: 2.3;
  margin: 0;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 27px;
}

.hero-note {
  margin-top: 20px;
  display: flex;
  align-items: center;
  gap: 9px;
  color: #758696;
  font-size: 11px;
}

.note-star {
  color: #c4a75b;
  font-size: 16px;
}

.note-separator {
  color: #b5c0c7;
}

.hero-visual {
  position: relative;
  min-width: 0;
  padding: 15px 0 25px 15px;
}

.hero-image {
  position: relative;
  height: 375px;
  overflow: hidden;
  border-radius: 46% 46% 24px 24px;
  background: #e2ece7;
  box-shadow: 0 25px 55px rgba(30, 72, 65, 0.12);
}

.hero-image::after {
  content: "";
  position: absolute;
  inset: 45% 0 0;
  background: linear-gradient(transparent, rgba(9, 47, 51, 0.15));
  pointer-events: none;
}

.hero-image img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 30%;
}

.floating-card {
  position: absolute;
  z-index: 2;
  right: -22px;
  bottom: 47px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(225, 238, 232, 0.9);
  border-radius: 15px;
  box-shadow: 0 12px 35px rgba(35, 65, 58, 0.12);
}

.floating-icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #eaf7f2;
  color: var(--primary);
  font-size: 24px;
}

.floating-card strong,
.floating-card small {
  display: block;
}

.floating-card strong {
  font-size: 12px;
}

.floating-card small {
  font-size: 10px;
  color: var(--muted);
}

.decor {
  position: absolute;
  z-index: 0;
  border: 1px solid #b8ded4;
  border-radius: 50%;
  pointer-events: none;
}

.decor-one {
  width: 100px;
  height: 100px;
  top: -10px;
  left: -15px;
}

.decor-two {
  width: 44px;
  height: 44px;
  bottom: 12px;
  left: -20px;
  background: #dcefe8;
}

/* Benefits */

.benefits {
  position: relative;
  z-index: 3;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  margin-top: -32px;
  padding: 20px 15px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 19px;
  box-shadow: 0 12px 38px rgba(26, 58, 49, 0.055);
}

.benefit-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 13px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
  text-align: center;
}

.benefit-item + .benefit-item {
  border-right: 1px solid var(--line);
}

.benefit-icon {
  flex-shrink: 0;
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  color: var(--primary);
  background: #eff8f5;
  border-radius: 14px;
  font-size: 22px;
}

/* Services */

.section {
  padding-block: 82px;
}

.section-heading {
  text-align: center;
  max-width: 620px;
  margin: 0 auto 36px;
}

.section-heading h2 {
  margin: 9px 0;
  font-size: clamp(23px, 2.5vw, 30px);
  letter-spacing: -0.5px;
}

.section-heading p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

.service-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.service-card {
  position: relative;
  min-height: 235px;
  padding: 27px 25px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 19px;
  box-shadow: 0 7px 25px rgba(24, 58, 50, 0.035);
  transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}

.service-card:hover {
  transform: translateY(-5px);
  border-color: #b5ded5;
  box-shadow: 0 15px 35px rgba(24, 58, 50, 0.075);
}

.service-number {
  position: absolute;
  top: 19px;
  left: 20px;
  color: #c0cec9;
  font-size: 12px;
  direction: ltr;
}

.service-icon {
  width: 53px;
  height: 53px;
  display: grid;
  place-items: center;
  border-radius: 17px;
  background: #edf7f3;
  color: var(--primary);
  font-size: 29px;
}

.service-card h3 {
  margin: 15px 0 7px;
  font-size: 16px;
}

.service-card p {
  min-height: 50px;
  margin: 0;
  color: var(--text);
  font-size: 12px;
  line-height: 2.1;
}

.service-link {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  margin-top: 15px;
  color: var(--primary) !important;
  font-size: 12px;
  font-weight: 700;
}

/* Steps and CTA */

.steps-section {
  padding: 65px 0;
  background: #f5faf8;
}

.steps-inner > .section-heading {
  margin-bottom: 35px;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  margin-bottom: 45px;
}

.step {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.step-number {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  background: #e1f2ec;
  color: var(--primary);
  border-radius: 14px;
  font-weight: 800;
}

.step h3 {
  margin: 0 0 5px;
  font-size: 14px;
}

.step p {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
}

.booking-cta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  padding: 30px 35px;
  background: var(--navy);
  color: #fff;
  border-radius: 23px;
}

.booking-cta .eyebrow {
  color: #90d8ca;
}

.booking-cta h2 {
  margin: 5px 0;
  font-size: 23px;
}

.booking-cta p {
  margin: 0;
  color: #d1dce4;
  font-size: 12px;
}

.button-light {
  flex-shrink: 0;
  background: #fff;
  color: var(--primary) !important;
}

.footer {
  padding-top: 35px;
  background: #fff;
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  padding-bottom: 25px;
}

.footer-inner p {
  color: var(--muted);
  font-size: 12px;
}

.footer-inner > a:last-child {
  color: var(--primary);
  font-size: 12px;
  font-weight: 700;
}

.copyright {
  padding: 15px 20px;
  text-align: center;
  color: #91a09b;
  background: #f6f9f7;
  border-top: 1px solid var(--line);
  font-size: 11px;
}

/* Responsive */

@media (min-width: 1400px) {
  .hero-grid {
    min-height: 500px;
  }

  .hero-image {
    height: 405px;
  }
}

@media (max-width: 900px) {
  .nav {
    gap: 14px;
  }

  .nav-links {
    gap: 15px;
    font-size: 12px;
  }

  .header-book {
    padding-inline: 15px;
  }

  .hero-grid {
    gap: 25px;
  }

  .hero-image {
    height: 320px;
  }

  .floating-card {
    right: -10px;
  }

  .service-grid {
    gap: 13px;
  }

  .service-card {
    padding: 22px 18px;
  }
}

@media (max-width: 680px) {
  .container {
    width: calc(100% - 32px);
  }

  .nav {
    min-height: 72px;
  }

  .brand strong {
    font-size: 13px;
  }

  .brand small {
    font-size: 10px;
  }

  .brand-icon {
    width: 39px;
    height: 39px;
  }

  .menu-toggle {
    display: grid;
    place-items: center;
    width: 41px;
    height: 41px;
    margin-right: auto;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    color: var(--primary);
    font-size: 20px;
    cursor: pointer;
  }

  .header-book {
    display: none;
  }

  .nav-links {
    position: absolute;
    top: calc(100% + 1px);
    right: 0;
    left: 0;
    display: none;
    align-items: stretch;
    gap: 0;
    padding: 12px 16px 18px;
    background: #fff;
    border-bottom: 1px solid var(--line);
    box-shadow: 0 14px 24px rgba(20, 55, 46, 0.07);
  }

  .nav-links.open {
    display: flex;
    flex-direction: column;
  }

  .nav-links > a:not(.mobile-book) {
    padding: 12px 6px;
  }

  .nav-links > a.active::after {
    display: none;
  }

  .mobile-book {
    display: block;
    margin-top: 8px;
    padding: 11px;
    border-radius: 11px;
    background: var(--primary);
    color: #fff !important;
    text-align: center;
    font-weight: 700;
  }

  .hero-grid {
    grid-template-columns: 1fr;
    gap: 15px;
    padding-top: 38px;
    padding-bottom: 55px;
  }

  .hero-copy {
    order: 1;
  }

  .hero-visual {
    order: 2;
    padding: 0 0 25px;
    width: 100%;
    max-width: 450px;
    margin-inline: auto;
  }

  .hero h1 {
    font-size: clamp(29px, 7vw, 38px);
    line-height: 1.6;
    letter-spacing: -0.7px;
  }

  .hero-description {
    font-size: 13px;
  }

  .hero-image {
    height: 280px;
    border-radius: 30px 30px 18px 18px;
  }

  .floating-card {
    right: 10px;
    bottom: 5px;
  }

  .decor-one {
    top: -12px;
    left: -5px;
  }

  .benefits {
    margin-top: -25px;
    grid-template-columns: 1fr;
    padding: 8px 15px;
    border-radius: 16px;
  }

  .benefit-item {
    justify-content: flex-start;
    padding: 10px 4px;
    text-align: right;
  }

  .benefit-item + .benefit-item {
    border-right: none;
    border-top: 1px solid var(--line);
  }

  .benefit-icon {
    width: 38px;
    height: 38px;
  }

  .section {
    padding-block: 55px;
  }

  .section-heading {
    margin-bottom: 26px;
  }

  .section-heading h2 {
    font-size: 23px;
  }

  .service-grid {
    grid-template-columns: 1fr;
    gap: 13px;
  }

  .service-card {
    min-height: auto;
    padding: 22px;
  }

  .service-card p {
    min-height: 0;
  }

  .steps-section {
    padding: 48px 0;
  }

  .steps-grid {
    grid-template-columns: 1fr;
    gap: 22px;
    margin-bottom: 32px;
  }

  .booking-cta {
    flex-direction: column;
    align-items: stretch;
    padding: 25px 21px;
    gap: 20px;
  }

  .booking-cta h2 {
    font-size: 21px;
  }

  .button-light {
    width: 100%;
  }

  .footer-inner {
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 12px;
  }

  .footer-inner p {
    flex-basis: 100%;
    order: 3;
  }
}

@media (prefers-reduced-motion: reduce) {
  .home *,
  .home *::before,
  .home *::after {
    scroll-behavior: auto !important;
    transition: none !important;
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

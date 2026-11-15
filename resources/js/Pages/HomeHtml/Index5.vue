<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";

const themeColor = "#648b78";

const menuOpen = ref(false);

const clinics = [
  {
    id: 1,
    title: "مطب مشهد",
    subtitle: "مراجعه حضوری با تعیین وقت قبلی",
    address: "آدرس مطب مشهد",
    icon: "🏥",
    route: "/appointment?clinic=1",
  },
  {
    id: 2,
    title: "مطب بجنورد",
    subtitle: "مراجعه حضوری با تعیین وقت قبلی",
    address: "آدرس مطب بجنورد",
    icon: "📍",
    route: "/appointment?clinic=2",
  },
];

const menuItems = [
  { label: "صفحه اصلی", href: "/" },
  { label: "درباره پزشک", href: "/about" },
  { label: "خدمات درمانی", href: "/services" },
  { label: "مقالات پزشکی", href: "/blog" },
  { label: "گالری تصاویر", href: "/gallery" },
  { label: "تماس با ما", href: "/contact" },
];
</script>

<template>
  <Head title="دریافت نوبت | دکتر فرهاد وفایی" />

  <div class="site" dir="rtl" :style="{ '--theme': themeColor }">
    <header class="header">
      <a href="/" class="brand" aria-label="صفحه اصلی">
        <span class="brand-icon">د</span>
        <span class="brand-text">
          <strong>دکتر فرهاد وفایی</strong>
          <small>خدمات پزشکی و درمانی</small>
        </span>
      </a>

      <nav class="desktop-nav" aria-label="منوی اصلی">
        <a v-for="item in menuItems" :key="item.href" :href="item.href">
          {{ item.label }}
        </a>
      </nav>

      <button
        class="menu-toggle"
        type="button"
        :aria-expanded="menuOpen"
        aria-label="نمایش منو"
        @click="menuOpen = !menuOpen"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </header>

    <div v-if="menuOpen" class="mobile-menu">
      <a
        v-for="item in menuItems"
        :key="item.href"
        :href="item.href"
        @click="menuOpen = false"
      >
        {{ item.label }}
      </a>
    </div>

    <main>
      <section class="hero">
        <div class="hero-content">
          <span class="eyebrow">
            <span class="status-dot"></span>
            سامانه نوبت‌دهی آنلاین
          </span>

          <h1>
            برای دریافت نوبت،
            <br />
            <span>مطب خود را انتخاب کنید.</span>
          </h1>

          <p class="hero-description">
            مطب موردنظر خود را انتخاب کنید و در چند مرحله ساده، زمان مراجعه‌تان را مشخص
            کنید.
          </p>

          <div class="hero-hint">
            <span class="hint-icon">↓</span>
            انتخاب یکی از دو مطب
          </div>
        </div>

        <div class="hero-decoration">
          <div class="decoration-circle circle-one"></div>

          <div class="doctor-circle">
            <img
              src="\storage\doctors\Dr.Farhad.Vafaei.jpg"
              alt="دکتر فرهاد وفایی"
              class="doctor-image"
            />
          </div>

          <div class="decoration-pill">
            <span class="pill-dot"></span>
            نوبت‌دهی آسان
          </div>

          <div class="decoration-cross">✳</div>
        </div>
      </section>

      <section class="clinics-section" id="clinics">
        <div class="section-heading">
          <div>
            <span class="section-kicker">انتخاب محل مراجعه</span>
            <h2>کدام مطب؟</h2>
          </div>
          <span class="clinic-count">۲ محل پذیرش</span>
        </div>

        <div class="clinic-grid">
          <article
            v-for="(clinic, index) in clinics"
            :key="clinic.id"
            class="clinic-card"
            :class="{ featured: index === 0 }"
          >
            <div class="card-top">
              <div class="clinic-icon">
                <svg
                  v-if="index === 0"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                  aria-hidden="true"
                >
                  <path
                    d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M12 9v4M10 11h4"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>

                <svg
                  v-else
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                  aria-hidden="true"
                >
                  <path
                    d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"
                    stroke-linejoin="round"
                  />
                  <circle cx="12" cy="10" r="2.5" />
                </svg>
              </div>

              <span class="clinic-number"> ۰{{ index + 1 }} </span>
            </div>

            <div class="clinic-info">
              <h3>{{ clinic.title }}</h3>
              <p>{{ clinic.subtitle }}</p>
            </div>

            <div class="address-row">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                aria-hidden="true"
              >
                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
                <circle cx="12" cy="10" r="2.5" />
              </svg>
              <span>{{ clinic.address }}</span>
            </div>

            <a :href="clinic.route" class="appointment-link">
              <span>دریافت نوبت</span>
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
              >
                <path
                  d="M19 12H5m7 7-7-7 7-7"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </a>
          </article>
        </div>

        <p class="help-note">
          <span class="help-icon">i</span>
          پیش از ثبت نهایی، محل مطب و زمان مراجعه را بررسی کنید.
        </p>
      </section>
    </main>

    <footer class="footer">
      <span>© دکتر فرهاد وفایی</span>
      <span>نوبت‌دهی آنلاین، ساده و سریع</span>
    </footer>

    <a href="#clinics" class="floating-booking">
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
      >
        <rect x="3" y="5" width="18" height="16" rx="3" />
        <path
          d="M16 3v4M8 3v4M3 10h18M9 15l2 2 4-4"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
      <span>نوبت</span>
    </a>
  </div>
</template>

<style scoped>
.site {
  --theme: #648b78;
  --theme-dark: #3f6554;
  --theme-light: #edf3ef;
  --ink: #26372f;
  --muted: #7a8980;
  --line: #e7ece8;
  min-height: 100vh;
  background: #fcfdfb;
  color: var(--ink);
  font-family: Vazirmatn, IRANSans, Tahoma, sans-serif;
}

* {
  box-sizing: border-box;
}

a {
  color: inherit;
  text-decoration: none;
}

button {
  font: inherit;
}

.header {
  max-width: 1200px;
  min-height: 86px;
  margin: 0 auto;
  padding: 16px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  border-bottom: 1px solid var(--line);
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  flex-shrink: 0;
}

.brand-icon {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  border-radius: 15px;
  background: var(--theme);
  color: white;
  font-size: 25px;
  font-weight: 800;
}

.brand-text {
  display: grid;
  gap: 4px;
}

.brand-text strong {
  font-size: 15px;
}

.brand-text small {
  color: var(--muted);
  font-size: 11px;
}

.desktop-nav {
  display: flex;
  align-items: center;
  gap: clamp(12px, 2vw, 26px);
}

.desktop-nav a {
  color: #66756b;
  font-size: 12px;
  transition: color 0.2s;
}

.desktop-nav a:hover {
  color: var(--theme-dark);
}

.menu-toggle,
.mobile-menu {
  display: none;
}

.hero {
  position: relative;
  max-width: 1200px;
  min-height: 280px;
  margin: 0 auto;
  padding: 55px 28px 30px;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 660px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--theme-dark);
  font-size: 12px;
  font-weight: 700;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: var(--theme);
  border-radius: 50%;
  box-shadow: 0 0 0 5px var(--theme-light);
}

.hero h1 {
  margin: 22px 0 13px;
  font-size: clamp(27px, 4vw, 43px);
  line-height: 1.7;
  letter-spacing: -1.2px;
  font-weight: 800;
}

.hero h1 span {
  color: var(--theme);
}

.hero-description {
  max-width: 480px;
  margin: 0;
  color: var(--muted);
  font-size: 14px;
  line-height: 2.1;
}

.hero-hint {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 23px;
  color: var(--theme-dark);
  font-size: 12px;
  font-weight: 700;
}

.hint-icon {
  width: 27px;
  height: 27px;
  display: grid;
  place-items: center;
  border: 1px solid #d9e6dd;
  border-radius: 50%;
}

.hero-decoration {
  position: absolute;
  left: 70px;
  top: 0;
  width: 290px;
  height: 270px;
  pointer-events: none;
}

.decoration-circle {
  position: absolute;
  border-radius: 48% 52% 62% 38% / 40% 46% 54% 60%;
}

.circle-one {
  width: 205px;
  height: 205px;
  left: 25px;
  top: 22px;
  background: var(--theme-light);
  transform: rotate(-15deg);
}

.circle-two {
  width: 132px;
  height: 132px;
  left: 88px;
  top: 62px;
  border: 1px solid #b9d0c1;
  background: transparent;
  transform: rotate(28deg);
}

.decoration-cross {
  position: absolute;
  left: 8px;
  top: 40px;
  color: var(--theme);
  font-size: 36px;
}

.decoration-pill {
  position: absolute;
  left: 0;
  bottom: 28px;
  padding: 11px 17px;
  border: 1px solid #e4ebe5;
  border-radius: 30px;
  background: #fff;
  color: var(--theme-dark);
  box-shadow: 0 10px 30px #294c3510;
  font-size: 11px;
}

.clinics-section {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px 28px 65px;
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 22px;
}

.section-kicker {
  color: var(--muted);
  font-size: 11px;
}

.section-heading h2 {
  margin: 7px 0 0;
  font-size: 24px;
  letter-spacing: -0.5px;
}

.clinic-count {
  padding: 8px 12px;
  border: 1px solid var(--line);
  border-radius: 30px;
  color: var(--muted);
  font-size: 11px;
  white-space: nowrap;
}

.clinic-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}

.clinic-card {
  padding: 26px;
  border: 1px solid var(--line);
  border-radius: 23px;
  background: white;
  box-shadow: 0 10px 35px #1b34240a;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.clinic-card:hover {
  transform: translateY(-5px);
  border-color: #bfd2c5;
  box-shadow: 0 18px 45px #1b342412;
}

.clinic-card.featured {
  border-color: #d5e3d9;
  background: linear-gradient(145deg, #ffffff 60%, #f6faf6);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.clinic-icon {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  border-radius: 17px;
  background: var(--theme-light);
  color: var(--theme-dark);
}

.clinic-icon svg {
  width: 29px;
  height: 29px;
}

.clinic-number {
  color: #b5c0b8;
  font-size: 13px;
  font-weight: 700;
}

.clinic-info {
  margin-top: 22px;
}

.clinic-info h3 {
  margin: 0 0 8px;
  font-size: 20px;
}

.clinic-info p {
  margin: 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.9;
}

.address-row {
  min-height: 52px;
  display: flex;
  align-items: center;
  gap: 9px;
  margin-top: 21px;
  padding: 13px 0;
  border-top: 1px solid var(--line);
  color: #77847b;
  font-size: 11px;
  line-height: 1.9;
}

.address-row svg {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
  color: var(--theme);
}

.appointment-link {
  min-height: 49px;
  margin-top: 10px;
  padding: 0 17px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-radius: 13px;
  background: var(--theme);
  color: white;
  font-size: 13px;
  font-weight: 700;
  transition: background 0.2s, transform 0.2s;
}

.appointment-link:hover {
  background: var(--theme-dark);
}

.appointment-link svg {
  width: 19px;
  height: 19px;
  transition: transform 0.2s;
}

.appointment-link:hover svg {
  transform: translateX(-4px);
}

.help-note {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin: 25px 0 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.9;
  text-align: center;
}

.help-icon {
  width: 18px;
  height: 18px;
  display: inline-grid;
  place-items: center;
  border: 1px solid #cbd7ce;
  border-radius: 50%;
  color: var(--theme-dark);
  font-size: 11px;
  flex-shrink: 0;
}

.footer {
  min-height: 66px;
  padding: 18px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  border-top: 1px solid var(--line);
  color: #89948c;
  font-size: 11px;
}

.floating-booking {
  position: fixed;
  z-index: 20;
  left: 24px;
  bottom: 24px;
  min-width: 66px;
  min-height: 66px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  border: 3px solid #ffffffc9;
  border-radius: 22px;
  background: var(--theme);
  color: white;
  box-shadow: 0 8px 28px #294c3530;
  font-size: 11px;
  font-weight: 700;
  transition: transform 0.2s, background 0.2s;
}

.floating-booking:hover {
  transform: translateY(-3px);
  background: var(--theme-dark);
}

.floating-booking svg {
  width: 23px;
  height: 23px;
}

@media (min-width: 1400px) {
  .hero {
    min-height: 310px;
  }
}

@media (max-width: 900px) {
  .desktop-nav {
    gap: 12px;
  }

  .desktop-nav a {
    font-size: 11px;
  }

  .hero-decoration {
    left: 15px;
    opacity: 0.65;
  }

  .hero-content {
    max-width: 75%;
  }
}

@media (max-width: 700px) {
  .header {
    min-height: 74px;
    padding: 13px 18px;
  }

  .brand-icon {
    width: 39px;
    height: 39px;
    border-radius: 13px;
  }

  .brand-text strong {
    font-size: 13px;
  }

  .desktop-nav {
    display: none;
  }

  .menu-toggle {
    width: 42px;
    height: 42px;
    padding: 11px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    border: 1px solid var(--line);
    border-radius: 13px;
    background: white;
    cursor: pointer;
  }

  .menu-toggle span {
    width: 100%;
    height: 2px;
    border-radius: 2px;
    background: var(--theme-dark);
  }

  .mobile-menu {
    position: absolute;
    z-index: 10;
    top: 70px;
    right: 14px;
    left: 14px;
    padding: 9px;
    display: grid;
    border: 1px solid var(--line);
    border-radius: 17px;
    background: white;
    box-shadow: 0 15px 40px #1b342418;
  }

  .mobile-menu a {
    padding: 13px;
    border-radius: 10px;
    color: #5e7064;
    font-size: 13px;
  }

  .mobile-menu a:hover {
    background: var(--theme-light);
  }

  .hero {
    min-height: 245px;
    padding: 38px 20px 22px;
  }

  .hero-content {
    max-width: 100%;
  }

  .hero h1 {
    font-size: clamp(25px, 7vw, 34px);
    line-height: 1.8;
  }

  .hero-description {
    max-width: 95%;
    font-size: 12px;
  }

  .hero-decoration {
    left: -110px;
    top: 12px;
    opacity: 0.25;
  }

  .hero-hint {
    margin-top: 18px;
  }

  .clinics-section {
    padding: 22px 18px 45px;
  }

  .section-heading h2 {
    font-size: 22px;
  }

  .clinic-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .clinic-card {
    padding: 22px;
    border-radius: 19px;
  }

  .clinic-info {
    margin-top: 17px;
  }

  .address-row {
    margin-top: 15px;
  }

  .help-note {
    align-items: flex-start;
    text-align: right;
  }

  .footer {
    padding: 17px 18px;
    font-size: 10px;
  }

  .floating-booking {
    left: 15px;
    bottom: 15px;
    min-width: 60px;
    min-height: 60px;
    border-radius: 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    transition: none !important;
  }
}
.hero-decoration {
  position: absolute;
  left: 55px;
  top: 0;
  width: 290px;
  height: 290px;
  pointer-events: none;
}

.circle-one {
  position: absolute;
  width: 235px;
  height: 235px;
  left: 20px;
  top: 20px;
  border-radius: 50%;
  background: var(--theme-light);
}

.doctor-circle {
  position: absolute;
  left: 36px;
  top: 36px;
  width: 203px;
  height: 203px;
  overflow: hidden;
  border: 6px solid white;
  border-radius: 50%;
  background: var(--theme-light);
  box-shadow: 0 12px 35px #26372f20;
}

.doctor-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
}

.decoration-pill {
  position: absolute;
  left: 5px;
  bottom: 22px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  border: 1px solid var(--theme-border);
  border-radius: 30px;
  background: white;
  color: var(--theme-dark);
  box-shadow: 0 8px 25px #26372f12;
  font-size: 12px;
  font-weight: 700;
}

.pill-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--theme);
}

.decoration-cross {
  position: absolute;
  right: 0;
  top: 32px;
  color: var(--theme);
  font-size: 30px;
}

@media (max-width: 700px) {
  .hero-decoration {
    position: relative;
    left: auto;
    top: auto;
    width: 260px;
    height: 260px;
    margin: 0 auto;
    opacity: 1;
  }

  .circle-one {
    width: 220px;
    height: 220px;
  }

  .doctor-circle {
    width: 190px;
    height: 190px;
  }
}
</style>

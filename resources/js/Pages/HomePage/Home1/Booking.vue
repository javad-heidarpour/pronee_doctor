<template>
  <PublicPageLayout
    :doctor="doctor"
    :showBooking="false"
    title="دریافت نوبت"
    subtitle="          مطب موردنظر، روز و ساعت مناسب خود را انتخاب کنید و نوبت خود را به‌صورت آنلاین
          ثبت نمایید.
"
    eyebrow="          نوبت ویزیت خود را
         آسان و سریع
          دریافت کنید
"
  >
    <main dir="rtl" class="booking-page">
      <!-- Main Content -->
      <div class="booking-container">
        <!-- Steps -->
        <section class="steps-card">
          <div class="step" :class="{ 'step-active': selectedClinicIndex !== null }">
            <span class="step-number">۱</span>
            <div>
              <strong>انتخاب مطب</strong>
              <small>موقعیت موردنظر</small>
            </div>
          </div>

          <div class="step-line" :class="{ 'line-active': isClinicSelected }"></div>

          <div class="step" :class="{ 'step-active': isClinicSelected }">
            <span class="step-number">۲</span>
            <div>
              <strong>انتخاب تاریخ</strong>
              <small>روز مراجعه</small>
            </div>
          </div>

          <div class="step-line" :class="{ 'line-active': !!selectedSlot }"></div>

          <div class="step" :class="{ 'step-active': !!selectedSlot }">
            <span class="step-number">۳</span>
            <div>
              <strong>انتخاب ساعت</strong>
              <small>زمان ویزیت</small>
            </div>
          </div>
        </section>

        <!-- No clinics -->
        <section v-if="!clinics.length" class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
              <rect x="3" y="4" width="18" height="17" rx="3" />
              <path d="M8 2V6M16 2V6M3 10H21" />
              <path d="M12 13V17M10 15H14" />
            </svg>
          </div>
          <h2>در حال حاضر نوبتی تعریف نشده است</h2>
          <p>
            هنوز زمان مراجعه‌ای برای این پزشک ثبت نشده یا تمامی نوبت‌ها رزرو شده‌اند.
            لطفاً در روزهای آینده مجدداً بررسی کنید.
          </p>
        </section>

        <template v-else>
          <!-- Clinic Selection -->
          <section class="content-card">
            <div class="section-heading">
              <div class="heading-icon">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M3 21H21M5 21V7L13 3V21M19 21V11L13 7" />
                  <path d="M9 9V9.01M9 13V13.01M9 17V17.01M16 13V13.01M16 17V17.01" />
                </svg>
              </div>
              <div>
                <h2>انتخاب مطب</h2>
                <p>مکانی را که قصد مراجعه به آن را دارید انتخاب کنید.</p>
              </div>
              <span class="section-count">{{ clinics.length }} مطب</span>
            </div>

            <div
              class="clinic-grid"
              :class="{ 'clinic-grid-selected': selectedClinicIndex !== null }"
            >
              <button
                v-for="(item, index) in clinics"
                :key="item.clinic.id"
                v-show="selectedClinicIndex === null || selectedClinicIndex === index"
                type="button"
                class="clinic-card"
                :class="{ 'clinic-selected': selectedClinicIndex === index }"
                @click="selectClinic(index)"
              >
                <div class="clinic-card-top">
                  <div class="clinic-icon">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                    >
                      <path d="M3 21H21M5 21V7L13 3V21M19 21V11L13 7" />
                      <path d="M9 9V9.01M9 13V13.01M9 17V17.01" />
                    </svg>
                  </div>

                  <span class="clinic-check">
                    <svg
                      v-if="selectedClinicIndex === index"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M16.704 5.29a1 1 0 010 1.415l-7.1 7.1a1 1 0 01-1.415 0l-3.6-3.6a1 1 0 011.415-1.415l2.893 2.893 6.392-6.393a1 1 0 011.415 0z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </span>
                </div>

                <h3>{{ item.clinic.name }}</h3>

                <p class="clinic-address">
                  {{ item.clinic.city?.name || "مطب پزشک" }}
                </p>

                <div class="clinic-meta">
                  <span>
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                    >
                      <rect x="3" y="4" width="18" height="17" rx="3" />
                      <path d="M8 2V6M16 2V6M3 10H21" />
                    </svg>
                    {{ item.dates.length }} روز دارای نوبت
                  </span>

                  <span class="clinic-select-label">
                    {{ selectedClinicIndex === index ? "انتخاب شده" : "انتخاب مطب" }}
                  </span>
                </div>
              </button>
            </div>
          </section>

          <!-- Date and Time -->
          <section v-if="isClinicSelected" class="content-card schedule-card">
            <div class="section-heading">
              <div class="heading-icon">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <rect x="3" y="4" width="18" height="17" rx="3" />
                  <path d="M8 2V6M16 2V6M3 10H21" />
                </svg>
              </div>
              <div>
                <h2>زمان مراجعه</h2>
                <p>روز و ساعت مناسب خود را انتخاب کنید.</p>
              </div>

              <button type="button" class="change-clinic" @click="changeClinic">
                تغییر مطب
              </button>
            </div>

            <!-- Selected Clinic -->
            <div class="selected-clinic-banner">
              <div class="selected-clinic-icon">✓</div>
              <div>
                <small>مطب انتخاب‌شده</small>
                <strong>{{ selectedClinic?.name }}</strong>
              </div>
            </div>

            <!-- Dates -->
            <div class="subsection-heading">
              <span class="subsection-number">۱</span>
              <div>
                <h3>روز مراجعه را انتخاب کنید</h3>
                <p>تاریخ‌های دارای نوبت نمایش داده شده‌اند.</p>
              </div>
            </div>

            <div class="date-wrapper">
              <button
                v-if="canScrollDateRight"
                class="scroll-button scroll-right"
                type="button"
                @click="scrollDates('right')"
                aria-label="حرکت به راست"
              >
                ‹
              </button>

              <div
                ref="dateStripRef"
                class="date-strip"
                @scroll="checkDateScrollPosition"
              >
                <button
                  v-for="(date, index) in currentClinicData.dates"
                  :key="date.date"
                  type="button"
                  class="date-card"
                  :class="{ 'date-selected': selectedDateIndex === index }"
                  @click="selectDate(index)"
                >
                  <span class="date-weekday">{{ getPersianDayName(date.date) }}</span>
                  <strong>{{ getPersianDayNumber(date.date) }}</strong>
                  <span class="date-month">{{ getPersianMonthName(date.date) }}</span>
                  <span class="date-dot"></span>
                </button>
              </div>

              <button
                v-if="canScrollDateLeft"
                class="scroll-button scroll-left"
                type="button"
                @click="scrollDates('left')"
                aria-label="حرکت به چپ"
              >
                ›
              </button>
            </div>

            <!-- Time Slots -->
            <div class="subsection-heading time-heading">
              <span class="subsection-number">۲</span>
              <div>
                <h3>ساعت مراجعه را انتخاب کنید</h3>
                <p>ساعت‌های سبز قابل رزرو هستند.</p>
              </div>
            </div>

            <div v-if="currentDateData?.slots?.length" class="time-grid">
              <button
                v-for="slot in currentDateData.slots"
                :key="slot.id"
                type="button"
                class="time-card"
                :class="{
                  'time-selected': selectedSlot?.id === slot.id,
                  'time-booked': slot.status !== 'available',
                }"
                :disabled="slot.status !== 'available'"
                @click="selectSlot(slot)"
              >
                <svg
                  v-if="slot.status === 'available'"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 7V12L15 14" />
                </svg>

                <span>{{ formatTime(slot.start_time) }}</span>

                <small v-if="slot.status === 'booked'">رزرو شده</small>
                <small v-else-if="selectedSlot?.id === slot.id">انتخاب شما</small>
                <small v-else>آزاد</small>
              </button>
            </div>

            <div v-else class="no-slots">
              <span class="no-slots-icon">◷</span>
              <strong>برای این روز نوبت خالی وجود ندارد.</strong>
              <p>لطفاً تاریخ دیگری را انتخاب کنید.</p>
            </div>

            <!-- Legend -->
            <div class="time-legend">
              <span><i class="legend-free"></i> نوبت آزاد</span>
              <span><i class="legend-selected"></i> انتخاب شده</span>
              <span><i class="legend-booked"></i> رزرو شده</span>
            </div>

            <!-- Selected Slot -->
            <div v-if="selectedSlot" class="selection-summary">
              <div class="summary-check">✓</div>
              <div>
                <small>زمان انتخابی شما</small>
                <strong>
                  {{ getPersianDayName(currentDateData.date) }}
                  {{ getPersianDayNumber(currentDateData.date) }}
                  {{ getPersianMonthName(currentDateData.date) }}
                  - ساعت {{ formatTime(selectedSlot.start_time) }}
                </strong>
              </div>
            </div>

            <div
              v-if="availabilityMessage"
              class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800"
              role="status"
              aria-live="polite"
            >
              {{ availabilityMessage }}
            </div>

            <!-- Booking Action -->
            <div class="booking-action">
              <BookingSectionActionButton
                :visitReasons="visitReasons"
                :selected-slot="selectedSlot"
                :current-date-data="currentDateData"
                @success="handleBookingSuccess"
                @slot-cleared="handleSlotCleared"
                @slot-booked="handleSlotBooked"
              />
            </div>
          </section>

          <!-- Clinic Information -->
          <section
            v-if="isClinicSelected && selectedClinic"
            class="content-card location-card"
          >
            <div class="section-heading">
              <div class="heading-icon location-heading-icon">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M20 10C20 15 12 22 12 22S4 15 4 10a8 8 0 1116 0Z" />
                  <circle cx="12" cy="10" r="2.5" />
                </svg>
              </div>
              <div>
                <h2>اطلاعات و موقعیت مطب</h2>
                <p>مشخصات محل مراجعه خود را بررسی کنید.</p>
              </div>
            </div>

            <div class="location-details">
              <div class="location-detail">
                <span class="detail-icon">⌖</span>
                <div>
                  <small>نشانی مطب</small>
                  <strong>{{
                    selectedClinic.full_address || "نشانی ثبت نشده است"
                  }}</strong>
                </div>
              </div>

              <div v-if="selectedClinic.city?.name" class="location-detail">
                <span class="detail-icon">⌂</span>
                <div>
                  <small>شهر</small>
                  <strong>{{ selectedClinic.city.name }}</strong>
                </div>
              </div>

              <div v-if="selectedClinic.phone" class="location-detail">
                <span class="detail-icon">☎</span>
                <div>
                  <small>تلفن تماس</small>
                  <a :href="`tel:${selectedClinic.phone}`">
                    {{ selectedClinic.phone }}
                  </a>
                </div>
              </div>
            </div>

            <div v-if="hasCoordinates" class="map-container">
              <iframe
                :src="getMapUrl(selectedClinic.Latitude, selectedClinic.Longitude)"
                title="موقعیت مطب روی نقشه"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
            </div>

            <div v-if="hasCoordinates" class="map-links">
              <a
                :href="
                  getGoogleMapsLink(selectedClinic.Latitude, selectedClinic.Longitude)
                "
                target="_blank"
                rel="noopener noreferrer"
                >مسیریابی گوگل</a
              >

              <a
                :href="getOSMLink(selectedClinic.Latitude, selectedClinic.Longitude)"
                target="_blank"
                rel="noopener noreferrer"
                >OpenStreetMap</a
              >

              <a
                :href="getNeshanUrl(selectedClinic.Latitude, selectedClinic.Longitude)"
                target="_blank"
                rel="noopener noreferrer"
                >نقشه نشان</a
              >
            </div>
          </section>
        </template>
      </div>
    </main>
  </PublicPageLayout>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import BookingSectionActionButton from "@/Components/HomePage/Booking/BookingSectionActionButton.vue";
import { Link } from "@inertiajs/vue3";
import PublicPageLayout from "@/Layouts/PublicPageLayout.vue";

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  clinicsWithSlots: {
    type: Array,
    default: () => [],
  },
  visitReasons: {
    type: Array,
    default: () => [],
  },
  clinicId: {
    type: [Number, String],
    default: null,
  },
});

const clinics = computed(() =>
  (props.clinicsWithSlots?.length ? props.clinicsWithSlots : props.doctor?.clinics_with_slots ?? []).filter(
    (item) => item.clinic?.id && item.clinic?.name
  )
);

const selectedClinicIndex = ref(null);
const selectedDateIndex = ref(0);
const selectedSlot = ref(null);
const availabilityMessage = ref("");

const isClinicSelected = computed(() => selectedClinicIndex.value !== null);

const currentClinicData = computed(
  () => clinics.value[selectedClinicIndex.value] ?? { dates: [] }
);

const selectedClinic = computed(() => currentClinicData.value?.clinic ?? null);

const currentDateData = computed(() => {
  const dates = currentClinicData.value?.dates ?? [];
  return dates[selectedDateIndex.value] ?? { slots: [] };
});

const hasCoordinates = computed(() => {
  const clinic = selectedClinic.value;
  return (
    clinic?.Latitude != null &&
    clinic?.Longitude != null &&
    clinic.Latitude !== "" &&
    clinic.Longitude !== ""
  );
});

function selectClinic(index) {
  selectedClinicIndex.value = index;
  selectedDateIndex.value = 0;
  selectedSlot.value = null;
  availabilityMessage.value = "";

  const dates = clinics.value[index]?.dates ?? [];

  const firstAvailable = dates.findIndex((date) =>
    date.slots?.some((slot) => slot.status === "available")
  );

  if (firstAvailable >= 0) {
    selectedDateIndex.value = firstAvailable;
  }

  nextTick(updateScrollPositions);
}

function changeClinic() {
  selectedClinicIndex.value = null;
  selectedDateIndex.value = 0;
  selectedSlot.value = null;
  availabilityMessage.value = "";
}

function selectDate(index) {
  selectedDateIndex.value = index;
  selectedSlot.value = null;
  availabilityMessage.value = "";
  nextTick(updateScrollPositions);
}

function selectSlot(slot) {
  if (slot.status === "available") {
    selectedSlot.value = slot;
    availabilityMessage.value = "";
  }
}

// انتخاب مطب از طریق URL
function syncClinicFromProps() {
  if (!clinics.value.length) return;

  if (props.clinicId !== null && props.clinicId !== "") {
    const index = clinics.value.findIndex(
      (item) => String(item.clinic.id) === String(props.clinicId)
    );

    if (index >= 0) {
      selectClinic(index);
      return;
    }
  }

  selectedClinicIndex.value = null;
}

watch(() => [props.clinicId, clinics.value], syncClinicFromProps, {
  immediate: true,
  deep: true,
});

// تاریخ شمسی
function parseLocalDate(date) {
  return new Date(`${date}T12:00:00`);
}

function getPersianDayName(date) {
  return new Intl.DateTimeFormat("fa-IR", {
    weekday: "long",
  }).format(parseLocalDate(date));
}

function getPersianDayNumber(date) {
  return new Intl.DateTimeFormat("fa-IR", {
    day: "numeric",
  }).format(parseLocalDate(date));
}

function getPersianMonthName(date) {
  return new Intl.DateTimeFormat("fa-IR", {
    month: "long",
  }).format(parseLocalDate(date));
}

function formatTime(time) {
  return String(time ?? "")
    .slice(0, 5)
    .replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[digit]);
}

// Scroll dates
const dateStripRef = ref(null);
const canScrollDateLeft = ref(false);
const canScrollDateRight = ref(false);

function checkDateScrollPosition() {
  const el = dateStripRef.value;
  if (!el) return;

  const maxScroll = el.scrollWidth - el.clientWidth;

  canScrollDateLeft.value = el.scrollLeft < -5;
  canScrollDateRight.value = el.scrollLeft > -maxScroll + 5;

  // پشتیبانی از مدل‌های مختلف RTL مرورگر
  if (el.scrollLeft >= 0) {
    canScrollDateLeft.value = el.scrollLeft > 5;
    canScrollDateRight.value = el.scrollLeft < maxScroll - 5;
  }
}

function scrollDates(direction) {
  dateStripRef.value?.scrollBy({
    left: direction === "right" ? 240 : -240,
    behavior: "smooth",
  });
}

function updateScrollPositions() {
  checkDateScrollPosition();
}

watch(
  () => currentClinicData.value?.dates,
  () => nextTick(updateScrollPositions),
  { deep: true }
);

onMounted(() => {
  nextTick(updateScrollPositions);
});

// Map
function getMapUrl(lat, lng) {
  const latitude = Number(lat);
  const longitude = Number(lng);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    return "";
  }

  const bbox = [
    longitude - 0.002,
    latitude - 0.002,
    longitude + 0.002,
    latitude + 0.002,
  ].join(",");

  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${latitude},${longitude}`;
}

const getGoogleMapsLink = (lat, lng) =>
  `https://www.google.com/maps/place/?api=1&destination=${lat},${lng}`;

const getOSMLink = (lat, lng) =>
  `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}`;

const getNeshanUrl = (lat, lng) =>
  `https://neshan.org/maps/iframe?lat=${lat}&lng=${lng}&zoom=15`;

// Booking events
function handleBookingSuccess() {
  selectedSlot.value = null;
}

function handleSlotCleared(message = "") {
  selectedSlot.value = null;
  availabilityMessage.value = message || "این ساعت دیگر آزاد نیست؛ لطفاً زمان دیگری انتخاب کنید.";
}

function handleSlotBooked(slotId) {
  const clinic = clinics.value[selectedClinicIndex.value];
  const date = clinic?.dates?.[selectedDateIndex.value];

  const slot = date?.slots?.find((item) => item.id === slotId);

  if (slot) {
    slot.status = "booked";
  }

  selectedSlot.value = null;
}
</script>
<style scoped>
.booking-page {
  --green: #087f5b;
  --green-dark: #075c45;
  --green-light: #e8f6ef;
  --text: #24352f;
  --muted: #82918a;

  display: block;
  position: relative;

  width: 100%;
  max-width: 100%;
  min-width: 0;

  min-height: auto;

  margin: 0;
  padding: 20px 0 50px;

  background: transparent;

  color: var(--text);
  font-family: inherit;

  box-sizing: border-box;
}
.booking-hero {
  position: relative;
  overflow: hidden;
  max-width: 1150px;
  min-height: 260px;
  margin: 0 auto 30px;
  padding: 42px 55px;
  border-radius: 30px;
  background: linear-gradient(120deg, #075c45, #07825e 65%, #14a477);
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 18px 45px rgba(5, 100, 70, 0.19);
}

.hero-glow {
  position: absolute;
  width: 280px;
  height: 280px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.12);
  pointer-events: none;
}

.hero-glow-one {
  top: -180px;
  left: 15%;
}

.hero-glow-two {
  bottom: -220px;
  right: 25%;
  width: 380px;
  height: 380px;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 650px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 8px 14px;
  margin-bottom: 20px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 30px;
  background: rgba(255, 255, 255, 0.12);
  font-size: 12px;
  backdrop-filter: blur(10px);
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #a7f3d0;
  box-shadow: 0 0 0 5px rgba(167, 243, 208, 0.15);
}

.hero-content h1 {
  margin: 0;
  font-size: clamp(25px, 3.3vw, 39px);
  font-weight: 900;
  line-height: 1.7;
}

.hero-content h1 span {
  color: #b5f5d7;
}

.hero-content > p {
  margin-top: 10px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  line-height: 2;
}

.hero-features {
  display: flex;
  flex-wrap: wrap;
  gap: 22px;
  margin-top: 23px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.9);
}

.hero-features > div {
  display: flex;
  align-items: center;
  gap: 7px;
}

.feature-icon {
  display: grid;
  place-items: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  color: #b5f5d7;
  font-weight: 900;
}

.hero-decoration {
  position: relative;
  width: 220px;
  height: 190px;
  flex: none;
  display: grid;
  place-items: center;
}

.decoration-circle {
  width: 150px;
  height: 150px;
  display: grid;
  place-items: center;
  border-radius: 45px;
  transform: rotate(-8deg);
  background: rgba(255, 255, 255, 0.13);
  border: 1px solid rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(12px);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.decoration-circle svg {
  width: 105px;
  transform: rotate(8deg);
}

.floating-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 13px;
  background: rgba(255, 255, 255, 0.95);
  color: #087f5b;
  border-radius: 13px;
  font-size: 11px;
  font-weight: 800;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.floating-card-top {
  top: 5px;
  right: -5px;
}

.floating-card-bottom {
  bottom: 5px;
  left: -5px;
}

.floating-card-bottom span {
  font-weight: 900;
}

.booking-container {
  width: 100%;
  max-width: 1050px;
  min-width: 0;

  margin: 0 auto;
  box-sizing: border-box;
}

.steps-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 23px;
  margin-bottom: 25px;
  border: 1px solid #e8efeb;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.8);
  box-shadow: 0 5px 22px rgba(30, 70, 50, 0.04);
}

.step {
  display: flex;
  align-items: center;
  gap: 11px;
  color: #a2ada7;
  transition: 0.25s;
}

.step-number {
  width: 39px;
  height: 39px;
  display: grid;
  place-items: center;
  flex: none;
  border-radius: 13px;
  background: #f0f3f1;
  font-weight: 900;
}

.step strong,
.step small {
  display: block;
}

.step strong {
  font-size: 13px;
}

.step small {
  margin-top: 4px;
  font-size: 10px;
}

.step-active {
  color: var(--green);
}

.step-active .step-number {
  background: var(--green);
  color: white;
  box-shadow: 0 5px 13px rgba(8, 127, 91, 0.2);
}

.step-line {
  width: 55px;
  height: 2px;
  background: #e9eeeb;
}

.line-active {
  background: #36b58b;
}

.content-card {
  padding: 30px;
  margin-bottom: 24px;
  border: 1px solid #e9efeb;
  border-radius: 25px;
  background: rgba(255, 255, 255, 0.93);
  box-shadow: 0 8px 28px rgba(30, 70, 50, 0.045);
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 25px;
}

.heading-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  flex: none;
  border-radius: 15px;
  color: var(--green);
  background: var(--green-light);
}

.heading-icon svg {
  width: 25px;
  height: 25px;
}

.section-heading h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 900;
}

.section-heading p {
  margin: 5px 0 0;
  font-size: 11px;
  color: var(--muted);
}

.section-count {
  margin-right: auto;
  padding: 7px 12px;
  border-radius: 20px;
  background: #f0f7f3;
  color: var(--green);
  font-size: 11px;
  font-weight: 800;
}

.clinic-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.clinic-card {
  text-align: right;
  min-width: 0;
  padding: 20px;
  border: 1.5px solid #e8eeea;
  border-radius: 20px;
  background: linear-gradient(145deg, #fff, #fbfdfb);
  cursor: pointer;
  transition: 0.25s ease;
}

.clinic-card:hover {
  border-color: #8bd6b7;
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(8, 127, 91, 0.08);
}

.clinic-selected {
  border-color: var(--green);
  background: linear-gradient(145deg, #f0faf5, #fff);
  box-shadow: 0 0 0 3px rgba(8, 127, 91, 0.07);
}

.clinic-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.clinic-icon {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  border-radius: 13px;
  background: #eaf6ef;
  color: var(--green);
}

.clinic-icon svg {
  width: 24px;
}

.clinic-check {
  width: 25px;
  height: 25px;
  display: grid;
  place-items: center;
  border: 1.5px solid #dce5df;
  border-radius: 50%;
  color: white;
}

.clinic-selected .clinic-check {
  border-color: var(--green);
  background: var(--green);
}

.clinic-check svg {
  width: 15px;
}

.clinic-card h3 {
  margin: 17px 0 5px;
  font-size: 15px;
  font-weight: 900;
}

.clinic-address {
  color: var(--muted);
  font-size: 11px;
}

.clinic-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding-top: 15px;
  margin-top: 15px;
  border-top: 1px solid #edf1ee;
}

.clinic-meta > span:first-child {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  color: #718078;
}

.clinic-meta svg {
  width: 15px;
  height: 15px;
}

.clinic-select-label {
  font-size: 10px;
  color: var(--green);
  font-weight: 800;
}

.schedule-card {
  border-top: 3px solid var(--green);
}

.change-clinic {
  margin-right: auto;
  padding: 8px 12px;
  border-radius: 10px;
  background: #f0f7f3;
  color: var(--green);
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: 0.2s;
}

.change-clinic:hover {
  background: var(--green);
  color: white;
}

.selected-clinic-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 17px;
  margin-bottom: 30px;
  border: 1px solid #d9f0e3;
  border-radius: 15px;
  background: #f2faf5;
}

.selected-clinic-icon {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: var(--green);
  color: white;
  font-weight: 900;
}

.selected-clinic-banner small,
.selected-clinic-banner strong {
  display: block;
}

.selected-clinic-banner small {
  color: var(--muted);
  font-size: 10px;
}

.selected-clinic-banner strong {
  margin-top: 4px;
  font-size: 13px;
  color: var(--green-dark);
}

.subsection-heading {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 17px;
}

.subsection-number {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: #eaf6ef;
  color: var(--green);
  font-size: 13px;
  font-weight: 900;
}

.subsection-heading h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 900;
}

.subsection-heading p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 10px;
}

.date-wrapper {
  position: relative;
  margin: 0 0 32px;
}

.date-strip {
  display: flex;
  gap: 11px;
  padding: 5px 3px 13px;
  overflow-x: auto;
  scrollbar-width: none;
  scroll-behavior: smooth;
}

.date-strip::-webkit-scrollbar {
  display: none;
}

.date-card {
  position: relative;
  flex: 0 0 86px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 14px 8px 16px;
  border: 1.5px solid #e9efeb;
  border-radius: 17px;
  background: #fff;
  cursor: pointer;
  transition: 0.2s;
}

.date-card:hover {
  border-color: #8bd6b7;
  background: #f7fcf9;
}

.date-selected {
  border-color: var(--green);
  background: var(--green);
  color: white;
  box-shadow: 0 7px 17px rgba(8, 127, 91, 0.19);
  transform: translateY(-3px);
}

.date-weekday {
  font-size: 10px;
  color: #8b9991;
}

.date-selected .date-weekday,
.date-selected .date-month {
  color: rgba(255, 255, 255, 0.8);
}

.date-card strong {
  margin: 8px 0;
  font-size: 25px;
  font-weight: 900;
}

.date-month {
  font-size: 10px;
  color: #8b9991;
}

.date-dot {
  width: 5px;
  height: 5px;
  margin-top: 9px;
  border-radius: 50%;
  background: #32b783;
}

.date-selected .date-dot {
  background: white;
}

.scroll-button {
  position: absolute;
  top: 50%;
  z-index: 2;
  width: 32px;
  height: 32px;
  border: 1px solid #e4ebe6;
  border-radius: 50%;
  background: white;
  color: var(--green);
  font-size: 22px;
  cursor: pointer;
  transform: translateY(-50%);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
}

.scroll-right {
  right: -12px;
}

.scroll-left {
  left: -12px;
}

.time-heading {
  margin-top: 10px;
}

.time-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
  gap: 12px;
}

.time-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  padding: 15px 8px;
  border: 1.5px solid #d5ede1;
  border-radius: 15px;
  background: #f5fbf7;
  color: var(--green);
  cursor: pointer;
  transition: 0.2s;
}

.time-card:hover:not(:disabled) {
  border-color: var(--green);
  background: #e7f7ee;
  transform: translateY(-2px);
}

.time-card svg {
  width: 19px;
  height: 19px;
}

.time-card span {
  font-size: 17px;
  font-weight: 900;
}

.time-card small {
  font-size: 10px;
}

.time-selected {
  border-color: var(--green);
  background: var(--green);
  color: white;
  box-shadow: 0 6px 16px rgba(8, 127, 91, 0.2);
}

.time-booked {
  border-color: #eceeed;
  background: #f2f3f3;
  color: #b3bbb6;
  cursor: not-allowed;
  opacity: 0.75;
}

.time-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  padding-top: 20px;
  margin-top: 22px;
  border-top: 1px solid #edf1ee;
}

.time-legend span {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  color: #79877f;
}

.time-legend i {
  width: 9px;
  height: 9px;
  border-radius: 3px;
}

.legend-free {
  background: #48b98b;
}

.legend-selected {
  background: #087f5b;
}

.legend-booked {
  background: #cbd1cd;
}

.selection-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  margin-top: 22px;
  border: 1px solid #bde9d1;
  border-radius: 16px;
  background: linear-gradient(120deg, #f0faf4, #fafffc);
}

.summary-check {
  width: 35px;
  height: 35px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: var(--green);
  color: white;
  font-weight: 900;
}

.selection-summary small,
.selection-summary strong {
  display: block;
}

.selection-summary small {
  color: #7d9487;
  font-size: 10px;
}

.selection-summary strong {
  margin-top: 5px;
  color: var(--green-dark);
  font-size: 12px;
}

.booking-action {
  margin-top: 25px;
}

.no-slots,
.empty-state {
  text-align: center;
  padding: 35px 20px;
  border: 1px dashed #dce8e0;
  border-radius: 19px;
  background: #fafcfb;
}

.no-slots-icon {
  display: block;
  margin-bottom: 12px;
  color: #9eafa4;
  font-size: 35px;
}

.no-slots strong {
  font-size: 13px;
}

.no-slots p {
  margin-top: 7px;
  color: var(--muted);
  font-size: 11px;
}

.empty-state {
  padding: 55px 25px;
  background: white;
  border-style: solid;
}

.empty-icon {
  width: 70px;
  height: 70px;
  display: grid;
  place-items: center;
  margin: 0 auto 20px;
  border-radius: 23px;
  background: #fff6e7;
  color: #d99b32;
}

.empty-icon svg {
  width: 38px;
}

.empty-state h2 {
  font-size: 17px;
  font-weight: 900;
}

.empty-state p {
  max-width: 470px;
  margin: 10px auto 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 2;
}

.location-heading-icon {
  background: #eaf2ff;
  color: #4382ce;
}

.location-details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.location-detail {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.detail-icon {
  width: 37px;
  height: 37px;
  flex: none;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #f0f5f2;
  color: var(--green);
  font-size: 20px;
}

.location-detail small,
.location-detail strong,
.location-detail a {
  display: block;
}

.location-detail small {
  margin-bottom: 5px;
  color: var(--muted);
  font-size: 10px;
}

.location-detail strong,
.location-detail a {
  color: #34483d;
  font-size: 12px;
  line-height: 1.9;
  text-decoration: none;
}

.location-detail a {
  color: #277ac1;
}

.map-container {
  overflow: hidden;
  margin-top: 23px;
  border-radius: 17px;
  border: 1px solid #e8eeea;
}

.map-container iframe {
  display: block;
  width: 100%;
  height: 260px;
  border: 0;
}

.map-links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 13px;
}

.map-links a {
  padding: 9px 13px;
  border-radius: 10px;
  background: #f0f7f3;
  color: var(--green);
  font-size: 10px;
  font-weight: 800;
  text-decoration: none;
  transition: 0.2s;
}

.map-links a:hover {
  background: var(--green);
  color: white;
}

/* Responsive */
@media (max-width: 767px) {
   .booking-page {
    width: 100%;
    max-width: 100%;
    padding: 15px 0 35px;
    margin: 0;
  }

  .booking-container {
    width: 100%;
    max-width: 100%;
  }


  .booking-hero {
    min-height: 0;
    padding: 28px 23px;
    border-radius: 23px;
  }

  .hero-content {
    max-width: 100%;
  }

  .hero-content h1 {
    font-size: 25px;
  }

  .hero-content > p {
    font-size: 12px;
  }

  .hero-decoration {
    display: none;
  }

  .hero-features {
    gap: 10px 15px;
    font-size: 10px;
  }

  .steps-card {
    gap: 8px;
    padding: 16px 10px;
    border-radius: 17px;
  }

  .step {
    gap: 6px;
  }

  .step-number {
    width: 31px;
    height: 31px;
    border-radius: 10px;
    font-size: 12px;
  }

  .step strong {
    font-size: 10px;
  }

  .step small {
    display: none;
  }

  .step-line {
    width: 15px;
  }

  .content-card {
    padding: 19px 15px;
    border-radius: 20px;
  }

  .section-heading {
    gap: 10px;
  }

  .heading-icon {
    width: 40px;
    height: 40px;
  }

  .section-heading h2 {
    font-size: 14px;
  }

  .section-heading p {
    font-size: 10px;
  }

  .clinic-grid {
    grid-template-columns: 1fr;
    gap: 11px;
  }

  .clinic-card {
    padding: 16px;
  }

  .clinic-card h3 {
    font-size: 14px;
  }

  .date-card {
    flex-basis: 76px;
  }

  .time-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
  }

  .time-card {
    padding: 13px 5px;
  }

  .time-card span {
    font-size: 15px;
  }

  .location-details {
    grid-template-columns: 1fr;
    gap: 15px;
  }

  .map-container iframe {
    height: 210px;
  }

  .selection-summary strong {
    font-size: 11px;
    line-height: 1.9;
  }
}

@media (max-width: 380px) {
    .booking-page {
    padding-inline: 0;
  }

  .booking-container {
    width: 100%;
  }

  .hero-content h1 {
    font-size: 22px;
  }

  .hero-features {
    gap: 9px;
  }

  .steps-card {
    gap: 5px;
  }

  .step strong {
    font-size: 9px;
  }

  .time-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .section-count {
    font-size: 9px;
    padding: 6px 8px;
  }
}
</style>

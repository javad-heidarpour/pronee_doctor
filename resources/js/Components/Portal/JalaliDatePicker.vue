  <!-- resources/js/Components/Doctor/JalaliDatePicker.vue -->
  <template>
    <div class="relative" ref="datePickerRef">
      <!-- Input -->
      <div class="relative">
        <input
          :value="displayValue"
          type="text"
          readonly
          :placeholder="placeholder"
          @click="toggleDropdown"
          :class="[
            'w-full px-4 py-3 pr-11 border rounded-xl cursor-pointer transition-all bg-white',
            hasError ? 'border-red-500 focus:ring-red-500' : 'border-gray-200 focus:ring-teal-500',
            'focus:ring-2 focus:border-transparent'
          ]"
        />
        <svg
          class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <button
          v-if="modelValue"
          @click.stop="clearDate"
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 hover:text-red-500 transition-colors"
        >
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Dropdown تقویم -->
      <Transition name="dropdown">
        <div
          v-if="isOpen"
          class="absolute z-50 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 p-4 w-80"
          :class="position === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'"
        >
          <!-- Header: انتخاب ماه و سال -->
          <div class="flex items-center justify-between mb-4">
            <button
              @click="prevMonth"
              class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <div class="flex gap-2">
              <!-- انتخاب ماه -->
              <select
                v-model="selectedMonth"
                @change="onMonthYearChange"
                class="px-2 py-1 text-sm font-bold text-gray-700 bg-gray-50 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer"
              >
                <option v-for="(month, idx) in persianMonths" :key="idx" :value="idx + 1">
                  {{ month }}
                </option>
              </select>

              <!-- انتخاب سال -->
              <select
                v-model="selectedYear"
                @change="onMonthYearChange"
                class="px-2 py-1 text-sm font-bold text-gray-700 bg-gray-50 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer w-20"
              >
                <option v-for="year in yearOptions" :key="year" :value="year">
                  {{ year }}
                </option>
              </select>
            </div>

            <button
              @click="nextMonth"
              class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
          </div>

          <!-- روزهای هفته -->
          <div class="grid grid-cols-7 gap-1 mb-2">
            <div
              v-for="day in weekDays"
              :key="day"
              class="text-center text-xs font-bold text-gray-500 py-1"
            >
              {{ day }}
            </div>
          </div>

          <!-- روزهای ماه -->
          <div class="grid grid-cols-7 gap-1">
            <div
              v-for="(day, index) in calendarDays"
              :key="index"
              @click="selectDay(day)"
              :class="[
                'text-center text-sm py-2 rounded-lg cursor-pointer transition-all',
                day === null ? 'invisible' : '',
                isSelectedDay(day) ? 'bg-teal-600 text-white font-bold shadow-md' : '',
                isToday(day) && !isSelectedDay(day) ? 'bg-teal-50 text-teal-700 font-bold border border-teal-200' : '',
                day !== null && !isSelectedDay(day) && !isToday(day) ? 'hover:bg-gray-100 text-gray-700' : '',
                isPastDay(day) && day !== null ? 'opacity-40 cursor-not-allowed' : ''
              ]"
            >
              {{ day }}
            </div>
          </div>

          <!-- Footer: دکمه امروز -->
          <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-100">
            <button
              @click="goToToday"
              class="text-xs text-teal-600 hover:text-teal-800 font-medium"
            >
              📍 امروز
            </button>
            <button
              @click="toggleDropdown"
              class="text-xs text-gray-500 hover:text-gray-700 font-medium"
            >
              بستن
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </template>

  <script setup>
  import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

  const props = defineProps({
    modelValue: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: 'انتخاب تاریخ',
    },
    minDate: {
      type: String,
      default: '',
    },
    maxDate: {
      type: String,
      default: '',
    },
    hasError: {
      type: Boolean,
      default: false,
    },
  });

  const emit = defineEmits(['update:modelValue', 'change']);

  // --- State ---
  const isOpen = ref(false);
  const datePickerRef = ref(null);

  // تاریخ انتخاب شده (شمسی)
  const selectedYear = ref(1404);
  const selectedMonth = ref(1);
  const selectedDay = ref(null);

  // نام ماه‌های فارسی
  const persianMonths = [
    'فروردین', 'اردیبهشت', 'خرداد',
    'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر',
    'دی', 'بهمن', 'اسفند',
  ];

  // نام روزهای هفته
  const weekDays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

  // --- تبدیل تاریخ میلادی به شمسی ---
  const gregorianToJalali = (gy, gm, gd) => {
    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    let jy = gy <= 1600 ? 0 : 979;
    gy -= gy <= 1600 ? 621 : 1600;
    const gy2 = gm > 2 ? gy + 1 : gy;
    let days = 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
    jy += 33 * Math.floor(days / 12053);
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
    const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
    return [jy, jm, jd];
  };

  // --- تبدیل تاریخ شمسی به میلادی ---
  const jalaliToGregorian = (jy, jm, jd) => {
    jy += 1595;
    let days = -355668 + 365 * jy + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
    const gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      days--;
      const gy2 = 100 * Math.floor(days / 36524);
      days %= 36524;
      if (days >= 365) days++;
      return [gy + gy2, days];
    }
    const gy2 = 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      const gy3 = Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
      return [gy + gy2 + gy3, days];
    }
    return [gy + gy2, days];
  };

  // --- محاسبه روز هفته برای اولین روز ماه شمسی ---
  const getFirstDayOfMonthWeekday = (jy, jm) => {
    // تبدیل به میلادی برای محاسبه روز هفته
    const [gy, days] = jalaliToGregorian(jy, jm, 1);
    const date = new Date(gy, 0, 1);
    date.setDate(date.getDate() + days);
    // در JavaScript: 0=یکشنبه, 1=دوشنبه, ..., 6=شنبه
    // ما می‌خواهیم: 0=شنبه, 1=یکشنبه, ..., 6=جمعه
    const jsDay = date.getDay(); // 0=Sun, 6=Sat
    return (jsDay + 1) % 7; // تبدیل به شنبه=0
  };

  // --- تعداد روزهای ماه شمسی ---
  const getDaysInMonth = (jy, jm) => {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    // اسفند: سال کبیسه 30 روز، وگرنه 29
    const isLeap = isJalaliLeapYear(jy);
    return isLeap ? 30 : 29;
  };

  // --- بررسی سال کبیسه شمسی ---
  const isJalaliLeapYear = (jy) => {
    const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
    const bl = breaks.length;
    let jp = breaks[0];
    let jump = 0;
    let leap = -14;
    let N = jy - jp;
    for (let i = 1; i < bl; i++) {
      const jm2 = breaks[i];
      jump = jm2 - jp;
      if (N < jump) break;
      leap = leap + Math.floor(jump / 33) * 8 + Math.floor((jump % 33) / 4);
      jp = jm2;
    }
    N -= jump;
    leap = leap + Math.floor(N / 33) * 8 + Math.floor((N % 33 + 3) / 4);
    if ((jump % 33) === 4 && jump - N === 4) leap += 1;
    const leapP = ((jy - jp) % 33 < 0 ? (jy - jp) % 33 + 33 : (jy - jp) % 33);
    if (leapP === 0) leap -= 1;
    return leap === 0;
  };

  // --- مقداردهی اولیه از modelValue ---
  const initFromModelValue = () => {
    if (props.modelValue) {
      // فرمت ورودی: YYYY-MM-DD (میلادی)
      const [y, m, d] = props.modelValue.split('-').map(Number);
      if (y && m && d) {
        const [jy, jm, jd] = gregorianToJalali(y, m, d);
        selectedYear.value = jy;
        selectedMonth.value = jm;
        selectedDay.value = jd;
      }
    } else {
      // تاریخ امروز
      const today = new Date();
      const [jy, jm, jd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
      selectedYear.value = jy;
      selectedMonth.value = jm;
      selectedDay.value = jd;
    }
  };

  // --- روزهای تقویم (با خانه‌های خالی برای روزهای قبل از شروع ماه) ---
  const calendarDays = computed(() => {
    const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value);
    const firstDayWeekday = getFirstDayOfMonthWeekday(selectedYear.value, selectedMonth.value);
    
    const days = [];
    // خانه‌های خالی قبل از شروع ماه
    for (let i = 0; i < firstDayWeekday; i++) {
      days.push(null);
    }
    // روزهای ماه
    for (let i = 1; i <= daysInMonth; i++) {
      days.push(i);
    }
    return days;
  });

  // --- گزینه‌های سال ---
  const yearOptions = computed(() => {
    const currentYear = selectedYear.value;
    const years = [];
    for (let y = currentYear - 5; y <= currentYear + 5; y++) {
      years.push(y);
    }
    return years;
  });

  // --- نمایش مقدار در input ---
  const displayValue = computed(() => {
    if (!props.modelValue) return '';
    const [y, m, d] = props.modelValue.split('-').map(Number);
    if (!y || !m || !d) return '';
    const [jy, jm, jd] = gregorianToJalali(y, m, d);
    return `${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`;
  });

  // --- بررسی روز انتخاب شده ---
  const isSelectedDay = (day) => {
    if (day === null) return false;
    if (!props.modelValue) return false;
    const [y, m, d] = props.modelValue.split('-').map(Number);
    const [jy, jm, jd] = gregorianToJalali(y, m, d);
    return jy === selectedYear.value && jm === selectedMonth.value && jd === day;
  };

  // --- بررسی امروز ---
  const isToday = (day) => {
    if (day === null) return false;
    const today = new Date();
    const [jy, jm, jd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    return jy === selectedYear.value && jm === selectedMonth.value && jd === day;
  };

  // --- بررسی روز گذشته ---
  const isPastDay = (day) => {
    if (day === null || !props.minDate) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const [jy, jm, jd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    
    if (selectedYear.value < jy) return true;
    if (selectedYear.value === jy && selectedMonth.value < jm) return true;
    if (selectedYear.value === jy && selectedMonth.value === jm && day < jd) return true;
    return false;
  };

  // --- انتخاب روز ---
  const selectDay = (day) => {
    if (day === null) return;
    if (isPastDay(day)) return;
    
    selectedDay.value = day;
    
    // تبدیل شمسی به میلادی
    const [gy, days] = jalaliToGregorian(selectedYear.value, selectedMonth.value, day);
    const date = new Date(gy, 0, 1);
    date.setDate(date.getDate() + days);
    
    const miladiDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    
    emit('update:modelValue', miladiDate);
    emit('change', miladiDate);
    
    isOpen.value = false;
  };

  // --- تغییر ماه/سال ---
  const onMonthYearChange = () => {
    // فقط تقویم را رفرش می‌کنیم
  };

  const prevMonth = () => {
    if (selectedMonth.value === 1) {
      selectedMonth.value = 12;
      selectedYear.value--;
    } else {
      selectedMonth.value--;
    }
  };

  const nextMonth = () => {
    if (selectedMonth.value === 12) {
      selectedMonth.value = 1;
      selectedYear.value++;
    } else {
      selectedMonth.value++;
    }
  };

  const goToToday = () => {
    const today = new Date();
    const [jy, jm, jd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    selectedYear.value = jy;
    selectedMonth.value = jm;
    selectedDay.value = jd;
    
    const miladiDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    emit('update:modelValue', miladiDate);
    emit('change', miladiDate);
    isOpen.value = false;
  };

  // --- باز/بسته کردن dropdown ---
  const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
  };

  const clearDate = () => {
    emit('update:modelValue', '');
    emit('change', '');
    selectedDay.value = null;
  };

  // --- بستن با کلیک بیرون ---
  const handleClickOutside = (event) => {
    if (datePickerRef.value && !datePickerRef.value.contains(event.target)) {
      isOpen.value = false;
    }
  };

  onMounted(() => {
    initFromModelValue();
    document.addEventListener('click', handleClickOutside);
  });

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
  });

  // Watch برای تغییرات خارجی
  watch(() => props.modelValue, (newVal) => {
    if (newVal) {
      const [y, m, d] = newVal.split('-').map(Number);
      if (y && m && d) {
        const [jy, jm, jd] = gregorianToJalali(y, m, d);
        selectedYear.value = jy;
        selectedMonth.value = jm;
        selectedDay.value = jd;
      }
    }
  });
  </script>

  <style scoped>
  .dropdown-enter-active,
  .dropdown-leave-active {
    transition: all 0.2s ease;
  }

  .dropdown-enter-from,
  .dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }

  /* استایل select */
  select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: left 0.25rem center;
    background-size: 1rem;
    padding-left: 1.5rem;
  }
  </style>
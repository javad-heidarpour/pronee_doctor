<template>
  <PortalLayout :doctor="doctor" :stats="stats">
    <div dir="rtl" class="space-y-5">
      <!-- آمار -->
      <AppointmentStats :stats="stats" />

      <!-- فیلترها -->
      <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="flex items-center gap-2 font-bold text-gray-800">
            <span class="text-teal-600">⌕</span>
            جستجو و فیلتر نوبت‌ها
          </h2>
        </div>

        <form
          class="grid grid-cols-1 gap-4 sm:grid-cols-3 xl:grid-cols-5"
          @submit.prevent="applyFilters"
        >
          <!-- جستجو -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700"> جستجو </label>
            <input
              v-model="filters.search"
              type="text"
              placeholder="کد ملی، نام، شماره تلفن و ..."
              class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"
            />
          </div>

          <!-- مطب -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700">مطب</label>
            <SelectInput
              v-model="filters.clinic_id"
              :options="clinicOptions"
              placeholder="همه مطب‌ها"
              :clearable="true"
              searchable
              @keydown.enter.prevent
            />
          </div>

          <!-- وضعیت پرداخت -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700">
              وضعیت پرداخت
            </label>
            <SelectInput
              v-model="filters.payment_status"
              :options="paymentStatusOptions"
              placeholder="همه وضعیت‌های پرداخت"
              :clearable="true"
              searchable
              @keydown.enter.prevent
            />
          </div>

          <!-- وضعیت نوبت -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700"> وضعیت نوبت </label>
            <SelectInput
              v-model="filters.status"
              :options="statusOptions"
              placeholder="همه وضعیت‌ها"
              :clearable="true"
              searchable
              @keydown.enter.prevent
            />
          </div>

          <!-- تاریخ از -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700"> از تاریخ </label>
            <JalaliDatePicker
              v-model="filters.date_from"
              placeholder="انتخاب تاریخ"
              :has-error="!!$page.props.errors.date_from"
            />
            <p v-if="$page.props.errors.date_from" class="mt-1 text-xs text-red-500">
              {{ $page.props.errors.date_from }}
            </p>
          </div>

          <!-- تاریخ تا -->
          <div>
            <label class="mb-2 block text-sm font-bold text-gray-700"> تا تاریخ </label>
            <JalaliDatePicker
              v-model="filters.date_to"
              placeholder="انتخاب تاریخ"
              :has-error="!!$page.props.errors.date_to"
            />
            <p v-if="$page.props.errors.date_to" class="mt-1 text-xs text-red-500">
              {{ $page.props.errors.date_to }}
            </p>
          </div>
        </form>

        <!-- دکمه‌های فیلتر -->
        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
          <button
            type="button"
            @click="applyFilters"
            class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-teal-500 to-cyan-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:shadow-md"
          >
            <span>🔍</span>
            اعمال فیلتر
          </button>

          <a
            :href="route('portal.patients.export', exportFilterParams)"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100"
          >
            <span aria-hidden="true">↓</span>
            خروجی Excel / CSV
          </a>

          <button
            type="button"
            @click="resetFilters"
            class="rounded-xl bg-gray-100 px-5 py-3 text-sm font-bold text-gray-700 transition hover:bg-gray-200"
          >
            پاک کردن فیلتر
          </button>

          <button
            type="button"
            @click="openAppointmentBooking"
            class="rounded-xl bg-gradient-to-l from-rose-800 to-red-500 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:shadow-md sm:mr-auto"
          >
            + ثبت نوبت جدید
          </button>
        </div>
      </section>

      <!-- عنوان لیست -->
      <div class="flex items-center justify-between">
        <h2 class="font-bold text-gray-800">فهرست نوبت‌ها</h2>

        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">
          {{ appointments.total }} نوبت
        </span>
      </div>

      <!-- ================================================= -->
      <!-- دسکتاپ -->
      <!-- ================================================= -->
      <section
        class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm md:block"
      >
        <div class="w-full overflow-x-auto">
          <table class="w-full min-w-[1100px] text-right">
            <thead class="border-b border-gray-100 bg-gray-50">
              <tr>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  #
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  بیمار
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  تاریخ نوبت
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  ساعت
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  مطب
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  دلیل مراجعه
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  پرداخت
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  وضعیت
                </th>
                <th class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600">
                  پیامک
                </th>
                <th
                  v-if="userRole === 'doctor'"
                  class="whitespace-nowrap px-4 py-4 text-xs font-bold text-gray-600"
                >
                  ویزیت
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="appointment in appointments.data"
                :key="appointment.id"
                class="transition-colors hover:bg-gray-50"
              >
                <!-- شناسه -->
                <td class="px-4 py-4 text-sm text-gray-500">
                  {{ appointment.id }}
                </td>

                <!-- بیمار -->
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 text-sm font-bold text-white"
                    >
                      {{ getInitials(appointment.patient_name) }}
                    </div>

                    <div class="min-w-0">
                      <Link
                        :href="route('portal.patients.show', appointment.id)"
                        class="whitespace-nowrap text-sm font-bold text-gray-900 transition hover:text-teal-700 hover:underline"
                      >
                        {{ appointment.patient_name }}
                      </Link>
                      <p class="mt-1 text-xs text-gray-500" dir="ltr">
                        {{ appointment.patient_phone }}
                      </p>
                    </div>
                  </div>
                </td>

                <!-- تاریخ -->
                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                  {{ toPersianDate(appointment.appointment_date) }}
                </td>

                <!-- ساعت -->
                <td class="whitespace-nowrap px-4 py-4 text-sm font-bold text-gray-700">
                  {{ appointment.appointment_time?.substr(0, 5) || "-" }}
                </td>

                <!-- مطب -->
                <td class="px-4 py-4 text-sm text-gray-700">
                  {{ appointment.clinic?.name || "-" }}
                </td>

                <!-- دلیل مراجعه -->
                <td class="px-4 py-4">
                  <span
                    v-if="appointment.visit_reason?.title"
                    class="inline-flex max-w-[180px] items-center rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium leading-5 text-rose-800"
                    :title="appointment.visit_reason.title"
                  >
                    {{ appointment.visit_reason.title }}
                  </span>
                  <span v-else class="text-xs text-gray-400"> ثبت نشده </span>
                </td>

                <!-- پرداخت -->
                <td class="px-4 py-3">
                  <span
                    class="inline-flex cursor-help items-center justify-center"
                    :title="getPaymentStatusLabel(appointment)"
                  >
                    <svg
                      v-if="appointment.payment_status === 'paid'"
                      class="h-5 w-5 text-green-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                    </svg>

                    <svg
                      v-else-if="appointment.payment_status === 'pending'"
                      class="h-5 w-5 text-yellow-500"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <circle cx="12" cy="12" r="9" stroke-width="2" />
                      <path stroke-linecap="round" stroke-width="2" d="M12 7v5l3 2" />
                    </svg>

                    <svg
                      v-else-if="
                        ['cancelled', 'failed'].includes(appointment.payment_status)
                      "
                      class="h-5 w-5 text-red-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <circle cx="12" cy="12" r="9" stroke-width="2" />
                      <path
                        stroke-linecap="round"
                        stroke-width="2"
                        d="M15 9l-6 6m0-6l6 6"
                      />
                    </svg>

                    <svg
                      v-else
                      class="h-5 w-5 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <circle cx="12" cy="12" r="9" stroke-width="2" />
                      <path stroke-linecap="round" stroke-width="2" d="M12 8v4m0 4h.01" />
                    </svg>
                  </span>
                </td>

                <!-- وضعیت نوبت -->
                <td class="min-w-[190px] px-4 py-4">
                  <select
                    :value="appointment.status"
                    :disabled="isStatusLocked(appointment)"
                    @change="updateStatus(appointment, $event)"
                    :class="[
                      getStatusClasses(appointment.status),
                      isStatusLocked(appointment) ? 'cursor-not-allowed opacity-70' : '',
                    ]"
                    class="max-w-[135px] rounded-lg border-0 px-3 py-2 text-xs font-bold outline-none focus:ring-2 focus:ring-teal-300 disabled:cursor-not-allowed"
                  >
                    <option value="pending">در انتظار</option>
                    <option value="cancelled">لغو شده</option>
                    <option value="visited">ویزیت شده</option>
                  </select>

                  <!-- در حال ذخیره -->
                  <p
                    v-if="statusStates[appointment.id]?.loading"
                    class="mt-2 text-xs text-teal-600"
                  >
                    <span class="inline-block animate-spin">◌</span>
                    در حال ذخیره...
                  </p>

                  <!-- خطا -->
                  <div
                    v-if="statusStates[appointment.id]?.error"
                    class="mt-2 max-w-[210px] rounded-lg border border-red-100 bg-red-50 p-2"
                    role="alert"
                  >
                    <p class="text-xs leading-5 text-red-700">
                      ⚠️ {{ statusStates[appointment.id].error }}
                    </p>
                  </div>

                  <!-- موفقیت -->
                  <p
                    v-if="statusStates[appointment.id]?.success"
                    class="mt-2 text-xs text-green-700"
                  >
                    ✓ وضعیت با موفقیت تغییر کرد.
                  </p>
                </td>

                <!-- پیامک -->
                <td class="px-4 py-4">
                  <div
                    v-if="appointment.payment_status !== 'paid'"
                    class="flex flex-col items-center gap-2"
                  >
                    <button
                      type="button"
                      @click="sendAppointmentSms(appointment)"
                      :disabled="
                        smsStates[appointment.id]?.sending ||
                        smsStates[appointment.id]?.sent
                      "
                      :title="
                        smsStates[appointment.id]?.sent
                          ? 'پیامک ارسال شده'
                          : 'ارسال پیامک نوبت'
                      "
                      class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-sm transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60"
                      :class="
                        smsStates[appointment.id]?.sent
                          ? 'bg-green-600'
                          : 'bg-gradient-to-l from-teal-500 to-cyan-600'
                      "
                    >
                      <svg
                        v-if="smsStates[appointment.id]?.sending"
                        class="h-5 w-5 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          class="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          stroke-width="4"
                        />
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                      </svg>

                      <svg
                        v-else-if="smsStates[appointment.id]?.sent"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M5 13l4 4L19 7"
                        />
                      </svg>

                      <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                        />
                      </svg>
                    </button>

                    <p
                      v-if="smsStates[appointment.id]?.message"
                      class="max-w-[130px] text-center text-[10px] leading-5"
                      :class="
                        smsStates[appointment.id]?.success
                          ? 'text-green-700'
                          : 'text-red-600'
                      "
                      role="status"
                      aria-live="polite"
                    >
                      {{ smsStates[appointment.id].message }}
                    </p>
                  </div>
                </td>

                <!-- اطلاعات ویزیت -->
                <td class="px-4 py-4">
                  <div
                    v-if="!isCancelled(appointment) && userRole === 'doctor'"
                    class="flex items-center gap-2"
                  >
                    <div
                      v-if="appointment.description"
                      class="max-w-[130px] truncate text-xs text-gray-600"
                      :title="appointment.description"
                    >
                      {{ appointment.description }}
                    </div>

                    <button
                      type="button"
                      @click="openDescriptionModal(appointment)"
                      :class="getDescriptionButtonClass(appointment)"
                      :title="
                        hasExtraInfo(appointment) ? 'ویرایش اطلاعات' : 'افزودن اطلاعات'
                      "
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition hover:scale-105"
                    >
                      <!-- آیکن ویرایش -->
                      <svg
                        v-if="hasExtraInfo(appointment)"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                        />
                      </svg>

                      <!-- آیکن افزودن -->
                      <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M12 5v14m-7-7h14"
                        />
                      </svg>
                    </button>
                  </div>
                </td>

                <!-- عملیات -->
              </tr>

              <!-- جدول خالی -->
              <tr v-if="appointments.data.length === 0">
                <td colspan="11" class="px-6 py-16 text-center text-gray-500">
                  <div class="text-4xl">📅</div>
                  <p class="mt-3 font-medium">هیچ نوبتی یافت نشد</p>
                  <p class="mt-1 text-xs text-gray-400">فیلترهای جستجو را تغییر دهید.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ================================================= -->
      <!-- موبایل -->
      <!-- ================================================= -->
      <section class="space-y-4 md:hidden">
        <article
          v-for="(appointment, index) in appointments.data"
          :key="appointment.id"
          class="relative overflow-hidden rounded-2xl border shadow-sm transition-all duration-200"
          :class="
            index % 2 === 0
              ? 'border-teal-200 bg-teal-50/40'
              : 'border-indigo-200 bg-indigo-50/40'
          "
        >
          <!-- نوار رنگی کناری -->
          <div
            class="absolute right-0 top-0 h-full w-1.5"
            :class="index % 2 === 0 ? 'bg-teal-500' : 'bg-indigo-500'"
          ></div>
          <!-- سربرگ -->
          <div
            class="flex items-center justify-between border-b px-4 py-3"
            :class="
              index % 2 === 0
                ? 'border-teal-100 bg-teal-100/50'
                : 'border-indigo-100 bg-indigo-100/50'
            "
          >
            <div class="flex min-w-0 items-center gap-3">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 text-sm font-bold text-white"
              >
                {{ getInitials(appointment.patient_name) }}
              </div>

              <div class="min-w-0">
                <Link
                  :href="route('portal.patients.show', appointment.id)"
                  class="block truncate text-sm font-bold text-gray-900 transition hover:text-teal-700 hover:underline"
                >
                  {{ appointment.patient_name }}
                </Link>
                <p class="mt-1 text-xs text-gray-500" dir="ltr">
                  {{ appointment.patient_phone }}
                </p>
              </div>
            </div>

            <div class="text-left">
              <span class="mb-1 block text-[10px] text-gray-500"> شماره نوبت </span>

              <span
                class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-extrabold"
                :class="
                  index % 2 === 0 ? 'bg-teal-600 text-white' : 'bg-indigo-600 text-white'
                "
              >
                #{{ appointment.id }}
              </span>
            </div>
          </div>

          <div class="space-y-4 p-4">
            <!-- تاریخ و ساعت -->
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl bg-blue-50 p-3">
                <div class="mb-1 flex items-center gap-1.5 text-xs text-blue-600">
                  <span>📅</span>
                  تاریخ نوبت
                </div>
                <p class="text-sm font-bold text-blue-900">
                  {{ toPersianDate(appointment.appointment_date) }}
                </p>
              </div>

              <div class="rounded-xl bg-purple-50 p-3">
                <div class="mb-1 flex items-center gap-1.5 text-xs text-purple-600">
                  <span>🕒</span>
                  ساعت نوبت
                </div>
                <p class="text-sm font-bold text-purple-900">
                  {{ appointment.appointment_time?.substr(0, 5) || "-" }}
                </p>
              </div>
            </div>

            <!-- مطب و دلیل مراجعه -->
            <div class="space-y-3">
              <div class="flex items-start gap-2">
                <span class="mt-0.5 text-gray-400">🏥</span>
                <div class="min-w-0">
                  <p class="text-xs text-gray-400">مطب</p>
                  <p class="mt-1 text-sm font-medium text-gray-800">
                    {{ appointment.clinic?.name || "-" }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-2">
                <span class="mt-0.5 text-gray-400">📋</span>
                <div class="min-w-0 flex-1">
                  <p class="text-xs text-gray-400">دلیل مراجعه</p>

                  <p
                    v-if="appointment.visit_reason?.title"
                    class="mt-1 inline-flex rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-800"
                  >
                    {{ appointment.visit_reason.title }}
                  </p>

                  <p v-else class="mt-1 text-xs text-gray-400">ثبت نشده</p>
                </div>
              </div>
            </div>

            <!-- وضعیت پرداخت -->
            <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
              <span class="text-xs text-gray-500">وضعیت پرداخت</span>

              <span
                class="rounded-full px-3 py-1 text-xs font-bold"
                :class="getPaymentStatusClass(appointment)"
              >
                {{ getPaymentStatusLabel(appointment) }}
              </span>
            </div>

            <!-- اطلاعات تکمیلی -->
            <div
              v-if="!isCancelled(appointment)"
              class="rounded-xl border border-gray-100 bg-gray-50 p-3"
            >
              <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-600"> اطلاعات تکمیلی </span>

                <button
                  type="button"
                  @click="openDescriptionModal(appointment)"
                  :class="getDescriptionButtonClass(appointment)"
                  :title="hasExtraInfo(appointment) ? 'ویرایش اطلاعات' : 'افزودن اطلاعات'"
                  class="flex h-9 w-9 items-center justify-center rounded-lg transition"
                >
                  <!-- آیکن ویرایش -->
                  <svg
                    v-if="hasExtraInfo(appointment)"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                    />
                  </svg>

                  <!-- آیکن افزودن -->
                  <svg
                    v-else
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2.5"
                      d="M12 5v14m-7-7h14"
                    />
                  </svg>
                </button>
              </div>

              <p v-if="appointment.description" class="text-xs leading-6 text-gray-600">
                {{ appointment.description }}
              </p>

              <div
                v-if="appointment.surgery_type || appointment.surgery_date"
                class="mt-2 space-y-1 border-t border-gray-200 pt-2 text-xs text-gray-600"
              >
                <p v-if="appointment.surgery_type">
                  نوع جراحی:
                  <span class="font-medium text-gray-800">
                    {{ appointment.surgery_type?.title || appointment.surgery_type }}
                  </span>
                </p>

                <p v-if="appointment.surgery_date">
                  تاریخ جراحی:
                  <span class="font-medium text-gray-800">
                    {{ toPersianDate(appointment.surgery_date) }}
                  </span>
                </p>
              </div>

              <p v-if="!hasExtraInfo(appointment)" class="text-xs text-gray-400">
                اطلاعات تکمیلی ثبت نشده است.
              </p>
            </div>

            <!-- وضعیت نوبت -->
            <div class="border-t border-gray-100 pt-3">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <p class="mb-2 text-xs text-gray-400">وضعیت نوبت</p>

                  <select
                    :value="appointment.status"
                    :disabled="isStatusLocked(appointment)"
                    @change="updateStatus(appointment, $event)"
                    :class="[
                      getStatusClasses(appointment.status),
                      isStatusLocked(appointment) ? 'cursor-not-allowed opacity-70' : '',
                    ]"
                    class="w-full max-w-[170px] rounded-xl border-0 px-3 py-2.5 text-xs font-bold outline-none focus:ring-2 focus:ring-teal-300 disabled:cursor-not-allowed"
                  >
                    <option value="pending">در انتظار</option>

                    <option value="cancelled">لغو شده</option>
                    <option v-if="userRole === 'doctor'" value="visited">ویزیت شده</option>
                  </select>
                </div>

                <!-- <Link
                  :href="route('portal.patients.show', appointment.id)"
                  class="flex shrink-0 items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-teal-700"
                >
                  مشاهده جزئیات
                  <span>←</span>
                </Link> -->
              </div>

              <!-- در حال ذخیره -->
              <p
                v-if="statusStates[appointment.id]?.loading"
                class="mt-3 text-xs text-teal-600"
              >
                <span class="inline-block animate-spin">◌</span>
                در حال ذخیره تغییرات...
              </p>

              <!-- خطای تغییر وضعیت -->
              <div
                v-if="statusStates[appointment.id]?.error"
                class="mt-3 rounded-xl border border-red-100 bg-red-50 p-3"
                role="alert"
              >
                <div class="flex items-start gap-2">
                  <span class="text-red-600">⚠️</span>
                  <p class="text-xs leading-6 text-red-700">
                    {{ statusStates[appointment.id].error }}
                  </p>
                </div>
              </div>

              <!-- موفقیت -->
              <p
                v-if="statusStates[appointment.id]?.success"
                class="mt-3 text-xs font-medium text-green-700"
              >
                ✓ وضعیت نوبت با موفقیت تغییر کرد.
              </p>
            </div>

            <!-- ارسال پیامک -->
            <div
              v-if="appointment.payment_status !== 'paid'"
              class="border-t border-gray-100 pt-3"
            >
              <button
                type="button"
                @click="sendAppointmentSms(appointment)"
                :disabled="
                  smsStates[appointment.id]?.sending || smsStates[appointment.id]?.sent
                "
                :title="
                  smsStates[appointment.id]?.sent ? 'پیامک ارسال شده' : 'ارسال پیامک نوبت'
                "
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl text-white shadow-sm transition hover:scale-105 disabled:cursor-not-allowed disabled:opacity-60"
                :class="
                  smsStates[appointment.id]?.sent
                    ? 'bg-green-600'
                    : 'bg-gradient-to-l from-teal-500 to-cyan-600'
                "
              >
                <!-- در حال ارسال -->
                <svg
                  v-if="smsStates[appointment.id]?.sending"
                  class="h-6 w-6 animate-spin"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  />
                </svg>

                <!-- ارسال موفق -->
                <svg
                  v-else-if="smsStates[appointment.id]?.sent"
                  class="h-6 w-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M5 13l4 4L19 7"
                  />
                </svg>

                <!-- آیکن پیامک -->
                <svg
                  v-else
                  class="h-6 w-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                  />
                </svg>
              </button>

              <p
                v-if="smsStates[appointment.id]?.message"
                class="mt-2 text-center text-xs leading-5"
                :class="
                  smsStates[appointment.id]?.success ? 'text-green-700' : 'text-red-600'
                "
                role="status"
                aria-live="polite"
              >
                {{ smsStates[appointment.id].message }}
              </p>

              <p
                v-if="smsStates[appointment.id]?.message"
                class="mt-2 text-center text-xs leading-5"
                :class="
                  smsStates[appointment.id]?.success ? 'text-green-700' : 'text-red-600'
                "
                role="status"
                aria-live="polite"
              >
                {{ smsStates[appointment.id].message }}
              </p>
            </div>
          </div>
        </article>

        <!-- لیست خالی -->
        <div
          v-if="appointments.data.length === 0"
          class="rounded-2xl border border-gray-100 bg-white px-4 py-16 text-center shadow-sm"
        >
          <div class="text-5xl">📅</div>
          <p class="mt-4 font-bold text-gray-700">هیچ نوبتی یافت نشد</p>
          <p class="mt-2 text-xs text-gray-400">فیلترهای جستجو را تغییر دهید.</p>
        </div>
      </section>

      <!-- ================================================= -->
      <!-- صفحه‌بندی -->
      <!-- ================================================= -->
      <div
        v-if="appointments.last_page > 1"
        class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-center text-xs text-gray-600 sm:text-right">
            نمایش
            <strong>{{ appointments.from }}</strong>
            تا
            <strong>{{ appointments.to }}</strong>
            از
            <strong>{{ appointments.total }}</strong>
            نوبت
          </p>

          <div class="flex flex-wrap items-center justify-center gap-1.5">
            <button
              v-for="link in appointments.links"
              :key="link.label"
              type="button"
              @click="changePage(link.url)"
              :disabled="!link.url || link.active"
              :class="[
                'min-w-9 rounded-lg px-3 py-2 text-xs font-medium transition',
                link.active
                  ? 'bg-teal-600 text-white'
                  : link.url
                  ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  : 'cursor-not-allowed bg-gray-50 text-gray-300',
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- مودال اطلاعات تکمیلی -->
      <AppointmentDescriptionModal
        v-model="showDescriptionModal"
        :appointment="selectedAppointment"
        :surgery-types="surgeryTypes"
        @saved="handleDescriptionSaved"
      />

      <!-- مودال ثبت نوبت -->
      <RegisterNewAppointment
        v-model="showAppointmentBooking"
        :clinics="clinics"
        :available-slots="availableSlots"
        :visit-reasons="$page.props.visitReasons || []"
        :loading-slots="loadingSlots"
        @load-slots="loadAvailableSlots"
        @success="onAppointmentCreated"
      />
    </div>
  </PortalLayout>
</template>
<script setup>
import { ref } from "vue";
import { router, Link } from "@inertiajs/vue3";
import axios from "axios";

import PortalLayout from "@/Layouts/PortalLayout.vue";
import JalaliDatePicker from "@/Components/Portal/JalaliDatePicker.vue";
import SelectInput from "@/Components/Portal/SelectInput.vue";
import { toPersianDate } from "@/utils/dateUtils";

import RegisterNewAppointment from "@/Components/Portal/patients/Register-new-appointment.vue";
import AppointmentDescriptionModal from "@/Components/Portal/patients/AppointmentDescriptionModal.vue";
import AppointmentStats from "@/Components/Portal/patients/AppointmentStats.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
const page = usePage();

const userRole = computed(() => page.props.auth?.user?.role);
// ==================================================
// Props
// ==================================================

const props = defineProps({
  doctor: {
    type: Object,
    required: true,
  },
  appointments: {
    type: Object,
    required: true,
  },

  stats: {
    type: Object,
    required: true,
  },

  filters: {
    type: Object,
    default: () => ({}),
  },

  surgeryTypes: {
    type: Array,
    default: () => [],
  },
  clinics: {
    type: Array,
    default: () => [],
  },
});
const isCancelled = (appointment) => {
  return ["cancelled", "canceled"].includes(appointment.status?.toLowerCase());
};
// ==================================================
// فیلترها
// ==================================================

const paymentStatusOptions = [
  { value: "paid", label: "پرداخت شده" },
  { value: "pending", label: "در انتظار پرداخت" },
  { value: "failed", label: "پرداخت ناموفق" },
  { value: "cancelled", label: "پرداخت لغو شده" },
  { value: "refunded", label: "مبلغ مسترد شده" },
];

const statusOptions = [
  { value: "pending", label: "در انتظار" },
  { value: "cancelled", label: "لغو شده" },
  { value: "visited", label: "ویزیت شده" },
];

const filters = ref({
  search: props.filters?.search || "",
  status: props.filters?.status || "",
  payment_status: props.filters?.payment_status || "",
  clinic_id: props.filters?.clinic_id ? String(props.filters.clinic_id) : "",
  date_from: props.filters?.date_from || "",
  date_to: props.filters?.date_to || "",
});

const clinicOptions = computed(() => props.clinics.map((clinic) => ({
  value: String(clinic.id),
  label: clinic.name,
})));

const exportFilterParams = computed(() => Object.fromEntries(
  Object.entries(filters.value).filter(([, value]) =>
    value !== "" && value !== null && value !== undefined
  )
));

const applyFilters = () => {
  const payload = exportFilterParams.value;

  router.get(route("portal.patients.index"), payload, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  filters.value = {
    search: "",
    status: "",
    payment_status: "",
    clinic_id: "",
    date_from: "",
    date_to: "",
  };

  applyFilters();
};

// ==================================================
// ثبت نوبت جدید
// ==================================================

const showAppointmentBooking = ref(false);
const availableSlots = ref([]);
const loadingSlots = ref(false);
const slotsError = ref("");

const openAppointmentBooking = () => {
  slotsError.value = "";
  availableSlots.value = [];
  showAppointmentBooking.value = true;
};

const loadAvailableSlots = async ({ clinic_id }) => {
  availableSlots.value = [];
  slotsError.value = "";

  if (!clinic_id) {
    loadingSlots.value = false;
    return;
  }

  loadingSlots.value = true;

  try {
    const response = await axios.get(route("portal.patients.available-slots"), {
      params: { clinic_id },
      headers: {
        Accept: "application/json",
      },
    });

    availableSlots.value = Array.isArray(response.data?.slots) ? response.data.slots : [];
  } catch (error) {
    slotsError.value =
      error.response?.data?.message || "دریافت ساعت‌های آزاد با خطا مواجه شد.";

    console.error("Load available slots error:", error);
  } finally {
    loadingSlots.value = false;
  }
};

const onAppointmentCreated = () => {
  showAppointmentBooking.value = false;

  router.reload({
    only: ["appointments", "stats"],
    preserveScroll: true,
  });
};

// ==================================================
// ارسال پیامک نوبت
// ==================================================

const smsStates = ref({});

const sendAppointmentSms = async (appointment) => {
  const appointmentId = appointment?.id;

  if (!appointmentId) return;

  const currentState = smsStates.value[appointmentId];

  if (currentState?.sending || currentState?.sent) return;

  smsStates.value[appointmentId] = {
    sending: true,
    sent: false,
    success: false,
    message: "",
  };

  try {
    const response = await axios.get(
      route("sms.visit", {
        visit_number: appointment.visit_number ?? appointmentId,
      }),
      {
        headers: {
          Accept: "application/json",
        },
      }
    );

    if (response.data?.success !== true) {
      throw new Error(response.data?.message || "ارسال پیامک انجام نشد.");
    }

    smsStates.value[appointmentId] = {
      sending: false,
      sent: true,
      success: true,
      message: response.data.message || "پیامک با موفقیت ارسال شد.",
    };
  } catch (error) {
    smsStates.value[appointmentId] = {
      sending: false,
      sent: false,
      success: false,
      message:
        error.response?.data?.message ||
        error.message ||
        "ارسال پیامک انجام نشد. لطفاً مجدداً تلاش کنید.",
    };
  }
};

// ==================================================
// مدیریت وضعیت نوبت
// ==================================================

const statusStates = ref({});

// وضعیت‌هایی که اجازه تغییر ندارند
const isStatusLocked = (appointment) => {
  return appointment.status === "visited" || statusStates.value[appointment.id]?.loading;
};

// تغییر وضعیت
const updateStatus = (appointment, event) => {
  const id = appointment.id;
  const newStatus = event.target.value;
  const currentStatus = appointment.status;
  
  if (newStatus === "visited" && userRole.value != "doctor") {
    event.target.value = currentStatus;

    statusStates.value[id] = {
      loading: false,
      error: "فقط پزشک مجاز به ویزیت است",
      success: false,
    };

    return;
  }
  // جلوگیری از تغییر نوبت ویزیت‌شده
  if (currentStatus === "visited") {
    event.target.value = currentStatus;

    statusStates.value[id] = {
      loading: false,
      error: "این نوبت قبلاً ویزیت شده است و امکان تغییر مجدد وضعیت آن وجود ندارد.",
      success: false,
    };

    return;
  }

  // جلوگیری از درخواست تکراری
  if (statusStates.value[id]?.loading) {
    event.target.value = currentStatus;
    return;
  }

  // اگر وضعیت تغییر نکرده باشد
  if (newStatus === currentStatus) {
    return;
  }

  // پاک کردن پیام قبلی و شروع درخواست
  statusStates.value[id] = {
    loading: true,
    error: "",
    success: false,
  };

  router.put(
    route("portal.appointments.status", id),
    {
      status: newStatus,
    },
    {
      preserveScroll: true,
      preserveState: true,

      onSuccess: (page) => {
        const flashError = page.props.flash?.error;

        if (flashError) {
          statusStates.value[id] = {
            loading: false,
            error: flashError,
            success: false,
          };

          event.target.value = currentStatus;
          return;
        }

        statusStates.value[id] = {
          loading: false,
          error: "",
          success: true,
        };
      },

      onError: (errors) => {
        const errorMessage =
          errors.status ||
          Object.values(errors)[0] ||
          "تغییر وضعیت نوبت انجام نشد. لطفاً مجدداً تلاش کنید.";

        statusStates.value[id] = {
          loading: false,
          error: errorMessage,
          success: false,
        };

        // بازگرداندن مقدار قبلی Select
        event.target.value = currentStatus;
      },

      onFinish: () => {
        if (statusStates.value[id]) {
          statusStates.value[id].loading = false;
        }
      },
    }
  );
};

// ==================================================
// وضعیت پرداخت
// ==================================================

const paymentStatusLabels = {
  pending: "در انتظار پرداخت",
  paid: "پرداخت شده",
  cancelled: "پرداخت لغو شده",
  failed: "پرداخت ناموفق",
  refunded: "مبلغ مسترد شده",
};

const getPaymentStatusLabel = (appointment) => {
  return paymentStatusLabels[appointment.payment_status] || "نامشخص";
};

const getPaymentStatusClass = (appointment) => {
  const classes = {
    paid: "bg-green-100 text-green-700",
    pending: "bg-yellow-100 text-yellow-700",
    cancelled: "bg-red-100 text-red-700",
    failed: "bg-red-100 text-red-700",
    refunded: "bg-blue-100 text-blue-700",
  };

  return classes[appointment.payment_status] || "bg-gray-100 text-gray-600";
};

// ==================================================
// کلاس وضعیت نوبت
// ==================================================

const getStatusClasses = (status) => {
  const classes = {
    pending: "bg-amber-100 text-amber-700",
    confirmed: "bg-green-100 text-green-700",
    paid: "bg-green-100 text-green-700",
    cancelled: "bg-red-100 text-red-700",
    visited: "bg-purple-100 text-purple-700",
  };

  return classes[status] || "bg-gray-100 text-gray-700";
};

// ==================================================
// اطلاعات تکمیلی نوبت
// ==================================================

const hasExtraInfo = (appointment) => {
  return Boolean(
    appointment.description || appointment.surgery_type || appointment.surgery_date
  );
};

const getDescriptionButtonClass = (appointment) => {
  return hasExtraInfo(appointment)
    ? "bg-teal-50 text-teal-700 hover:bg-teal-100"
    : "bg-gray-100 text-gray-600 hover:bg-gray-200";
};

// ==================================================
// صفحه‌بندی
// ==================================================

const changePage = (url) => {
  if (!url) return;

  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  });
};

// ==================================================
// اطلاعات بیمار
// ==================================================

const getInitials = (name) => {
  if (!name) return "?";

  return name
    .split(" ")
    .map((part) => part[0])
    .join("")
    .substring(0, 2);
};

// ==================================================
// مودال اطلاعات تکمیلی
// ==================================================

const showDescriptionModal = ref(false);
const selectedAppointment = ref(null);

const openDescriptionModal = (appointment) => {
  selectedAppointment.value = appointment;
  showDescriptionModal.value = true;
};

const handleDescriptionSaved = () => {
  showDescriptionModal.value = false;

  router.reload({
    only: ["appointments", "stats"],
    preserveScroll: true,
  });
};
</script>

<template>
  <div
    id="location"
    class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 hover:shadow-lg transition-shadow duration-300"
  >
    <div class="flex items-center gap-3 mb-6">
      <div class="p-2 bg-blue-50 rounded-xl">
        <svg
          class="w-6 h-6 text-blue-600"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
          />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900">موقعیت مطب و آدرس</h2>
    </div>

    <div class="space-y-8">
      <template v-for="(addr, index) in doctor.address" :key="addr.id || index">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- آدرس و اطلاعات -->
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <svg
                class="w-5 h-5 text-red-500 mt-1 flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <div class="flex flex-col">
                <span class="text-gray-800 font-medium text-base">
                  {{ addr.city?.name }} - {{ addr.name }}
                </span>
                <span class="text-gray-600 text-sm mt-1">
                  {{ addr.full_address }}
                </span>
                <span
                  v-if="addr.phone"
                  class="text-sm text-gray-500 mt-2 dir-ltr text-left"
                >
                  <a :href="`tel:${addr.phone.replace(/[\s\-\(\)]/g, '')}`">
                    📞 {{ addr.phone }}
                  </a>
                </span>
              </div>
            </div>

            <Link
              :href="route('booking', { slug: doctor.slug, clinic: addr.id })"
              class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-800"
            >
              دریافت نوبت از این شعبه
              <span aria-hidden="true">←</span>
            </Link>

            <!-- دکمه‌های لینک -->
            <div v-if="addr.Latitude && addr.Longitude" class="flex gap-2 mt-4">
              <a
                :href="getGoogleMapsLink(addr.Latitude, addr.Longitude)"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
              >
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
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
                مسیریابی گوگل
              </a>
              <a
                :href="getOSMLink(addr.Latitude, addr.Longitude)"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
              >
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
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
                نمایش در OSM
              </a>
              <a
                :href="getNeshanUrl(addr.Latitude, addr.Longitude)"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors"
              >
                <!-- <a
                  :href="getNeshanUrl(addr.Latitude, addr.Longitude)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-blue-600 hover:underline"
                > -->
                <!-- </a> -->
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
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
                نمایش در نشان
              </a>
            </div>
          </div>

          <!-- نقشه -->
          <div
            class="relative h-64 w-full bg-gray-100 rounded-2xl overflow-hidden border border-gray-200"
          >
            <template v-if="addr.Latitude && addr.Longitude">
              <iframe
                v-if="Number(addr.Latitude) && Number(addr.Longitude)"
                :src="getMapUrl(addr.Latitude, addr.Longitude)"
                class="w-full h-full border-0"
              ></iframe>
            </template>
            <div
              v-else
              class="absolute inset-0 flex items-center justify-center bg-gradient-to-tr from-gray-100 to-gray-200"
            >
              <div class="text-center px-4">
                <svg
                  class="w-12 h-12 text-gray-400 mx-auto mb-2"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                  />
                </svg>
                <p class="text-gray-500 text-sm">مختصات جغرافیایی ثبت نشده است</p>
              </div>
            </div>
          </div>
        </div>

        <!-- خط جداکننده بین آدرس‌ها -->
        <div
          v-if="index < doctor.address.length - 1"
          class="border-t border-gray-100 pt-8"
        ></div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
  doctor: {
    type: Object,
    required: true,
  },
});

// دریافت URL نقشه OpenStreetMap
const getMapUrl = (lat, lng) => {
  lat = Number(lat);
  lng = Number(lng);

  const bbox = [lng - 0.002, lat - 0.002, lng + 0.002, lat + 0.002].join(",");

  const url =
    `https://www.openstreetmap.org/export/embed.html?` +
    `bbox=${bbox}&layer=mapnik&marker=${lat},${lng}`;


  return url;
};

const onMapLoad = () => {
};

const onMapError = (e) => {
};
// لینک مستقیم به OpenStreetMap

const getOSMLink = (lat, lng) => {
  return `https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=;${lat},${lng}`;
};

// لینک به Google Maps

const getGoogleMapsLink = (lat, lng) => {
  return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
};

const getNeshanUrl = (lat, lng) => {
  // return `https://nshn.ir/maps?destination=${lat},${lng}&type=drive`;
  return `https://neshan.org/maps/routing/car/destination/${lat},${lng}#c37.475-57.333-21z-0p`;
};
</script>

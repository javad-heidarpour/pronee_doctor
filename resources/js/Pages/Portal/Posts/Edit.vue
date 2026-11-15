<template>
  <PortalLayout :doctor="doctor">
    <div
      class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 pb-12"
    >
      <!-- Header -->
      <header
        class="bg-white/80 backdrop-blur-lg border-b border-slate-200 sticky top-0 z-40 shadow-sm"
      >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1
                class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
              >
                ویرایش مقاله
              </h1>
              <p class="text-slate-600 mt-1 text-sm">
                تغییرات خود را با دقت بررسی و ذخیره کنید
              </p>
            </div>
            <div class="flex items-center gap-3">
              <button
                @click="router.visit(route('portal.posts.index'))"
                class="px-5 py-2.5 border border-slate-200 text-slate-700 rounded-xl font-medium hover:bg-slate-50 transition"
              >
                انصراف
              </button>
              <button
                @click="submitForm"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium hover:shadow-lg hover:scale-105 transition-all duration-300 shadow-md disabled:opacity-70 disabled:cursor-not-allowed"
              >
                <svg
                  v-if="form.processing"
                  class="animate-spin h-5 w-5 text-white"
                  xmlns="http://www.w3.org/2000/svg"
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
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
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
                ذخیره تغییرات
              </button>
            </div>
          </div>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- 🔴 پیام خطای کلی در بالای فرم (برای خطاهای عمومی سرور) -->
          <div v-if="form.errors.error || form.hasErrors" class="lg:col-span-3">
            <div class="bg-red-50 border-r-4 border-red-500 p-4 rounded-lg">
              <div class="flex items-start gap-3">
                <svg
                  class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <div class="flex-1">
                  <h3 class="text-sm font-semibold text-red-800">خطا در ذخیره‌سازی</h3>
                  <p class="text-sm text-red-700 mt-1">
                    {{ form.errors.error || "لطفاً خطاهای زیر را بررسی و اصلاح کنید." }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Content Column -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Title & Slug -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
              <div class="space-y-4">
                <!-- Title -->
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold text-slate-700">
                      عنوان مقاله <span class="text-red-500">*</span>
                    </label>
                    <button
                      type="button"
                      @click="generateTitle"
                      class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition font-medium"
                    >
                      ✨ تولید خودکار از محتوا
                    </button>
                  </div>

                  <input
                    v-model="form.title"
                    type="text"
                    :class="[
                      'w-full px-4 py-3 bg-slate-50 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent transition text-lg font-medium',
                      form.errors.title
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-slate-200 focus:ring-indigo-500',
                    ]"
                    placeholder="عنوان جذاب و کامل مقاله را وارد کنید..."
                  />

                  <p
                    v-if="form.errors.title"
                    class="text-red-500 text-xs mt-1 flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    {{ form.errors.title }}
                  </p>
                </div>

                <!-- Slug -->
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2"
                    >نامک (Slug)</label
                  >
                  <div class="flex gap-2">
                    <input
                      v-model="form.slug"
                      type="text"
                      :class="[
                        'flex-1 px-4 py-3 bg-slate-50 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent transition font-mono text-left dir-ltr',
                        form.errors.slug
                          ? 'border-red-300 focus:ring-red-500'
                          : 'border-slate-200 focus:ring-indigo-500',
                      ]"
                      placeholder="post-slug"
                    />
                    <button
                      type="button"
                      @click="generateSlug"
                      class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition text-sm font-medium"
                    >
                      تولید خودکار
                    </button>
                  </div>
                  <p
                    v-if="form.errors.slug"
                    class="text-red-500 text-xs mt-1 flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    {{ form.errors.slug }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Excerpt -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-semibold text-slate-700">
                  خلاصه مقاله <span class="text-red-500">*</span>
                </label>
                <button
                  type="button"
                  @click="generateExcerpt"
                  class="text-xs px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition font-medium"
                >
                  ✨ تولید خودکار از محتوا
                </button>
              </div>

              <textarea
                v-model="form.excerpt"
                rows="3"
                :class="[
                  'w-full px-4 py-3 bg-slate-50 border rounded-xl focus:outline-none focus:ring-2 focus:border-transparent transition resize-none',
                  form.errors.excerpt
                    ? 'border-red-300 focus:ring-red-500'
                    : 'border-slate-200 focus:ring-indigo-500',
                ]"
                placeholder="چکیده‌ای کوتاه از مقاله که در لیست مقالات نمایش داده می‌شود..."
              ></textarea>

              <p
                v-if="form.errors.excerpt"
                class="text-red-500 text-xs mt-1 flex items-center gap-1"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                {{ form.errors.excerpt }}
              </p>
            </div>
            <!-- Content -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                محتوای اصلی <span class="text-red-500">*</span>
              </label>

              <!-- کامپوننت ادیتور TipTap -->
              <TipTapEditor v-model="form.content" />

              <!-- نمایش خطا -->
              <p
                v-if="form.errors.content"
                class="text-red-500 text-xs mt-2 flex items-center gap-1"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                {{ form.errors.content }}
              </p>
            </div>

            <!-- SEO Section -->
            <div
              class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
            >
              <button
                type="button"
                @click="showSeo = !showSeo"
                class="w-full px-6 py-4 flex items-center justify-between bg-slate-50/50 hover:bg-slate-50 transition"
              >
                <span class="font-semibold text-slate-700 flex items-center gap-2">
                  <svg
                    class="w-5 h-5 text-indigo-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                  تنظیمات سئو (SEO)
                </span>
                <svg
                  :class="[
                    'w-5 h-5 text-slate-400 transition-transform',
                    showSeo ? 'rotate-180' : '',
                  ]"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <div v-if="showSeo" class="p-6 space-y-4 border-t border-slate-100">
                <!-- Meta Title -->
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1"
                    >عنوان سئو (Meta Title)</label
                  >
                  <input
                    v-model="form.meta_title"
                    type="text"
                    :class="[
                      'w-full px-4 py-2.5 bg-slate-50 border rounded-xl focus:ring-2 focus:outline-none',
                      form.errors.meta_title
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-slate-200 focus:ring-indigo-500',
                    ]"
                  />
                  <p v-if="form.errors.meta_title" class="text-red-500 text-xs mt-1">
                    {{ form.errors.meta_title }}
                  </p>
                </div>

                <!-- Meta Description -->
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1"
                    >توضیحات متا (Meta Description)</label
                  >
                  <textarea
                    v-model="form.meta_description"
                    rows="2"
                    :class="[
                      'w-full px-4 py-2.5 bg-slate-50 border rounded-xl focus:ring-2 focus:outline-none resize-none',
                      form.errors.meta_description
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-slate-200 focus:ring-indigo-500',
                    ]"
                  ></textarea>
                  <p
                    v-if="form.errors.meta_description"
                    class="text-red-500 text-xs mt-1"
                  >
                    {{ form.errors.meta_description }}
                  </p>
                </div>

                <!-- Keywords -->
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1"
                    >کلمات کلیدی (با Enter جدا کنید)</label
                  >
                  <div
                    :class="[
                      'flex flex-wrap gap-2 p-2 bg-slate-50 border rounded-xl focus-within:ring-2',
                      form.errors.keywords
                        ? 'border-red-300 focus-within:ring-red-500'
                        : 'border-slate-200 focus-within:ring-indigo-500',
                    ]"
                  >
                    <span
                      v-for="(keyword, index) in form.keywords"
                      :key="index"
                      class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-sm"
                    >
                      {{ keyword }}
                      <button
                        type="button"
                        @click="removeKeyword(index)"
                        class="hover:text-red-500"
                      >
                        ×
                      </button>
                    </span>
                    <input
                      v-model="newKeyword"
                      @keydown.enter.prevent="addKeyword"
                      type="text"
                      class="flex-1 bg-transparent border-none focus:ring-0 p-1 text-sm min-w-[120px]"
                      placeholder="کلمه جدید..."
                    />
                  </div>
                  <p v-if="form.errors.keywords" class="text-red-500 text-xs mt-1">
                    {{ form.errors.keywords }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar Column -->
          <div class="space-y-6">
            <div
              class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sticky top-24"
            >
              <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <svg
                  class="w-5 h-5 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                  />
                </svg>
                تنظیمات انتشار
              </h3>

              <div class="space-y-4">
                <!-- Category -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1"
                    >دسته‌بندی</label
                  >
                  <select
                    v-model="form.category_id"
                    :class="[
                      'w-full px-3 py-2.5 bg-slate-50 border rounded-xl focus:ring-2 focus:outline-none',
                      form.errors.category_id
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-slate-200 focus:ring-indigo-500',
                    ]"
                  >
                    <option value="">انتخاب کنید...</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">
                    {{ form.errors.category_id }}
                  </p>
                </div>

                <!-- Author -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1"
                    >نویسنده</label
                  >
                  <select
                    v-model="form.author_id"
                    :class="[
                      'w-full px-3 py-2.5 bg-slate-50 border rounded-xl focus:ring-2 focus:outline-none',
                      form.errors.author_id
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-slate-200 focus:ring-indigo-500',
                    ]"
                  >
                    <option value="">انتخاب کنید...</option>
                    <option v-for="author in authors" :key="author.id" :value="author.id">
                      {{ author.name }}
                    </option>
                  </select>
                  <p v-if="form.errors.author_id" class="text-red-500 text-xs mt-1">
                    {{ form.errors.author_id }}
                  </p>
                </div>

                <hr class="border-slate-100" />

                <!-- Toggles -->
                <div class="space-y-3">
                  <label class="flex items-center justify-between cursor-pointer group">
                    <span
                      class="text-sm font-medium text-slate-700 group-hover:text-indigo-600 transition"
                      >منتشر شود</span
                    >
                    <div class="relative">
                      <input
                        type="checkbox"
                        v-model="form.is_published"
                        class="sr-only peer"
                      />
                      <div
                        class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"
                      ></div>
                    </div>
                  </label>

                  <label class="flex items-center justify-between cursor-pointer group">
                    <span
                      class="text-sm font-medium text-slate-700 group-hover:text-amber-600 transition"
                      >مقاله ویژه</span
                    >
                    <div class="relative">
                      <input
                        type="checkbox"
                        v-model="form.is_featured"
                        class="sr-only peer"
                      />
                      <div
                        class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"
                      ></div>
                    </div>
                  </label>

                  <label class="flex items-center justify-between cursor-pointer group">
                    <span
                      class="text-sm font-medium text-slate-700 group-hover:text-purple-600 transition"
                      >پین (سنجاق) شود</span
                    >
                    <div class="relative">
                      <input
                        type="checkbox"
                        v-model="form.is_pinned"
                        class="sr-only peer"
                      />
                      <div
                        class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"
                      ></div>
                    </div>
                  </label>
                </div>

                <hr class="border-slate-100" />
                <!-- Featured Image -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2"
                    >تصویر شاخص</label
                  >

                  <div
                    :class="[
                      'relative border-2 border-dashed rounded-xl p-4 text-center transition cursor-pointer group',
                      form.errors.featured_image || imageError
                        ? 'border-red-300 hover:border-red-500 hover:bg-red-50/30'
                        : 'border-slate-300 hover:border-indigo-500 hover:bg-indigo-50/30',
                    ]"
                    @click="$refs.imageInput.click()"
                  >
                    <input
                      type="file"
                      ref="imageInput"
                      class="hidden"
                      accept="image/*"
                      @change="handleImageUpload"
                    />

                    <!-- 🔴 Loading Spinner -->
                    <div v-if="imageLoading" class="py-6">
                      <div class="flex flex-col items-center justify-center">
                        <svg
                          class="animate-spin h-10 w-10 text-indigo-600 mb-3"
                          xmlns="http://www.w3.org/2000/svg"
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
                          ></circle>
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                          ></path>
                        </svg>
                        <p class="text-sm text-slate-600 font-medium">
                          در حال پردازش تصویر...
                        </p>
                      </div>
                    </div>

                    <!-- 🔴 نمایش تصویر (وقتی لودینگ تمام شد) -->
                    <div v-else-if="imageSrc" class="relative">
                      <img :src="imageSrc" class="w-full h-40 object-cover rounded-lg" />
                      <button
                        type="button"
                        @click.stop="removeImage"
                        class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-full shadow-md hover:bg-red-600 transition"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                          />
                        </svg>
                      </button>
                    </div>

                    <!-- 🔴 نمایش آیکون آپلود (وقتی تصویری نیست) -->
                    <div v-else class="py-6">
                      <svg
                        class="mx-auto h-10 w-10 text-slate-400 group-hover:text-indigo-500 transition"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                      </svg>
                      <p class="mt-2 text-sm text-slate-500">
                        برای آپلود کلیک کنید یا فایل را بکشید
                      </p>
                      <p class="text-xs text-slate-400">PNG, JPG, WEBP تا 5MB</p>
                    </div>
                  </div>

                  <!-- نمایش خطاها -->
                  <p
                    v-if="form.errors.featured_image"
                    class="text-red-500 text-xs mt-1 flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    {{ form.errors.featured_image }}
                  </p>

                  <p
                    v-if="imageError"
                    class="text-red-500 text-xs mt-1 flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    {{ imageError }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </form>
      </main>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import PortalLayout from "@/Layouts/PortalLayout.vue";
import TipTapEditor from "./Components/Edit/TipTapEditor.vue";

// 🔴 ۲. تنظیمات نوار ابزار (فقط ابزارهای ضروری و کاربردی)
const editorModules = {
  toolbar: [
    [{ header: [1, 2, 3, false] }], // تیترهای H1, H2, H3 و متن عادی
    ["bold", "italic", "underline", "strike"], // استایل‌های متن
    [{ color: [] }, { background: [] }], // رنگ متن و پس‌زمینه
    [{ list: "ordered" }, { list: "bullet" }], // لیست شماره‌دار و نقطه‌ای
    ["link", "image"], // لینک و تصویر
    ["clean"], // دکمه پاک کردن فرمت‌ها
  ],
};
const props = defineProps({
   doctor: {
    type: Object,
    required: true,
  },
  post: Object,
  categories: Array,
  authors: Array,
});
// تولید عنوان از محتوای مقاله
const generateTitle = () => {
  if (!form.content) {
    alert("ابتدا محتوای مقاله را بنویسید.");
    return;
  }

  // ایجاد یک عنصر موقت برای پارس کردن HTML
  const tempDiv = document.createElement("div");
  tempDiv.innerHTML = form.content;

  // تلاش برای پیدا کردن اولین H1 یا H2
  const h1 = tempDiv.querySelector("h1");
  const h2 = tempDiv.querySelector("h2");

  let title = "";

  if (h1) {
    title = h1.textContent.trim();
  } else if (h2) {
    title = h2.textContent.trim();
  } else {
    // اگر تیتری نبود، اولین ۶۰ کاراکتر از متن را بگیر
    const plainText = tempDiv.textContent || tempDiv.innerText || "";
    title = plainText.trim().substring(0, 60);

    // اگر متن طولانی بود، در آخرین فاصله قطع کن
    if (plainText.length > 60) {
      const lastSpace = title.lastIndexOf(" ");
      if (lastSpace > 30) {
        title = title.substring(0, lastSpace);
      }
      title += "...";
    }
  }

  // محدود کردن به ۲۵۵ کاراکتر (حداکثر طول عنوان)
  if (title.length > 255) {
    title = title.substring(0, 255);
  }

  form.title = title;

  // تولید خودکار slug همزمان
  if (!form.slug) {
    generateSlug();
  }
};
// تولید خلاصه از محتوای مقاله
const generateExcerpt = () => {
  if (!form.content) {
    alert("ابتدا محتوای مقاله را بنویسید.");
    return;
  }

  // حذف تگ‌های HTML
  const tempDiv = document.createElement("div");
  tempDiv.innerHTML = form.content;
  const plainText = tempDiv.textContent || tempDiv.innerText || "";

  // محدود کردن به ۱۵۰ کاراکتر و افزودن ... در انتها
  const maxLength = 150;
  let excerpt = plainText.trim();

  if (excerpt.length > maxLength) {
    excerpt = excerpt.substring(0, maxLength).trim();
    // پیدا کردن آخرین فاصله برای قطع نکردن کلمه
    const lastSpace = excerpt.lastIndexOf(" ");
    if (lastSpace > 0) {
      excerpt = excerpt.substring(0, lastSpace);
    }
    excerpt += "...";
  }

  form.excerpt = excerpt;
};
// مقداردهی اولیه فرم با داده‌های مقاله موجود
const form = useForm({
  title: props.post.title || "",
  slug: props.post.slug || "",
  excerpt: props.post.excerpt || "",
  content: props.post.content || "",
  category_id: props.post.category_id || "",
  author_id: props.post.author_id || "",

  // 🔴 تغییر کلیدی: همیشه null باشد تا Inertia گیج نشود
  featured_image: null,

  meta_title: props.post.meta_title || "",
  meta_description: props.post.meta_description || "",
  keywords: props.post.keywords || [],
  is_published: props.post.is_published || false,
  is_featured: props.post.is_featured || false,
  is_pinned: props.post.is_pinned || false,
});
const showSeo = ref(false);
const newKeyword = ref("");
const imagePreview = ref(null);
const imageInput = ref(null);

const imageLoading = ref(false); // 🔴 جدید: برای نمایش loading

// تولید خودکار اسلاگ از عنوان
const generateSlug = () => {
  if (form.title) {
    form.slug = form.title
      .replace(/[^a-zA-Z0-9\u0600-\u06FF\s]/g, "")
      .replace(/\s+/g, "-")
      .toLowerCase();
  }
};

// مدیریت کلمات کلیدی
const addKeyword = () => {
  const keyword = newKeyword.value.trim();
  if (keyword && !form.keywords.includes(keyword)) {
    form.keywords.push(keyword);
    newKeyword.value = "";
  }
};

const removeKeyword = (index) => {
  form.keywords.splice(index, 1);
};

// مدیریت آپلود تصویر
// متغیر جدید برای ذخیره پیام خطای حجم فایل (اگر قبلاً نساخته‌اید، این را اضافه کنید)
const imageError = ref("");

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    // 🔴 ۱. بررسی حجم فایل (حداکثر ۵ مگابایت)
    const maxSize = 5 * 1024 * 1024; // 5 MB به بایت

    if (file.size > maxSize) {
      // نمایش خطا زیر فیلد
      imageError.value = `حجم تصویر انتخابی (${(file.size / 1024 / 1024).toFixed(
        2
      )} مگابایت) بیش از حد مجاز (۵ مگابایت) است.`;

      // پاک کردن فایل انتخاب‌شده از اینپوت
      event.target.value = "";
      return; // توقف اجرای تابع (فایل به فرم اضافه نمی‌شود)
    }

    // 🔴 ۲. اگر حجم مناسب بود، ادامه بده
    imageError.value = ""; // پاک کردن خطاهای قبلی
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);

  }
};

const removeImage = () => {
  form.featured_image = null;
  imagePreview.value = null;
  imageError.value = ""; // پاک کردن خطا هنگام حذف عکس
  if (imageInput.value) imageInput.value.value = "";
};
const imageSrc = computed(() => {
  // ۱. اولویت اول: اگر کاربر عکس جدیدی انتخاب کرده باشد (پیش‌نمایش مرورگر)
  if (imagePreview.value) {
    return imagePreview.value;
  }

  // ۲. اولویت دوم: اگر عکس جدیدی انتخاب نشده، عکس قدیمی را از دیتابیس بخوان
  // نکته کلیدی: چون form.featured_image را null گذاشتیم، مستقیماً به props.post مراجعه می‌کنیم
  const oldImage = props.post.featured_image;

  if (oldImage) {
    // اگر آدرس کامل (http) یا نسبی (/) است، همان را برگردان
    if (oldImage.startsWith("http") || oldImage.startsWith("/")) {
      return oldImage;
    }
    // اگر فقط نام فایل است (مثلاً posts/image.jpg)، پیشوند storage را اضافه کن
    return "/storage/" + oldImage;
  }

  // ۳. اولویت سوم: اگر به هر دلیلی فایل در فرم بود (محض اطمینان)
  if (form.featured_image instanceof File) {
    return URL.createObjectURL(form.featured_image);
  }

  // ۴. در غیر این صورت چیزی نمایش نده
  return null;
});
const submitForm = () => {
  form.post(route("portal.posts.update", props.post.id), {
    preserveScroll: true,

    // 🔴 این خط به Inertia دستور می‌دهد حتماً به صورت فایل ارسال کند
    forceFormData: true,

    transform: (data) => {
      return {
        ...data,
        keywords: Array.isArray(data.keywords) ? data.keywords : [],
      };
    },
    onSuccess: () => {
    },
    onError: (errors) => {
    },
  });
};
</script>

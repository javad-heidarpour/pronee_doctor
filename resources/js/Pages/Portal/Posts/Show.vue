<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    post: Object,
    relatedPosts: Array
})

// ========== Reading Progress ==========
const scrollProgress = ref(0)
const showBackToTop = ref(false)

const updateProgress = () => {
    const winScroll = document.documentElement.scrollTop
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight
    scrollProgress.value = (winScroll / height) * 100
    showBackToTop.value = winScroll > 500
}

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ========== Table of Contents ==========
const headings = ref([])
const activeHeading = ref('')

const extractHeadings = () => {
    const content = document.querySelector('.post-content')
    if (!content) return
    const nodes = content.querySelectorAll('h2, h3')
    headings.value = Array.from(nodes).map((el, i) => {
        const id = `heading-${i}`
        el.id = id
        return {
            id,
            text: el.textContent,
            level: el.tagName
        }
    })
}

const handleScrollSpy = () => {
    const positions = headings.value.map(h => {
        const el = document.getElementById(h.id)
        return { id: h.id, top: el.getBoundingClientRect().top }
    })
    const current = positions.find(p => p.top >= 0 && p.top < 200)
    if (current) activeHeading.value = current.id
}

// ========== Share ==========
const shareUrl = computed(() => window.location.href)
const share = (platform) => {
    const urls = {
        twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent(props.post.title)}&url=${encodeURIComponent(shareUrl.value)}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}`,
        telegram: `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(props.post.title)}`
    }
    window.open(urls[platform], '_blank', 'width=600,height=400')
}

const copyLink = async () => {
    await navigator.clipboard.writeText(shareUrl.value)
    alert('لینک کپی شد!')
}

// ========== Reading Time ==========
const readingTime = computed(() => {
    const words = props.post.content?.replace(/<[^>]*>/g, '').split(/\s+/).length || 0
    return Math.max(1, Math.ceil(words / 200))
})

// ========== Formatted Date ==========
const formattedDate = computed(() => {
    return new Date(props.post.published_at).toLocaleDateString('fa-IR', {
        year: 'numeric', month: 'long', day: 'numeric'
    })
})

onMounted(() => {
    window.addEventListener('scroll', () => {
        updateProgress()
        handleScrollSpy()
    })
    extractHeadings()
})

onUnmounted(() => {
    window.removeEventListener('scroll', updateProgress)
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 text-slate-800 dark:text-slate-100">
        
        <!-- Reading Progress Bar -->
        <div class="fixed top-0 left-0 right-0 h-1 bg-transparent z-50">
            <div
                class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 transition-all duration-150"
                :style="{ width: scrollProgress + '%' }"
            />
        </div>

        <!-- Hero Section -->
        <section class="relative h-[70vh] min-h-[500px] overflow-hidden">
            <div
                class="absolute inset-0 bg-cover bg-center scale-105 transition-transform duration-[3s]"
                :style="{ backgroundImage: `url(${post.cover_image})` }"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent" />
            
            <!-- Back Button -->
            <div class="absolute top-6 right-6 z-10">
                <Link
                    :href="route('portal.posts.index')"
                    class="flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 text-white rounded-full hover:bg-white/20 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    بازگشت
                </Link>
            </div>

            <!-- Hero Content -->
            <div class="relative z-10 h-full flex items-end pb-16 px-6 max-w-5xl mx-auto">
                <div class="text-white space-y-6 animate-fade-in-up">
                    <div class="flex items-center gap-3 flex-wrap">
                        <Link
                            :href="route('portal.categories.show', post.category.slug)"
                            class="px-4 py-1.5 bg-gradient-to-r from-indigo-500 to-purple-500 text-sm font-medium rounded-full hover:shadow-lg hover:shadow-indigo-500/50 transition"
                        >
                            {{ post.category.name }}
                        </Link>
                        <span class="flex items-center gap-1.5 text-sm text-white/80">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ readingTime }} دقیقه مطالعه
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-6xl font-black leading-tight drop-shadow-2xl">
                        {{ post.title }}
                    </h1>

                    <p v-if="post.excerpt" class="text-lg md:text-xl text-white/90 max-w-3xl leading-relaxed">
                        {{ post.excerpt }}
                    </p>

                    <!-- Author -->
                    <div class="flex items-center gap-4 pt-4">
                        <img
                            :src="post.author.avatar"
                            :alt="post.author.name"
                            class="w-12 h-12 rounded-full border-2 border-white/30 object-cover"
                        />
                        <div>
                            <div class="font-semibold">{{ post.author.name }}</div>
                            <div class="text-sm text-white/70">{{ formattedDate }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-12">
            
            <!-- Article -->
            <article class="min-w-0">
                <div
                    class="post-content prose prose-lg dark:prose-invert max-w-none
                           prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white
                           prose-p:leading-8 prose-p:text-slate-700 dark:prose-p:text-slate-300
                           prose-a:text-indigo-600 dark:prose-a:text-indigo-400 prose-a:no-underline hover:prose-a:underline
                           prose-img:rounded-2xl prose-img:shadow-xl
                           prose-blockquote:border-indigo-500 prose-blockquote:bg-indigo-50 dark:prose-blockquote:bg-indigo-950/30 prose-blockquote:rounded-r-xl prose-blockquote:py-2 prose-blockquote:px-6
                           prose-code:bg-slate-100 dark:prose-code:bg-slate-800 prose-code:px-2 prose-code:py-0.5 prose-code:rounded prose-code:text-pink-600 dark:prose-code:text-pink-400
                           prose-pre:bg-slate-900 prose-pre:text-slate-100 prose-pre:rounded-2xl"
                    v-html="post.content"
                />

                <!-- Tags -->
                <div v-if="post.tags?.length" class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-500 mb-4">برچسب‌ها</h3>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="tag in post.tags"
                            :key="tag.id"
                            :href="route('portal.tags.show', tag.slug)"
                            class="group px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-full text-sm font-medium transition-all duration-300"
                        >
                            #{{ tag.name }}
                        </Link>
                    </div>
                </div>

                <!-- Author Card -->
                <div class="mt-12 p-8 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-3xl border border-indigo-100 dark:border-indigo-900/50">
                    <div class="flex items-start gap-6">
                        <img :src="post.author.avatar" class="w-20 h-20 rounded-2xl object-cover shadow-lg" />
                        <div class="flex-1">
                            <div class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mb-1">نوشته شده توسط</div>
                            <h3 class="text-xl font-bold mb-2">{{ post.author.name }}</h3>
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                {{ post.author.bio || 'نویسنده و تولیدکننده محتوا در حوزه فناوری و برنامه‌نویسی.' }}
                            </p>
                            <Link
                                :href="route('portal.authors.show', post.author.id)"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:gap-3 transition-all"
                            >
                                مشاهده پروفایل
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Related Posts -->
                <div v-if="relatedPosts?.length" class="mt-16">
                    <h2 class="text-2xl font-bold mb-8 flex items-center gap-3">
                        <span class="w-1 h-8 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"/>
                        پست‌های مرتبط
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Link
                            v-for="related in relatedPosts"
                            :key="related.id"
                            :href="route('portal.posts.show', related.slug)"
                            class="group bg-white dark:bg-slate-800/50 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300"
                        >
                            <div class="aspect-video overflow-hidden">
                                <img
                                    :src="related.cover_image"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                            </div>
                            <div class="p-5">
                                <div class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mb-2">
                                    {{ related.category.name }}
                                </div>
                                <h3 class="font-bold line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                    {{ related.title }}
                                </h3>
                            </div>
                        </Link>
                    </div>
                </div>
            </article>

            <!-- Sidebar -->
            <aside class="hidden lg:block">
                <div class="sticky top-8 space-y-6">
                    
                    <!-- Table of Contents -->
                    <div v-if="headings.length" class="p-6 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-bold mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                            فهرست مطالب
                        </h3>
                        <nav class="space-y-2">
                            <a
                                v-for="h in headings"
                                :key="h.id"
                                :href="`#${h.id}`"
                                class="block text-sm py-1 border-r-2 transition-all duration-200"
                                :class="[
                                    h.level === 'H2' ? 'pr-3 font-medium' : 'pr-3 pl-4 text-xs text-slate-500',
                                    activeHeading === h.id
                                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'border-transparent hover:border-slate-300 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                {{ h.text }}
                            </a>
                        </nav>
                    </div>

                    <!-- Share -->
                    <div class="p-6 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl text-white">
                        <h3 class="text-sm font-bold mb-4">اشتراک‌گذاری</h3>
                        <div class="flex gap-2">
                            <button @click="share('twitter')" class="flex-1 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition" title="Twitter">
                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                            </button>
                            <button @click="share('linkedin')" class="flex-1 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition" title="LinkedIn">
                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M20.5 2h-17A1.5 1.5 0 002 3.5v17A1.5 1.5 0 003.5 22h17a1.5 1.5 0 001.5-1.5v-17A1.5 1.5 0 0020.5 2zM8 19H5v-9h3zM6.5 8.25A1.75 1.75 0 118.3 6.5a1.78 1.78 0 01-1.8 1.75zM19 19h-3v-4.74c0-1.42-.6-1.93-1.38-1.93A1.74 1.74 0 0013 14.19a.66.66 0 000 .14V19h-3v-9h2.9v1.3a3.11 3.11 0 012.7-1.4c1.55 0 3.36.86 3.36 3.66z"/></svg>
                            </button>
                            <button @click="share('telegram')" class="flex-1 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition" title="Telegram">
                                <svg class="w-5 h-5 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            </button>
                            <button @click="copyLink" class="flex-1 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition" title="کپی لینک">
                                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Back to Top -->
        <Transition name="fade">
            <button
                v-if="showBackToTop"
                @click="scrollToTop"
                class="fixed bottom-8 left-8 w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-full shadow-lg shadow-indigo-500/50 hover:scale-110 transition-transform z-40 flex items-center justify-center"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </button>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out;
}
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
.post-content :deep(.drop-cap:first-letter) {
    font-size: 3.5em;
    float: right;
    line-height: 1;
    margin-left: 0.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
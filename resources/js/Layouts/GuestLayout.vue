<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <main class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-8 sm:px-6">
        <div class="mb-2">
            <Link href="/" aria-label="صفحه اصلی پرونیکس">
                <ApplicationLogo class="h-16 w-16 fill-current text-gray-500 sm:h-20 sm:w-20" />
            </Link>
        </div>
        <section class="mt-4 w-full max-w-md rounded-xl bg-white px-5 py-6 shadow-md sm:px-8 sm:py-7">
            <slot />
        </section>
    </main>
</template>

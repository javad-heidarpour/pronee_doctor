// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // ✅ فعال‌سازی Dark Mode با کلاس
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazir', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ✅ رنگ‌های سفارشی بر اساس متغیرهای CSS
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    light: 'var(--color-primary-light)',
                    dark: 'var(--color-primary-dark)',
                },
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                },
                accent: {
                    DEFAULT: 'var(--color-accent)',
                },
            },
            typography: (theme) => ({
                DEFAULT: {
                    css: {
                        maxWidth: 'none',
                        color: theme('colors.gray.700'),
                        a: {
                            color: theme('colors.teal.600'),
                            '&:hover': {
                                color: theme('colors.teal.700'),
                            },
                        },
                        h1: {
                            color: theme('colors.gray.900'),
                            fontWeight: '800',
                        },
                        h2: {
                            color: theme('colors.gray.900'),
                            fontWeight: '700',
                        },
                        h3: {
                            color: theme('colors.gray.900'),
                            fontWeight: '700',
                        },
                        code: {
                            color: theme('colors.teal.600'),
                            backgroundColor: theme('colors.teal.50'),
                            padding: '0.25rem 0.5rem',
                            borderRadius: '0.375rem',
                            fontWeight: '400',
                        },
                    },
                },
            }),
        },
    },
    plugins: [forms, typography],
};
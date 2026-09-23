import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
                script: ['Caveat', 'cursive'],
            },
            colors: {
                primary: {
                    DEFAULT: '#16A36A',
                    dark: '#075B55',
                    soft: '#EAF8F1',
                    50: '#F3FBF7',
                    100: '#EAF8F1',
                    600: '#16A36A',
                    700: '#128A5A',
                    800: '#075B55',
                },
                surface: '#F7FAF9',
                ink: '#0F2F3B',
                muted: '#6B7C85',
                danger: '#E85C67',
                warning: '#E8A93A',
                line: '#E5ECE9',
            },
            boxShadow: {
                card: '0 8px 30px rgba(15, 47, 59, 0.06)',
                soft: '0 4px 18px rgba(15, 47, 59, 0.05)',
            },
            borderRadius: {
                '2xl': '1.25rem',
            },
        },
    },
    plugins: [forms],
};

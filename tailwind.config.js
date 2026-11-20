import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './Modules/*/resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // XERIN GROUP brand — logo palette (orange primary, charcoal neutrals)
                xerin: {
                    50: '#FEF5EC', 100: '#FCE8D5', 200: '#F9CFA9', 300: '#F5AE72',
                    400: '#F18C42', 500: '#F47721', 600: '#E86612', 700: '#C2550E',
                    800: '#9B4512', 900: '#7D3A14', 950: '#431E07',
                },
                gray: {
                    50: '#F5F6F8', 100: '#EDEEF0', 200: '#E2E5E9', 300: '#D2D6DC',
                    400: '#A7AFB9', 500: '#737B85', 600: '#5B636D', 700: '#41464D',
                    800: '#30353B', 900: '#252A30', 950: '#202328',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};

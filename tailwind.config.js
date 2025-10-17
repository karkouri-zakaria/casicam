/** @type {import('tailwindcss').Config} */
export const content = [
  './src/**/*.{html,js}',
  './index.php'
];
export const theme = {
  extend: {
    colors: {
      'crimson': '#651C25',
    },
    fontFamily: {
      houstiq: ['Houstiq', 'sans-serif'],
      gilroy: ['Gilroy', 'sans-serif'],
      saira: ['Saira', 'sans-serif'],
    },
  },
};
export const plugins = [
  require('tailwindcss'),
  require('autoprefixer'),
];
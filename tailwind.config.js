/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.html.twig', // Pour Twig
    './src/**/*.{php,html}',
    './assets/**/*.{js,ts,vue}',
  ],
  theme: {
    extend: {

    },
  },
  plugins: [],
}


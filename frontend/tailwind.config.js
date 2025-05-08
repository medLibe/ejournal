/** @type {import('tailwindcss').Config} */
import primeui from 'tailwindcss-primeui'

export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
    fontFamily: {
      'manrope': ['manrope', 'serif'], 
      'inter': ['inter', 'serif'], 
      'montserrat': ['montserrat', 'serif'], 
    }
  },
  plugins: [primeui]
}
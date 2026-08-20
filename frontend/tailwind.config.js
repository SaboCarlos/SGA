/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        // Paleta institucional provisoria (RNF-18: interface em portugues,
        // mas as cores tambem carecem de validacao junto da ESCN).
        escn: {
          azul: '#1E3A5F',
          dourado: '#C9A227',
        },
      },
    },
  },
  plugins: [],
}

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  darkMode: 'media',
  theme: {
    extend: {
      colors: {
        // Paleta institucional (ver src/index.css para os valores por tema).
        // "profunda" e o dourado sao fixos (a barra lateral funciona como
        // moldura da aplicacao, independente do tema claro/escuro).
        tinta: 'var(--tinta)',
        'tinta-profunda': 'var(--tinta-profunda)',
        dourado: 'var(--dourado)',
        'dourado-suave': 'var(--dourado-suave)',
        bruma: 'var(--bruma)',
        superficie: 'var(--superficie)',
        'superficie-alt': 'var(--superficie-alt)',
        texto: 'var(--tinta-texto)',
        aco: 'var(--aco)',
        'aco-suave': 'var(--aco-suave)',
        linha: 'var(--linha)',
        sucesso: 'var(--sucesso)',
        'sucesso-bg': 'var(--sucesso-bg)',
        perigo: 'var(--perigo)',
        'perigo-bg': 'var(--perigo-bg)',
        aviso: 'var(--aviso)',
        'aviso-bg': 'var(--aviso-bg)',
      },
      fontFamily: {
        display: ['Fraunces', 'Georgia', 'Times New Roman', 'serif'],
        sans: ['Public Sans', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

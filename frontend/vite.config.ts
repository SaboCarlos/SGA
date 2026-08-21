import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'

// Configuracao do Vite para o SGA.
// O proxy /api evita CORS em desenvolvimento local (fora do Docker Compose,
// onde o Nginx ja resolve o mesmo papel — ver docker/nginx/default.conf).
// defineConfig vem de 'vitest/config' (reexporta o de 'vite') só para o
// bloco `test` abaixo ser reconhecido pelo TypeScript.
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
  },
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/setupTestes.ts'],
    globals: true,
  },
})

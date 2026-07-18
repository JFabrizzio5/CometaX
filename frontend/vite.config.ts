import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],

  server: {
    // Expose en 0.0.0.0 para que sea accesible desde el host (WSL/Docker)
    host: '0.0.0.0',
    port: 5173,

    // Proxy: en modo dev, las llamadas a /api/* van al backend Laravel
    proxy: {
      '/api': {
        target: process.env.VITE_API_URL ?? 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
})

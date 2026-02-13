import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'   // <-- import path module

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),   // <-- add alias here
    },
  },
  server: {
    host: true,           // expose to LAN / ngrok
    port: 5173,           // dev server port
    strictPort: false,
    allowedHosts: [
      'vanda-crocused-stefanie.ngrok-free.dev'
    ],
    cors: true            // allow ngrok to access dev server
  },
  preview: {
    host: true,
    port: 4173
  }
})

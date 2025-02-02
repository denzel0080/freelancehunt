import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

export default defineConfig({
  plugins: [vue()],
  root: './assets',
  base: process.env.APP_ENV === 'dev' ? '/' : '/dist/',
  
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './assets'),
      'vue': 'vue/dist/vue.esm-bundler.js' 
    }
  },

  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    manifest: true,
  },

  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://localhost:5173'
  }
})
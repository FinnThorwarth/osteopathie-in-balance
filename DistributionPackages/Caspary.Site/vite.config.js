import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './Resources/Private/JavaScript')
    }
  },
  build: {
    manifest: true,
    outDir: 'Resources/Public',
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'Resources/Private/JavaScript/main.js'),
        styles: resolve(__dirname, 'Resources/Private/Styles/main.css')
      }
    }
  },
  server: {
    hmr: {
      protocol: 'ws',
      host: 'localhost'
    }
  }
})
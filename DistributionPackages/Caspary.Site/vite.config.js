import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],

  root: resolve(__dirname, 'Resources/Private'),

  build: {
    outDir: resolve(__dirname, 'Resources/Public'),
    emptyOutDir: false,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'Resources/Private/JavaScript/main.js'),
        app: resolve(__dirname, 'Resources/Private/Styles/app.css')
      },
      output: {
        entryFileNames: 'JavaScript/[name].js',
        chunkFileNames: 'JavaScript/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'Styles/[name][extname]'
          }
          return 'Assets/[name][extname]'
        }
      }
    }
  },

  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173'
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'Resources/Private/JavaScript'),
      '@styles': resolve(__dirname, 'Resources/Private/Styles'),
      '@components': resolve(__dirname, 'Resources/Private/JavaScript/components')
    }
  }
})

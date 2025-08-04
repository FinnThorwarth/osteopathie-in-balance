import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import fullReload from 'vite-plugin-full-reload'
import { resolve } from 'path'
import Critters from 'critters'

export default defineConfig({
  plugins: [
    vue(),
    // Reload page when Fusion files change
    fullReload(['Resources/Private/Fusion/**/*.fusion'], {
      root: __dirname
    })
  ],
  
  define: {
    // Enable Vue Options API and runtime compiler
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false
  },

  root: resolve(__dirname, 'Resources/Private'),

  build: {
    outDir: resolve(__dirname, 'Resources/Public'),
    emptyOutDir: false,
    manifest: true,
    cssCodeSplit: false, // Keep CSS in one file for easier critical CSS extraction
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'Resources/Private/JavaScript/main.js')
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
    origin: 'http://localhost:5173',
    watch: {
      // Watch Fusion files for changes
      ignored: ['!**/Resources/Private/Fusion/**']
    }
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'Resources/Private/JavaScript'),
      '@styles': resolve(__dirname, 'Resources/Private/Styles'),
      '@components': resolve(__dirname, 'Resources/Private/JavaScript/components'),
      // Use Vue build with compiler for runtime template compilation
      'vue': 'vue/dist/vue.esm-bundler.js'
    }
  }
})

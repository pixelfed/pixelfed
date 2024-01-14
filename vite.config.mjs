import { fileURLToPath, URL } from 'node:url'
import path from 'path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import laravel from 'laravel-vite-plugin'

// https://vitejs.dev/config/
export default defineConfig({
    publicDir: "public",
  plugins: [
    vue(),
    vueJsx(),
    laravel([
        'resources/js/main.js',
    ]),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
      '~': path.resolve(__dirname, 'resources/js'),
    }
  }
})

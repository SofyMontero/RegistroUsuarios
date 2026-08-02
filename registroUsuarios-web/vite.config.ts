import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      // @digitalpersona/devices hace require("WebSdk"); Vite/Rolldown no lo resuelve solo.
      WebSdk: path.resolve(__dirname, 'src/shims/websdk.cjs'),
    },
  },
  optimizeDeps: {
    include: ['@digitalpersona/devices'],
  },
  server: {
    port: 5173,
    proxy: {
      '/api-php': {
        target: 'https://registrousuarios.edmaramericas.com',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api-php/, ''),
      },
    },
  },
});

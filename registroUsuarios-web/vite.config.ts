import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    rolldownOptions: {
      external: ['WebSdk'],
    },
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

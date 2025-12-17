// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                main: './index.html',
                login: './login.html',
                tasks: './tasks.html',
                projects: './projects.html',
                issues: './issues.html',
                users: './users.html',
            }
        }
    },
    server: {
        port: 3000,
        proxy: {
            // 🔑 Ключевое изменение: проксируем /api → в PHP-бэкенд
            '/api': {
                target: 'http://localhost:8000',   // ← где запущен php -S
                changeOrigin: true,
                rewrite: (path) => path.replace(/^\/api/, ''),
                configure: (proxy, _options) => {
                    // Обеспечиваем передачу query-параметров (action=..., id=...)
                    proxy.on('proxyReq', (proxyReq, req) => {
                        const url = new URL(req.url || '', 'http://localhost');
                        if (url.search && !proxyReq.path.includes('?')) {
                            proxyReq.path += url.search;
                        }
                    });
                }
            }
        }
    }
});
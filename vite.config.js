import { defineConfig } from 'vite'

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                main: './index.html',
                tasks: './tasks.html',
                projects: './projects.html',
                issues: './issues.html',
            }
        }
    },
    server: {
        port: 3000
    }
})
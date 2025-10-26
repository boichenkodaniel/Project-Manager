import { defineConfig } from 'vite'

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                main: './index.html',
                tasks: './tasks.html',
                projects: './projects.html',
                issues: './issues.html',
                users: './users.html',
            }
        }
    },
    server: {
        port: 3000
    }
})
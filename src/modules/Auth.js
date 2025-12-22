// modules/Auth.js

class Auth {
    constructor() {
        this.user = null;
        this.API_URL = '/api';
        this.authChecked = false;
        this.checkAuthPromise = this.checkAuth();
    }

    async checkAuth() {
        // Проверяем localStorage сначала
        const storedUser = localStorage.getItem('user');
        if (storedUser) {
            try {
                this.user = JSON.parse(storedUser);
            } catch (e) {
                console.error('Ошибка парсинга пользователя из localStorage:', e);
            }
        }

        try {
            const response = await fetch(`${this.API_URL}?action=auth.me`);
            const result = await response.json();
            
            if (result.success) {
                this.user = result.data;
                localStorage.setItem('user', JSON.stringify(result.data));
                this.authChecked = true;
                this.updateUI();
            } else {
                localStorage.removeItem('user');
                this.user = null;
                this.authChecked = true;
                // Не редиректим сразу, пусть страница загрузится
                if (window.location.pathname !== '/login.html' && !window.location.pathname.includes('login')) {
                    setTimeout(() => this.redirectToLogin(), 500);
                }
            }
        } catch (error) {
            console.error('Ошибка проверки авторизации:', error);
            // Если ошибка сети, но есть пользователь в localStorage, используем его
            if (!this.user && window.location.pathname !== '/login.html' && !window.location.pathname.includes('login')) {
                setTimeout(() => this.redirectToLogin(), 500);
            }
            this.authChecked = true;
        }
    }

    redirectToLogin() {
        if (window.location.pathname !== '/login.html') {
            window.location.href = '/login.html';
        }
    }

    async logout() {
        try {
            await fetch(`${this.API_URL}?action=auth.logout`, {
                method: 'POST'
            });
        } catch (error) {
            console.error('Ошибка выхода:', error);
        } finally {
            localStorage.removeItem('user');
            window.location.href = '/login.html';
        }
    }

    updateUI() {
        // Обновляем информацию о пользователе в интерфейсе
        const profileName = document.querySelector('.profile__name');
        const profileEmail = document.querySelector('.profile__email');
        
        if (profileName && this.user) {
            profileName.textContent = this.user.fullname || 'Пользователь';
        }
        if (profileEmail && this.user) {
            profileEmail.textContent = this.user.email || '';
        }

        // Скрываем/показываем элементы в зависимости от роли
        this.updateRoleBasedUI();
    }

    updateRoleBasedUI() {
        if (!this.user) return;

        const role = this.user.role;
        
        // Скрываем кнопку создания пользователей для не-админов
        const userTab = document.querySelector('.tab[data-tab="user"]');
        if (userTab && role !== 'Администратор') {
            userTab.style.display = 'none';
        }

        // Скрываем кнопку создания проектов для не-руководителей
        const projectTab = document.querySelector('.tab[data-tab="project"]');
        if (projectTab && role !== 'Руководитель проектов' && role !== 'Администратор') {
            projectTab.style.display = 'none';
        }

        // Скрываем кнопку создания задач для не-руководителей
        const taskTab = document.querySelector('.tab[data-tab="task"]');
        if (taskTab && role !== 'Руководитель проектов' && role !== 'Администратор') {
            taskTab.style.display = 'none';
        }

        // Скрываем ссылку на пользователей для не-админов
        const usersLink = document.querySelector('a[href="users.html"]');
        if (usersLink && role !== 'Администратор') {
            usersLink.parentElement.style.display = 'none';
        }

        // Скрываем пункт "Отчёты" для всех, кроме руководителей проектов и администраторов
        const reportsLink = document.querySelector('a[href="reports.html"]');
        if (
            reportsLink &&
            role !== 'Руководитель проектов' &&
            role !== 'Руководитель' &&
            role !== 'Администратор'
        ) {
            reportsLink.parentElement.style.display = 'none';
        }
    }

    hasRole(allowedRoles) {
        if (!this.user) return false;
        if (!Array.isArray(allowedRoles)) {
            allowedRoles = [allowedRoles];
        }
        return allowedRoles.includes(this.user.role);
    }

    getUser() {
        return this.user;
    }
}

export default Auth;


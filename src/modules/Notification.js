// modules/Notification.js

class Notification {
    constructor() {
        this.API_URL = '/api';
        this.notifications = [];
        this.unreadCount = 0;
        this.initialLoad = true;
        this.init();
    }

    async init() {
        await this.loadNotifications();
        this.render();
        this.setupAutoRefresh();
    }

    async loadNotifications() {
        try {
            const previousIds = new Set(this.notifications.map(n => n.id));
            const response = await fetch(`${this.API_URL}?action=notification.index`);
            const result = await response.json();
            
            if (result.success) {
                this.notifications = result.data || [];
                this.unreadCount = this.notifications.filter(n => !n.is_read).length;

                // Показываем всплывающие уведомления только для новых непрочитанных
                if (!this.initialLoad) {
                    const newUnread = this.notifications.filter(
                        n => !n.is_read && !previousIds.has(n.id)
                    );
                    if (newUnread.length > 0) {
                        this.showToasts(newUnread);
                    }
                }

                // Обновляем счетчик в навигации
                this.updateUnreadBadge();

                this.initialLoad = false;
            }
        } catch (error) {
            console.error('Ошибка загрузки уведомлений:', error);
        }
    }

    render() {
        const container = document.querySelector('[data-notifications-container]');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<p>Нет уведомлений</p>';
            return;
        }

        container.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" data-notification-id="${notification.id}">
                <div class="notification-header">
                    <h4>${notification.message}</h4>
                </div>
                <div class="notification-footer">
                    <span class="notification-date">${this.formatDate(notification.created_at)}</span>
                    ${!notification.is_read ? '<button class="mark-read-btn" data-action="mark-read">Отметить прочитанным</button>' : ''}
                    <button class="delete-notification-btn" data-action="delete">Удалить</button>
                </div>
            </div>
        `).join('');

        // Добавляем обработчики событий
        container.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const notificationId = e.target.closest('.notification-item').dataset.notificationId;
                const action = e.target.dataset.action;
                
                if (action === 'mark-read') {
                    this.markAsRead(notificationId);
                } else if (action === 'delete') {
                    this.deleteNotification(notificationId);
                }
            });
        });

        // Кнопка "Отметить все прочитанными"
        const markAllBtn = document.querySelector('[data-mark-all-read]');
        if (markAllBtn) {
            markAllBtn.onclick = () => this.markAllAsRead();
        }
    }

    updateUnreadBadge() {
        const badges = document.querySelectorAll('[data-unread-count]');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = String(this.unreadCount);
                badge.classList.remove('hidden');
            } else {
                badge.textContent = '';
                badge.classList.add('hidden');
            }
        });
    }

    showToasts(notifications) {
        const container = this.getToastContainer();
        if (!container) return;

        notifications.forEach(notification => {
            const toast = document.createElement('div');
            toast.className = `toast toast--${notification.type || 'info'}`;
            toast.innerHTML = `
                <div class="toast__title">${notification.title}</div>
                <div class="toast__message">${notification.message}</div>
            `;
            container.appendChild(toast);

            // Авто-скрытие
            setTimeout(() => {
                toast.classList.add('toast--hide');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        });
    }

    getToastContainer() {
        let container = document.querySelector('[data-toast-container]');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.setAttribute('data-toast-container', '');
            document.body.appendChild(container);
        }
        return container;
    }

    async markAsRead(id) {
        try {
            const response = await fetch(`${this.API_URL}?action=notification.markAsRead&id=${id}`, {
                method: 'POST'
            });
            const result = await response.json();
            
            if (result.success) {
                await this.loadNotifications();
                this.render();
            }
        } catch (error) {
            console.error('Ошибка отметки уведомления:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch(`${this.API_URL}?action=notification.markAllAsRead`, {
                method: 'POST'
            });
            const result = await response.json();
            
            if (result.success) {
                await this.loadNotifications();
                this.render();
            }
        } catch (error) {
            console.error('Ошибка отметки всех уведомлений:', error);
        }
    }

    async deleteNotification(id) {
        try {
            const response = await fetch(`${this.API_URL}?action=notification.delete&id=${id}`, {
                method: 'POST'
            });
            const result = await response.json();
            
            if (result.success) {
                await this.loadNotifications();
                this.render();
            }
        } catch (error) {
            console.error('Ошибка удаления уведомления:', error);
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    setupAutoRefresh() {
        // Обновляем уведомления каждые 30 секунд
        setInterval(() => {
            this.loadNotifications().then(() => this.render());
        }, 30000);
    }

    getUnreadCount() {
        return this.unreadCount;
    }
}

export default Notification;


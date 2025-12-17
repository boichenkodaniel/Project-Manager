// modules/Notification.js

class Notification {
    constructor() {
        this.API_URL = '/api';
        this.notifications = [];
        this.unreadCount = 0;
        this.init();
    }

    async init() {
        await this.loadNotifications();
        this.render();
        this.setupAutoRefresh();
    }

    async loadNotifications() {
        try {
            const response = await fetch(`${this.API_URL}?action=notification.index`);
            const result = await response.json();
            
            if (result.success) {
                this.notifications = result.data || [];
                this.unreadCount = this.notifications.filter(n => !n.is_read).length;
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
                    <h4>${notification.title}</h4>
                    <span class="notification-type ${notification.type}">${notification.type}</span>
                </div>
                <p>${notification.message}</p>
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


class Task {
    root = {
        dataTemplate: "[data-task-template]",
        dataName: "[data-task-name]",
        dataDescription: "[data-task-description]",
        dataByWorker: "[data-task-by-worker]",
        dataToWorker: "[data-task-to-worker]",
        dataTaskContainer: "[data-task-container]",
        dataTotal: "[data-total-tasks]",
        dataTaskContainerInDashboard: "[data-task-container-in-dashboard]", // Добавляем для дашборда
    };

    taskList = [];

    constructor() {
        // Конструктор теперь просто инициализирует, загрузка будет через явные методы
    }

    async loadAndRender() {
        try {
            const tasks = await this.fetchTasks();
            this.taskList = this.mapTasksToView(tasks);

            this.render();
            this.renderInfoToDashboard();
        } catch (e) {
            console.error('Ошибка загрузки задач:', e);
        }
    }

    async loadRecentCompletedTasks() {
        try {
            const tasks = await this.fetchTasks('status=completed&limit=5'); // Ограничение на 5 последних завершенных
            this.taskList = this.mapTasksToView(tasks);
            const container = document.querySelector(this.root.dataTaskContainerInDashboard);
            if (container) {
                container.innerHTML = '';
                this.taskList.forEach(task => {
                    const taskElement = this.createTaskLayout(task, document.querySelector(this.root.dataTemplate));
                    container.appendChild(taskElement);
                });
            }
            // renderInfoToDashboard относится к общему количеству задач, может не подходить для дашборда
        } catch (e) {
            console.error('Ошибка загрузки последних завершенных задач:', e);
        }
    }

    async fetchTasks(queryParams = '') {
        try {
            const url = `/api?action=task.index${queryParams ? '&' + queryParams : ''}`;
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${(await response.text()) || 'Unknown error'}`);
            }
            const result = await response.json();

            if (!result.success) {
                console.error('Ошибка API задач:', result.error);

                return [];
            }

            return result.data || [];
        } catch (error) {
            console.error('Ошибка загрузки задач:', error);
            return [];
        }
    }


    mapTasksToView(tasks) {
        return tasks.map(task => {
            // Извлекаем ID, обрабатывая случаи когда они могут быть null
            const byWorkerId = task.TaskBy || task.TaskByID || task.taskby || null;
            const toWorkerId = task.TaskTo || task.TaskToID || task.taskto || null;
            
            return {
                id: task.ID || task.id,
                name: task.Title || task.title || '—',
                description: task.Description || task.description || '—',
                byWorker: task.creator_fullname || task.creator_fullname || '—',
                byWorkerId: byWorkerId ? String(byWorkerId) : '',       
                toWorker: task.executor_fullname || task.executor_fullname || '—',
                toWorkerId: toWorkerId ? String(toWorkerId) : '',     
                projectId: task.ProjectID || task.projectid,
                projectTitle: task.project_title || '—',
                status: task.Status || task.status || 'К выполнению',
                startDate: task.StartDate || task.startdate || null,
                endDate: task.EndDate || task.enddate || null
            };
        });
    }


    render() {
        const container = document.querySelector(this.root.dataTaskContainer);
        const template = document.querySelector(this.root.dataTemplate);

        if (!container || !template) return;

        container.innerHTML = '';

        this.taskList.forEach(task => {
            const taskElement = this.createTaskLayout(task, template);
            container.appendChild(taskElement);
        });
    }

    renderInfoToDashboard() {
        const totalEl = document.querySelector(this.root.dataTotal);
        if (totalEl) {
            totalEl.textContent = String(this.taskList.length);
        }
    }

    createTaskLayout(task, template) {
        const fragment = document.importNode(template.content, true);
        const li = fragment.querySelector('.activity-item');

        // Обрабатываем ID, преобразуя в строки
        const byWorkerId = task.byWorkerId ? String(task.byWorkerId) : '';
        const toWorkerId = task.toWorkerId ? String(task.toWorkerId) : '';
        const projectId = task.projectId ? String(task.projectId) : '';

        li.dataset.id = task.id;
        li.dataset.type = 'task';
        // Сохраняем ID в dataset для быстрого доступа
        li.dataset.taskBy = byWorkerId;
        li.dataset.taskTo = toWorkerId;
        li.dataset.projectId = projectId;

        fragment.querySelector(this.root.dataName).textContent = task.name;
        fragment.querySelector(this.root.dataDescription).textContent = task.description;
        fragment.querySelector(this.root.dataByWorker).textContent = task.byWorker;
        fragment.querySelector(this.root.dataToWorker).textContent = task.toWorker;

        // === Добавляем скрытые поля для редактирования ===
        const projectEl = fragment.querySelector('[data-task-project]');
        if (projectEl) {
            projectEl.dataset.projectId = projectId;
        }
        
        // Находим или создаем скрытые элементы для taskBy и taskTo
        let taskByEl = fragment.querySelector('[data-task-by]');
        if (!taskByEl) {
            taskByEl = document.createElement('span');
            taskByEl.setAttribute('data-task-by', '');
            taskByEl.style.display = 'none';
            li.appendChild(taskByEl);
        }
        // Сохраняем ID в dataset.userId для совместимости с getEntityData
        taskByEl.dataset.userId = byWorkerId;
        
        let taskToEl = fragment.querySelector('[data-task-to]');
        if (!taskToEl) {
            taskToEl = document.createElement('span');
            taskToEl.setAttribute('data-task-to', '');
            taskToEl.style.display = 'none';
            li.appendChild(taskToEl);
        }
        // Сохраняем ID в dataset.userId для совместимости с getEntityData
        taskToEl.dataset.userId = toWorkerId;
        
        console.log('Task data saved to DOM:', {
            id: task.id,
            byWorkerId,
            toWorkerId,
            projectId,
            taskByElUserId: taskByEl.dataset.userId,
            taskToElUserId: taskToEl.dataset.userId
        });

        const statusEl = fragment.querySelector('[data-task-status]');
        if (statusEl) statusEl.textContent = task.status ?? 'К выполнению';

        const startDateEl = fragment.querySelector('[data-task-start-date]');
        if (startDateEl) startDateEl.value = task.startDate ? task.startDate.split('T')[0] : '';

        const endDateEl = fragment.querySelector('[data-task-end-date]');
        if (endDateEl) endDateEl.value = task.endDate ? task.endDate.split('T')[0] : '';


        return fragment;
    }




    async createTask(data) {
        const res = await fetch('/api?action=task.create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (!result.success) throw new Error(result.error);

        this.taskList.push(this.mapTasksToView([result.data])[0]);
        this.render();
        this.renderInfoToDashboard();
    }

    async updateTask(id, data) {
        const res = await fetch(`/api?action=task.update&id=${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (!result.success) throw new Error(result.error);

        const index = this.taskList.findIndex(t => t.id == id);
        if (index !== -1) {
            this.taskList[index] = { ...this.taskList[index], ...data };
            this.render();
        }
    }

    async deleteTask(id) {
        if (!confirm('Удалить задачу?')) return;

        const res = await fetch(`/api?action=task.delete&id=${id}`);
        const result = await res.json();

        if (!result.success) throw new Error(result.error);

        this.taskList = this.taskList.filter(t => t.id != id);
        this.render();
        this.renderInfoToDashboard();
    }



}

export default Task;

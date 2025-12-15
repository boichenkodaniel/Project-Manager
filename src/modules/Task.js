class Task {
    root = {
        dataTemplate: "[data-task-template]",
        dataName: "[data-task-name]",
        dataDescription: "[data-task-description]",
        dataByWorker: "[data-task-by-worker]",
        dataToWorker: "[data-task-to-worker]",
        dataTaskContainer: "[data-task-container]",
        dataTotal: "[data-total-tasks]",
    };

    taskList = [];

    constructor() {
        this.loadAndRender();
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

    async fetchTasks() {
        const response = await fetch('/api?action=task.index');
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'Ошибка API');
        }

        return result.data || [];
    }


    mapTasksToView(tasks) {
        return tasks.map(task => ({
            id: task.id,
            name: task.title,
            description: task.description || '—',
            byWorker: task.creator_fullname || '—',
            byWorkerId: task.creator_id ?? '',       // <-- добавляем ID
            toWorker: task.executor_fullname || '—',
            toWorkerId: task.executor_id ?? '',     // <-- добавляем ID
            projectId: task.projectid,
            projectTitle: task.project_title || '—',
            status: task.status || 'К выполнению',
            startDate: task.startdate || null,
            endDate: task.enddate || null
        }));
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

        li.dataset.id = task.id;
        li.dataset.type = 'task';
        li.dataset.taskBy = task.byWorkerId ?? '';
        li.dataset.taskTo = task.toWorkerId ?? '';
        li.dataset.projectId = task.projectId ?? '';

        fragment.querySelector(this.root.dataName).textContent = task.name;
        fragment.querySelector(this.root.dataDescription).textContent = task.description;
        fragment.querySelector(this.root.dataByWorker).textContent = task.byWorker;
        fragment.querySelector(this.root.dataToWorker).textContent = task.toWorker;

        // === Добавляем скрытые поля для редактирования ===
        const projectEl = fragment.querySelector('[data-task-project]');
        if (projectEl) {
            projectEl.dataset.projectId = task.projectId ?? '';
            projectEl.dataset.taskTo = task.toWorkerId ?? '';
            projectEl.dataset.taskBy = task.byWorkerId ?? '';
        }

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

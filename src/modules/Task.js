class Task {
    root = {
        dataTemplate: "[data-task-template]",
        dataName: "[data-task-name]",
        dataDescription: "[data-task-description]",
        dataByWorker: "[data-task-by-worker]",
        dataToWorker: "[data-task-to-worker]",
        dataTaskContainer: "[data-task-container]",
        dataTaskContainerInDashboard: "[data-task-container-in-dashboard]",
        dataTemplateInDashboard: "[data-task-template-in-dashboard]",
        dataTotal: "[data-total-tasks]",
    };

    taskList = [
        {
            name: 'New Task Added',
            description: 'New Task added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New Task Added',
            description: 'New Task added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New Task Added',
            description: 'New Task added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New Task Added',
            description: 'New Task added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New Task Added',
            description: 'New Task added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
    ];

    constructor() {
        this.render();
        this.renderInDashboard();
    }

    render() {
        const taskContainer = document.querySelector(this.root.dataTaskContainer);
        const template = document.querySelector(this.root.dataTemplate);

        // Проверяем существование элементов
        if (!taskContainer || !template) return;

        this.taskList.forEach(task => {
            const taskElement = this.createTaskLayout(task, template);
            taskContainer.appendChild(taskElement);
        });

    }

    renderInDashboard() {
        const taskContainer = document.querySelector(this.root.dataTaskContainerInDashboard);
        const template = document.querySelector(this.root.dataTemplateInDashboard);

        // Проверяем существование элементов
        if (!taskContainer || !template) return;

        this.taskList.slice(-3).forEach(task => {
            const taskElement = this.createTaskLayout(task, template);
            taskContainer.appendChild(taskElement);
        });

        const totalElement = document.querySelector(this.root.dataTotal);
        if (!totalElement || !totalElement.textContent) return;
        totalElement.textContent = String(this.taskList.length);
    }

    createTaskLayout(task, template) {
        const { name, description, toWorker, byWorker } = task;
        const taskElement = document.importNode(template.content, true);

        taskElement.querySelector(this.root.dataName).textContent = name;
        taskElement.querySelector(this.root.dataDescription).textContent = description;
        taskElement.querySelector(this.root.dataToWorker).textContent = toWorker;
        taskElement.querySelector(this.root.dataByWorker).textContent = byWorker;

        return taskElement;
    }
}

export default Task;
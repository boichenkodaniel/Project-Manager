class Task {
    root = {
        dataTemplate: "[data-task-template]",
        dataName: "[data-task-name]",
        dataDescription: "[data-task-description]",
        dataByWorker: "[data-task-by-worker]",
        dataToWorker: "[data-task-to-worker]",
        dataTaskContainer: "[data-task-container]",
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
    }

    render() {
        const taskContainer = document.querySelector(this.root.dataTaskContainer);
        const template = document.querySelector(this.root.dataTemplate);

        if (!taskContainer || !template) {
            console.error('Missing required DOM elements');
            return;
        }

        this.taskList.forEach(task => {
            const taskElement = this.createTaskLayout(task, template);
            taskContainer.appendChild(taskElement);
        });
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
class Project {
    root = {
        dataTemplate: "[data-project-template]",
        dataName: "[data-project-name]",
        dataDescription: "[data-project-description]",
        dataActive: "[data-project-active]",
        dataActiveIcon: "[data-project-active-icon]",
        dataDate: "[data-project-date]",
        dataCountTask: "[data-project-count-task]",
        dataAssigned: "[data-project-assigned]",
        dataProjectContainer: "[data-project-container]",
        dataTotal: "[data-total-projects]",
    };

    projectList = [
        {
            name: 'React JS 01',
            description: 'Project Regarding creating a dash in react js using Next Js as backend and APIs.',
            active: 'Inactive',
            date: '1 Jun 25',
            countTask: '12 Tasks',
            assigned: '6 Assigned',
        },
        {
            name: 'React JS 01',
            description: 'Project Regarding creating a dash in react js using Next Js as backend and APIs.',
            active: 'Inactive',
            date: '1 Jun 25',
            countTask: '12 Tasks',
            assigned: '6 Assigned',
        },
        {
            name: 'React JS 01',
            description: 'Project Regarding creating a dash in react js using Next Js as backend and APIs.',
            active: 'Active',
            date: '1 Jun 25',
            countTask: '12 Tasks',
            assigned: '6 Assigned',
        },
    ];

    constructor() {
        this.render();
        this.renderInfoToDashboard()
    }

    render() {
        const projectContainer = document.querySelector(this.root.dataProjectContainer);
        const template = document.querySelector(this.root.dataTemplate);

        if (!projectContainer || !template) return;

        this.projectList.forEach(project => {
            const projectElement = this.createProjectLayout(project, template);
            projectContainer.appendChild(projectElement);
        });

    }

    renderInfoToDashboard() {
        const totalElement = document.querySelector(this.root.dataTotal);
        if (!totalElement || !totalElement.textContent) return;
        totalElement.textContent = String(this.projectList.length);
    }

    createProjectLayout(project, template) {
        const { name, description, active, date, countTask, assigned } = project;
        const projectElement = document.importNode(template.content, true);

        projectElement.querySelector(this.root.dataName).textContent = name;
        projectElement.querySelector(this.root.dataDescription).textContent = description;
        projectElement.querySelector(this.root.dataActive).textContent = active;
        if (projectElement.querySelector(this.root.dataActive).textContent === 'Inactive') {
            projectElement.querySelector(this.root.dataActiveIcon).src = 'src/assets/icons/inactive.svg';
        }
        else {
            projectElement.querySelector(this.root.dataActiveIcon).src = 'src/assets/icons/active.svg';
        }
        projectElement.querySelector(this.root.dataDate).textContent = date;
        projectElement.querySelector(this.root.dataCountTask).textContent = countTask;
        projectElement.querySelector(this.root.dataAssigned).textContent = assigned;

        return projectElement;
    }
}

export default Project;
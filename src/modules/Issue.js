class Issue {
    root = {
        dataTemplate: "[data-issue-template]",
        dataName: "[data-issue-name]",
        dataDescription: "[data-issue-description]",
        dataByWorker: "[data-issue-by-worker]",
        dataToWorker: "[data-issue-to-worker]",
        dataIssueContainer: "[data-issue-container]",
    };

    issueList = [
        {
            name: 'New issue Added',
            description: 'New issue added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New issue Added',
            description: 'New issue added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New issue Added',
            description: 'New issue added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New issue Added',
            description: 'New issue added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
        {
            name: 'New issue Added',
            description: 'New issue added for Project 03 for the deadline 14 Jun 25',
            byWorker: 'By Jason Stew',
            toWorker: 'To John Doe',
        },
    ];

    constructor() {
        this.render();
    }

    render() {
        const issueContainer = document.querySelector(this.root.dataIssueContainer);
        const template = document.querySelector(this.root.dataTemplate);

        if (!issueContainer || !template) {
            console.error('Missing required DOM elements');
            return;
        }

        this.issueList.forEach(issue => {
            const issueElement = this.createIssueLayout(issue, template);
            issueContainer.appendChild(issueElement);
        });
    }

    createIssueLayout(issue, template) {
        const { name, description, toWorker, byWorker } = issue;
        const issueElement = document.importNode(template.content, true);

        issueElement.querySelector(this.root.dataName).textContent = name;
        issueElement.querySelector(this.root.dataDescription).textContent = description;
        issueElement.querySelector(this.root.dataToWorker).textContent = toWorker;
        issueElement.querySelector(this.root.dataByWorker).textContent = byWorker;

        return issueElement;
    }
}

export default Issue;
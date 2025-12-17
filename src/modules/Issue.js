class Issue {
    root = {
        dataTemplate: "[data-issue-template]",
        dataName: "[data-issue-name]",
        dataDescription: "[data-issue-description]",
        dataByWorker: "[data-issue-by-worker]",
        dataToWorker: "[data-issue-to-worker]",
        dataIssueContainer: "[data-issue-container]",
        dataIssueContainerInDashboard: "[data-issue-container-in-dashboard]",
        dataTemplateInDashboard: "[data-issue-template-in-dashboard]",
    };

    issueList = [];

    constructor() {
        // Конструктор теперь просто инициализирует, загрузка будет через явные методы
    }

    async loadAndRender() {
        try {
            const issues = await this.fetchIssues();
            this.issueList = this.mapIssuesToView(issues);
            const container = document.querySelector(this.root.dataIssueContainer);
            const template = document.querySelector(this.root.dataTemplate);
            if (container && template) {
                container.innerHTML = '';
                this.issueList.forEach(issue => {
                    const issueElement = this.createIssueLayout(issue, template);
                    container.appendChild(issueElement);
                });
            }
        } catch (e) {
            console.error('Ошибка загрузки Issues:', e);
        }
    }

    async loadTopIssues() {
        try {
            const issues = await this.fetchIssues('limit=3'); // Ограничение на 3 последних issue
            this.issueList = this.mapIssuesToView(issues);
            const container = document.querySelector(this.root.dataIssueContainerInDashboard);
            const template = document.querySelector(this.root.dataTemplateInDashboard);
            if (container && template) {
                container.innerHTML = '';
                this.issueList.forEach(issue => {
                    const issueElement = this.createIssueLayout(issue, template);
                    container.appendChild(issueElement);
                });
            }
        } catch (e) {
            console.error('Ошибка загрузки последних Issues:', e);
        }
    }

    async fetchIssues(queryParams = '') {
        try {
            const url = `/api?action=issue.index${queryParams ? '&' + queryParams : ''}`;
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${(await response.text()) || 'Unknown error'}`);
            }
            const result = await response.json();

            if (!result.success) {
                console.error('Ошибка API Issues:', result.error);
                return [];
            }

            return result.data || [];
        } catch (error) {
            console.error('Ошибка загрузки Issues:', error);
            return [];
        }
    }

    mapIssuesToView(issues) {
        return issues.map(issue => ({
            id: issue.ID || issue.id,
            name: issue.Title || issue.title || '—',
            description: issue.Description || issue.description || '—',
            byWorker: issue.creator_fullname || '—',
            toWorker: issue.executor_fullname || '—',
            projectId: issue.ProjectID || issue.projectid,
            projectTitle: issue.project_title || '—',
            status: issue.Status || issue.status || 'open',
            createdAt: issue.CreationDate || issue.created_at || null,
        }));
    }

    createIssueLayout(issue, template) {
        const fragment = document.importNode(template.content, true);
        const li = fragment.querySelector('.activity-item');

        li.dataset.id = issue.id;
        li.dataset.type = 'issue';
        li.dataset.projectId = issue.projectId ?? '';

        fragment.querySelector(this.root.dataName).textContent = issue.name;
        fragment.querySelector(this.root.dataDescription).textContent = issue.description;
        fragment.querySelector(this.root.dataToWorker).textContent = issue.toWorker;
        fragment.querySelector(this.root.dataByWorker).textContent = issue.byWorker;

        return fragment;
    }
}

export default Issue;
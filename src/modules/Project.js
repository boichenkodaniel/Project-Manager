class Project {
  root = {
    dataTemplate: '[data-project-template]',
    dataContainer: '[data-project-container]',
    dataTotal: '[data-total-projects]'
  };

  projectList = [];

  constructor() {
    this.loadAndRender();
  }

  async loadAndRender() {
    try {
      const projects = await this.fetchProjects();
      this.projectList = projects;
      this.render();
      this.renderInfoToDashboard();
    } catch (e) {
      console.error('Ошибка загрузки проектов:', e);
    }
  }

  async fetchProjects() {
    try {
      const res = await fetch('/api?action=projects.index');
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      const result = await res.json();

      if (!result.success) {
        console.error('Ошибка API проектов:', result.error);
        return [];
      }

      return result.data || [];
    } catch (error) {
      console.error('Ошибка загрузки проектов:', error);
      return [];
    }
  }

  render() {
    const container = document.querySelector(this.root.dataContainer);
    const template = document.querySelector(this.root.dataTemplate);
    if (!container || !template) return;

    container.innerHTML = '';

    this.projectList.forEach(project => {
      const fragment = this.createProjectLayout(project, template);
      container.appendChild(fragment);
    });
  }

  renderInfoToDashboard() {
    const el = document.querySelector(this.root.dataTotal);
    if (el) el.textContent = String(this.projectList.length);
  }

  createProjectLayout(project, template) {
    const fragment = document.importNode(template.content, true);
    const item = fragment.querySelector('.project__item');

    item.dataset.id = project.id;
    item.dataset.type = 'project';

    item.querySelector('[data-project-name]').textContent =
      project.title || '—';

    item.querySelector('[data-project-description]').textContent =
      project.detaileddescription || '—';

    item.querySelector('[data-project-active]').textContent =
      project.status || '—';

    item.querySelector('[data-project-date]').textContent =
      project.startdate ? project.startdate.split('T')[0] : '—';

    const assignedEl = item.querySelector('[data-project-assigned]');
    assignedEl.textContent = project.client_fullname || '—';
    assignedEl.dataset.userId = project.clientid || '';

    return fragment;
  }

  async deleteProject(id) {
    if (!confirm('Удалить проект?')) return;

    const res = await fetch(`/api?action=projects.delete&id=${id}`, {
      method: 'POST'
    });

    const result = await res.json();
    if (!result.success) throw new Error(result.error);

    this.projectList = this.projectList.filter(p => p.id != id);
    this.render();
    this.renderInfoToDashboard();
  }
}

export default Project;

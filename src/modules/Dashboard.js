class Dashboard {
  constructor() {
    this.currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    this.loadProjectsWithTasks();
  }

  async loadProjectsWithTasks() {
    try {
      // Получаем проекты пользователя
      const projectsRes = await fetch('/api?action=projects.index');
      const projectsResult = await projectsRes.json();
      
      if (!projectsResult.success) {
        console.error('Ошибка загрузки проектов:', projectsResult.error);
        return;
      }

      const projects = projectsResult.data || [];
      
      // Для каждого проекта получаем задачи
      const projectsWithTasks = await Promise.all(
        projects.map(async (project) => {
          const tasksRes = await fetch(`/api?action=task.byProject&id=${project.id}`);
          const tasksResult = await tasksRes.json();
          return {
            ...project,
            tasks: tasksResult.success ? (tasksResult.data || []) : []
          };
        })
      );

      this.render(projectsWithTasks);
    } catch (error) {
      console.error('Ошибка загрузки Dashboard:', error);
    }
  }

  render(projectsWithTasks) {
    const container = document.querySelector('[data-dashboard-projects]');
    if (!container) return;

    const projectTemplate = document.querySelector('[data-project-dashboard-template]');
    const taskTemplate = document.querySelector('[data-task-dashboard-template]');
    
    if (!projectTemplate || !taskTemplate) {
      console.error('Шаблоны не найдены');
      return;
    }

    container.innerHTML = '';

    projectsWithTasks.forEach(project => {
      const projectFragment = document.importNode(projectTemplate.content, true);
      const projectEl = projectFragment.querySelector('.dashboard-project');
      
      projectEl.dataset.projectId = project.id;
      projectEl.querySelector('[data-project-title]').textContent = project.title || '—';
      projectEl.querySelector('[data-project-status]').textContent = project.status || '—';
      
      const tasksContainer = projectEl.querySelector('[data-project-tasks]');
      
      if (project.tasks && project.tasks.length > 0) {
        project.tasks.forEach(task => {
          const taskFragment = document.importNode(taskTemplate.content, true);
          const taskEl = taskFragment.querySelector('.dashboard-task');
          
          taskEl.dataset.taskId = task.ID || task.id;
          taskEl.querySelector('[data-task-title]').textContent = task.Title || task.title || '—';
          taskEl.querySelector('[data-task-description]').textContent = task.Description || task.description || '—';
          taskEl.querySelector('[data-task-status]').textContent = task.Status || task.status || '—';
          
          // Добавляем кнопки действий в зависимости от роли
          const actionsContainer = taskEl.querySelector('[data-task-actions]');
          this.addTaskActions(actionsContainer, task);
          
          tasksContainer.appendChild(taskFragment);
        });
      } else {
        const emptyTask = document.createElement('li');
        emptyTask.className = 'dashboard-task dashboard-task--empty';
        emptyTask.textContent = 'Нет задач';
        tasksContainer.appendChild(emptyTask);
      }
      
      container.appendChild(projectFragment);
    });
  }

  addTaskActions(container, task) {
    const userRole = this.currentUser.role;
    const taskStatus = task.Status || task.status;
    const executorId = task.TaskTo || task.taskto || task.executorid;
    const projectId = task.ProjectID || task.projectid;
    const taskId = task.ID || task.id;
    
    // Сравниваем ID как числа для надежности
    const isExecutor = executorId && (String(executorId) === String(this.currentUser.id) || Number(executorId) === Number(this.currentUser.id));
    
    console.log('Task action check:', {
      userRole,
      taskStatus,
      executorId,
      currentUserId: this.currentUser.id,
      isExecutor,
      taskId,
      task
    });
    
    // Получаем проект для проверки, является ли пользователь руководителем
    fetch(`/api?action=projects.get&id=${projectId}`)
      .then(res => res.json())
      .then(result => {
        if (!result.success) {
          // Если не удалось получить проект, все равно показываем действия для исполнителя
          if (isExecutor && (userRole === 'Исполнитель' || userRole === 'employee' || userRole === 'Администратор' || userRole === 'admin')) {
            container.innerHTML = '';
            if (taskStatus === 'К выполнению') {
              const takeBtn = document.createElement('button');
              takeBtn.className = 'dashboard-task__action-btn';
              takeBtn.textContent = 'Взять в работу';
              takeBtn.addEventListener('click', () => this.updateTaskStatus(taskId, 'В работе'));
              container.appendChild(takeBtn);
            } else if (taskStatus === 'В работе') {
              const reviewBtn = document.createElement('button');
              reviewBtn.className = 'dashboard-task__action-btn';
              reviewBtn.textContent = 'Отправить на проверку';
              reviewBtn.addEventListener('click', () => this.updateTaskStatus(taskId, 'На проверке'));
              container.appendChild(reviewBtn);
            }
          }
          return;
        }
        
        const project = result.data;
        const isManager = project.managerid == this.currentUser.id;
        const isAdmin = userRole === 'Администратор';
        
        container.innerHTML = '';
        
        // Исполнитель может взять задачу в работу или отправить на проверку
        // Показываем кнопку если: задача назначена исполнителю ИЛИ задача не назначена никому (статус "К выполнению")
        const canTakeTask = isExecutor || (!executorId && taskStatus === 'К выполнению');
        
        if ((canTakeTask || isExecutor) && (userRole === 'Исполнитель' || userRole === 'employee' || isAdmin)) {
          if (taskStatus === 'К выполнению') {
            const takeBtn = document.createElement('button');
            takeBtn.className = 'dashboard-task__action-btn';
            takeBtn.textContent = 'Взять в работу';
            takeBtn.addEventListener('click', () => {
              console.log('Taking task to work:', { taskId, executorId, currentUserId: this.currentUser.id });
              this.updateTaskStatus(taskId, 'В работе');
            });
            container.appendChild(takeBtn);
          } else if (taskStatus === 'В работе' && isExecutor) {
            const reviewBtn = document.createElement('button');
            reviewBtn.className = 'dashboard-task__action-btn';
            reviewBtn.textContent = 'Отправить на проверку';
            reviewBtn.addEventListener('click', () => {
              console.log('Sending task for review:', { taskId, executorId, currentUserId: this.currentUser.id });
              this.updateTaskStatus(taskId, 'На проверке');
            });
            container.appendChild(reviewBtn);
          }
        }
        
        // Руководитель проекта может принять задачу (изменить статус на "Выполнена")
        // Администратор не может принимать задачи, только руководитель
        if (isManager && !isAdmin && taskStatus === 'На проверке') {
          const acceptBtn = document.createElement('button');
          acceptBtn.className = 'dashboard-task__action-btn dashboard-task__action-btn--accept';
          acceptBtn.textContent = 'Принять задачу';
          acceptBtn.addEventListener('click', () => this.updateTaskStatus(taskId, 'Выполнена'));
          container.appendChild(acceptBtn);
        }
      })
      .catch(err => {
        console.error('Ошибка получения проекта:', err);
        // Показываем действия для исполнителя даже при ошибке
        if (isExecutor && (userRole === 'Исполнитель' || userRole === 'employee' || userRole === 'Администратор' || userRole === 'admin')) {
          container.innerHTML = '';
          if (taskStatus === 'К выполнению') {
            const takeBtn = document.createElement('button');
            takeBtn.className = 'dashboard-task__action-btn';
            takeBtn.textContent = 'Взять в работу';
            takeBtn.addEventListener('click', () => this.updateTaskStatus(taskId, 'В работе'));
            container.appendChild(takeBtn);
          } else if (taskStatus === 'В работе') {
            const reviewBtn = document.createElement('button');
            reviewBtn.className = 'dashboard-task__action-btn';
            reviewBtn.textContent = 'Отправить на проверку';
            reviewBtn.addEventListener('click', () => this.updateTaskStatus(taskId, 'На проверке'));
            container.appendChild(reviewBtn);
          }
        }
      });
  }

  async updateTaskStatus(taskId, newStatus) {
    try {
      const requestBody = JSON.stringify({ Status: newStatus });
      console.log('Updating task status:', { taskId, newStatus, requestBody });
      
      const response = await fetch(`/api?action=task.update&id=${taskId}`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: requestBody
      });

      const result = await response.json();
      console.log('Update task response:', result);
      
      if (!result.success) {
        throw new Error(result.error || 'Ошибка обновления статуса');
      }

      // Перезагружаем Dashboard для обновления статуса проекта
      await this.loadProjectsWithTasks();
    } catch (error) {
      console.error('Ошибка обновления статуса задачи:', error);
      alert('Не удалось обновить статус задачи: ' + error.message);
    }
  }
}

export default Dashboard;


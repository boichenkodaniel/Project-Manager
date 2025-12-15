class User {
  root = {
    dataTemplate: "[data-user-template]",
    dataName: "[data-user-name]",
    dataCreated: "[data-user-created]",
    dataAdminContainer: "[data-admin-container]",
    dataEmployeeContainer: "[data-employee-container]",
    dataClientContainer: "[data-client-container]",
    dataTotal: "[data-total-users]",
  };

  userList = []; // будет заполнен из API

  constructor() {
    this.loadAndRender();
  }

  // Основной метод: загрузка + рендер
  async loadAndRender() {
    try {
      const users = await this.fetchUsers();
      this.userList = this.mapUsersToView(users);
      this.render();
      this.renderInfoToDashboard();
    } catch (error) {
      console.error('Ошибка загрузки пользователей:', error);
      this.showError(error.message || 'Не удалось загрузить пользователей');
    }
  }

  // Загрузка из бэкенда
  async fetchUsers() {
    const response = await fetch('/api?action=user.index');
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    const result = await response.json();
    if (!result.success) {
      throw new Error(result.error || 'Ошибка API');
    }
    return result.data || [];
  }

  // Преобразование из БД → view-модель (type: admin/employee/client)
  mapUsersToView(users) {
    return users.map(user => {
      let type = 'employee'; // по умолчанию
      let role = 'employee'; // стандартное значение для формы

      if (['Администратор'].includes(user.role)) {
        type = 'admin';
        role = 'admin';
      } else if (['Руководитель', 'Исполнитель'].includes(user.role)) {
        type = 'employee';
        role = 'employee';
      } else if (user.role === 'Клиент') {
        type = 'client';
        role = 'client';
      }

      let created = 'Created: —';
      if (user.created_at) {
        const date = new Date(user.created_at);
        const day = String(date.getDate()).padStart(2, '0');
        const month = date.toLocaleString('en-GB', { month: 'short' });
        const year = String(date.getFullYear()).slice(-2);
        created = `Created: ${day} ${month} ${year}`;
      }

      return {
        id: user.id,
        name: user.fullname || user.login || '—',
        email: user.email,
        role,  // <- стандартное значение для формы
        created,
        type,
        login:user.login
      };
    });
  }

  render() {
    const adminContainer = document.querySelector(this.root.dataAdminContainer);
    const employeeContainer = document.querySelector(this.root.dataEmployeeContainer);
    const clientContainer = document.querySelector(this.root.dataClientContainer);
    const template = document.querySelector(this.root.dataTemplate);

    if (!adminContainer || !employeeContainer || !clientContainer || !template) {
      console.warn('Один или несколько элементов не найдены');
      return;
    }

    // Очищаем
    adminContainer.innerHTML = '';
    employeeContainer.innerHTML = '';
    clientContainer.innerHTML = '';

    // Распределяем
    this.userList.forEach(user => {
      const userElement = this.createUserLayout(user, template);
      switch (user.type) {
        case 'admin':
          adminContainer.appendChild(userElement);
          break;
        case 'employee':
          employeeContainer.appendChild(userElement);
          break;
        case 'client':
          clientContainer.appendChild(userElement);
          break;
        default:
          console.warn(`Неизвестный тип: ${user.type}`);
      }
    });

    this.checkEmptyContainers(adminContainer, employeeContainer, clientContainer);


  }

  renderInfoToDashboard() {
    const totalElement = document.querySelector(this.root.dataTotal);
    if (totalElement) {
      totalElement.textContent = String(this.userList.length);
    }
  }

  createUserLayout(user, template) {
    const { id, name, created, role, email } = user;

    const fragment = document.importNode(template.content, true);
    const li = fragment.querySelector('.user-item');

    li.dataset.id = id;
    li.dataset.type = 'user';

    // Опционально сохраняем email и роль для удобства
    li.dataset.email = email;
    li.dataset.role = role;
    li.dataset.login = user.login;
    const nameEl = fragment.querySelector(this.root.dataName);
    const createdEl = fragment.querySelector(this.root.dataCreated);

    if (nameEl) nameEl.textContent = name;
    if (createdEl) createdEl.textContent = created;

    return fragment;
  }



  checkEmptyContainers(...containers) {
    containers.forEach(container => {
      if (container.children.length === 0) {
        container.innerHTML = `<li class="no-users">No ${container.dataset?.containerType || 'users'} found</li>`;
      }
    });
  }

  // Показ ошибки в интерфейсе (опционально)
  showError(message) {
    const containers = [
      document.querySelector(this.root.dataAdminContainer),
      document.querySelector(this.root.dataEmployeeContainer),
      document.querySelector(this.root.dataClientContainer)
    ].filter(Boolean);

    containers.forEach(container => {
      container.innerHTML = `<li class="error-message">⚠️ ${message}</li>`;
    });

    const totalEl = document.querySelector(this.root.dataTotal);
    if (totalEl) totalEl.textContent = '0';
  }
}


document.addEventListener('click', (e) => {
  const actionBtn = e.target.closest('[data-action]');
  if (!actionBtn) return;
  console.log()
  const action = actionBtn.dataset.action;
  const userItem = actionBtn.closest('.user-item');
  const userId = userItem.dataset.id;



  if (action === 'delete') {
    this.deleteUser(userId);
  }
});


export default User;
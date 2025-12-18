let modal = null;
let tabs = null;
let forms = null;
let modalTitle = null;
let modalMode = 'create' // 'create' | 'edit'
let currentEntity = null
let currentId = null
let users = [];
let projects = [];

function initModalElements() {
  modal = document.getElementById('create-modal');
  tabs = document.querySelectorAll('.tab');
  forms = document.querySelectorAll('.form');
  modalTitle = document.querySelector('[data-modal-title]');

  if (!modal) {
    console.error('Modal element not found!');
    return false;
  }
  return true;
}
const TITLES = {
  task: 'Add Task',
  user: 'Add User',
  project: 'Add Project'
}

function switchTab(type) {
  if (!tabs || !forms || !modalTitle) {
    if (!initModalElements()) return;
  }

  tabs.forEach(tab => {
    tab.classList.toggle('tab--active', tab.dataset.tab === type)
  })

  forms.forEach(form => {
    form.classList.toggle('form--active', form.dataset.form === type)
  })
  if (modalTitle) {
    modalTitle.textContent = TITLES[type]
  }
}

async function openModal({ type, mode = 'create', data = null }) {
  if (!modal && !initModalElements()) {
    console.error('Cannot open modal: modal element not found');
    return;
  }

  modalMode = mode
  currentEntity = type
  currentId = data?.id ?? null

  modal.classList.remove('hidden')

  switchTab(type)
  updateModalTitle(type, mode)
  updateSubmitButton(type, mode)

  if (mode === 'edit' && data) {
    await loadUsersAndProjects();
    await fillForm(type, data);
  } else {
    clearForm(type);
    if (type === 'task') {
      await loadUsersAndProjects();
      const form = document.querySelector(`.form[data-form="task"]`);
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

      // Фильтруем только исполнителей для Assigned to
      const employees = users.filter(user => {
        const role = user.role || '';
        return role === 'Исполнитель' || role === 'employee' || role.toLowerCase() === 'исполнитель';
      });

      // Фильтруем только руководителей для Assigned by
      let managers = users.filter(user => {
        const role = user.role || '';
        const lower = role.toLowerCase();
        return (
          role === 'Руководитель' ||
          role === 'Руководитель проектов' ||
          role === 'Администратор' ||
          role === 'manager' ||
          role === 'project manager' ||
          role === 'admin' ||
          lower === 'руководитель' ||
          lower === 'руководитель проектов' ||
          lower === 'администратор'
        );
      });

      // Гарантируем, что текущий пользователь (руководитель) попадёт в список managers
      if (currentUser && currentUser.id && !managers.some(u => String(u.id) === String(currentUser.id))) {
        managers = [
          ...managers,
          {
            id: currentUser.id,
            fullname: currentUser.fullname || 'Текущий пользователь',
            role: currentUser.role || 'Руководитель'
          }
        ];
      }

      populateSelect(form.querySelector('[name="taskProject"]'), projects, 'id', 'title');

      // Автоматически подставляем текущего пользователя в TaskBy и блокируем поле
      const taskBySelect = form.querySelector('[name="taskBy"]');
      populateSelect(taskBySelect, managers, 'id', 'fullname', currentUser.id);
      taskBySelect.disabled = true; // Блокируем при создании

      populateSelect(form.querySelector('[name="taskTo"]'), employees, 'id', 'fullname');
    }
    if (type === 'project') {
      await loadUsersAndProjects();

      const form = document.querySelector('.form[data-form="project"]');

      console.log('Loaded users for project:', users);

      // Фильтруем только клиентов для селекта клиентов проекта
      const clients = users.filter(user => {
        const role = user.role || '';
        const isClient = role === 'Клиент' ||
          role === 'client' ||
          role.toLowerCase() === 'клиент' ||
          role.toLowerCase() === 'client';
        console.log(`User ${user.fullname || user.id}: role="${role}", isClient=${isClient}`);
        return isClient;
      });

      console.log('Filtered clients:', clients);
      console.log('All users roles for debugging:', users.map(u => ({ id: u.id, name: u.fullname, role: u.role })));

      const clientSelect = form.querySelector('[name="projectClient"]');
      if (!clientSelect) {
        console.error('Client select not found!');
        return;
      }

      if (clients.length === 0) {
        console.warn('⚠️ No clients found! Available users:', users);
        console.warn('Make sure you have users with role "Клиент" in the database');
        console.warn('Check with: SELECT id, fullname, role FROM "User" WHERE role = \'Клиент\';');
      }

      populateSelect(
        clientSelect,
        clients,
        'id',
        'fullname'
      );
    }

  }
}


function updateModalTitle(type, mode) {
  if (!modalTitle && !initModalElements()) return;

  const titles = {
    task: { create: 'Add Task', edit: 'Edit Task' },
    user: { create: 'Add User', edit: 'Edit User' },
    project: { create: 'Add Project', edit: 'Edit Project' }
  }

  modalTitle.textContent = titles[type][mode]
}
function updateSubmitButton(type, mode) {
  const form = document.querySelector(`.form[data-form="${type}"]`)
  const button = form.querySelector('button[type="submit"]')

  button.textContent = mode === 'edit'
    ? `Save ${capitalize(type)}`
    : `Create ${capitalize(type)}`
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

async function fillForm(type, data) {
  console.log(users);

  const form = document.querySelector(`.form[data-form="${type}"]`);
  if (!form || !data) return;

  if (type === 'task') {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const isAdmin = currentUser.role === 'Администратор' || currentUser.role === 'admin';

    // Фильтруем только исполнителей для Assigned to
    const employees = users.filter(user => {
      const role = user.role || '';
      return role === 'Исполнитель' || role === 'employee' || role.toLowerCase() === 'исполнитель';
    });

    // Фильтруем только руководителей для Assigned by
    let managers = users.filter(user => {
      const role = user.role || '';
      const lower = role.toLowerCase();
      return (
        role === 'Руководитель' ||
        role === 'Руководитель проектов' ||
        role === 'Администратор' ||
        role === 'manager' ||
        role === 'project manager' ||
        role === 'admin' ||
        lower === 'руководитель' ||
        lower === 'руководитель проектов' ||
        lower === 'администратор'
      );
    });

    // Гарантируем наличие текущего пользователя в списке managers при редактировании
    if (currentUser && currentUser.id && !managers.some(u => String(u.id) === String(currentUser.id))) {
      managers = [
        ...managers,
        {
          id: currentUser.id,
          fullname: currentUser.fullname || 'Текущий пользователь',
          role: currentUser.role || 'Руководитель'
        }
      ];
    }

    // Наполняем select и сразу выставляем выбранное значение
    populateSelect(form.querySelector('[name="taskProject"]'), projects, 'id', 'title', data.taskProject);

    const taskBySelect = form.querySelector('[name="taskBy"]');
    populateSelect(taskBySelect, managers, 'id', 'fullname', data.taskBy);

    // Блокируем TaskBy при редактировании для всех кроме администратора
    if (modalMode === 'edit' && !isAdmin) {
      taskBySelect.disabled = true;
    }

    populateSelect(form.querySelector('[name="taskTo"]'), employees, 'id', 'fullname', data.taskTo);
  }
  if (type === 'project') {
    // Фильтруем только клиентов для селекта клиентов проекта
    const clients = users.filter(user => {
      const role = user.role || '';
      const isClient = role === 'Клиент' ||
        role === 'client' ||
        role.toLowerCase() === 'клиент' ||
        role.toLowerCase() === 'client';
      console.log(`[fillForm] User ${user.fullname || user.id}: role="${role}", isClient=${isClient}`);
      return isClient;
    });

    console.log('[fillForm] Filtered clients for edit:', clients);

    populateSelect(
      form.querySelector('[name="projectClient"]'),
      clients,
      'id',
      'fullname',
      data.projectClient
    );
  }

  // 3. Заполняем остальные поля (текст, даты, радио), кроме select
  Object.entries(data).forEach(([key, value]) => {
    const field = form.querySelector(`[name="${key}"]`);
    if (!field || field.tagName === 'SELECT') return; // Пропускаем select

    if (field.type === 'radio') {
      const radio = form.querySelector(`[name="${key}"][value="${value}"]`);
      if (radio) radio.checked = true;
    } else if (field.type === 'date') {
      field.value = value ? value.split('T')[0] : '';
    } else {
      field.value = value ?? '';
    }
  });
}




function clearForm(type) {
  const form = document.querySelector(`.form[data-form="${type}"]`)
  form.reset()
}
async function fetchUsers(filterRole = null) {
  let url = '/api?action=user.index';
  if (filterRole) {
    url += `&role=${filterRole}`;
  }

  console.log('Fetching users from:', url);

  const res = await fetch(url);

  if (!res.ok) {
    console.error('HTTP error:', res.status, res.statusText);
    const errorText = await res.text();
    console.error('Error response:', errorText);
    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
  }

  const result = await res.json();
  console.log('Users API response:', result);

  if (!result.success) {
    console.error('API error:', result.error);
    throw new Error(result.error || 'Ошибка загрузки пользователей');
  }

  const userData = result.data || [];
  console.log('Returning users:', userData);
  return userData;
}

async function fetchProjects() {
  const res = await fetch('/api?action=projects.index'); // или нужный тебе эндпоинт
  const result = await res.json();

  if (!result.success) {
    throw new Error(result.error || 'Ошибка загрузки проектов');
  }

  return result.data || [];
}

async function loadUsersAndProjects() {
  try {
    console.log('Loading users and projects...');

    // Загружаем всех пользователей (для админов и руководителей) или только нужных ролей
    try {
      users = await fetchUsers();
      console.log('Fetched users:', users);
      console.log('Users count:', users.length);

      // Выводим роли всех пользователей для отладки
      if (users.length > 0) {
        console.log('User roles:', users.map(u => ({ id: u.id, name: u.fullname, role: u.role })));
      }
    } catch (error) {
      console.error('Error fetching all users:', error);
      // Если нет доступа, пробуем загрузить только клиентов и исполнителей
      try {
        console.log('Trying to fetch clients and employees separately...');
        const clients = await fetchUsers('client').catch(() => []);
        const employees = await fetchUsers('employee').catch(() => []);
        users = [...clients, ...employees];
        console.log('Fetched clients and employees:', users);
      } catch (e) {
        console.error('Error fetching filtered users:', e);
        users = [];
      }
    }

    if (!users || users.length === 0) {
      console.warn('⚠️ No users loaded! This might be a permissions issue.');
      console.warn('Current user role:', localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')).role : 'unknown');
    }

    projects = await fetchProjects();
    console.log('Fetched projects:', projects);
  } catch (error) {
    console.error('Ошибка загрузки данных:', error);
    // НЕ очищаем users, если они уже загружены - это позволяет использовать их даже при ошибке загрузки проектов
    if (!users || users.length === 0) {
      users = [];
    }
    projects = [];
  }
}


function closeModal() {
  if (!modal && !initModalElements()) {
    return;
  }
  modal.classList.add('hidden')
}

function initFormTabs() {
  if (!tabs && !initModalElements()) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      switchTab(tab.dataset.tab)
    })
  })
}

function initFormSubmit() {
  if (!forms && !initModalElements()) return;

  forms.forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();

      const data = Object.fromEntries(new FormData(form));
      const type = form.dataset.form; // user | task | project
      const editId = form.dataset.editId;

      if (editId) {
        if (type === 'user') updateUser(editId, data);
        if (type === 'task') updateTask(editId, data);
        if (type === 'project') updateProject(editId, data);

        delete form.dataset.editId;
      } else {
        if (type === 'user') createUser(data);
        if (type === 'task') createTask(data);
        if (type === 'project') createProject(data);
      }

      form.reset();
      closeModal();
    });
  });
}

function mapRoleForDB(role) {
  switch (role) {
    case 'admin': return 'Администратор';
    case 'manager': return 'Руководитель';
    case 'employee': return 'Исполнитель';
    case 'client': return 'Клиент';
    default: return role;
  }
}

async function createUser(data) {
  try {
    // Формируем тело запроса
    const body = {
      fullname: data.userName,
      email: data.userEmail,
      login: data.userLogin,
      password: data.userPassword,
      role: mapRoleForDB(data.userRole) // приводим к значению для базы
    };

    console.log('Отправляемые данные для создания пользователя:', body);
    console.log('Пароль в данных:', data.userPassword ? 'Есть' : 'Отсутствует');

    const response = await fetch(`/api?action=user.create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();
    console.log('Ответ сервера при создании пользователя:', result);

    if (!result.success) {
      throw new Error(result.error || 'Ошибка создания пользователя');
    }

    // result.data теперь содержит объект нового пользователя с id
    const newUser = result.data;
    console.log('Созданный пользователь:', newUser);

    console.log('User created:', newUser);

    // Добавляем нового пользователя в DOM
    addUserToDOM(newUser);

    closeModal();

  } catch (err) {
    console.error('Create user failed:', err);
    alert('Не удалось создать пользователя: ' + err.message);
  }
}

function addUserToDOM(user) {
  const template = document.querySelector('[data-user-template]');
  if (!template) {
    console.warn('User template not found, skip DOM update');
    return;
  }
  const fragment = document.importNode(template.content, true);
  const li = fragment.querySelector('.user-item');

  li.dataset.id = user.id;
  li.dataset.role = user.role;
  li.dataset.email = user.email;

  const nameEl = li.querySelector('[data-user-name]');
  const createdEl = li.querySelector('[data-user-created]');

  if (nameEl) nameEl.textContent = user.fullname;
  if (createdEl) createdEl.textContent = 'Created: ' + new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: '2-digit' });

  // Выбираем контейнер в зависимости от роли
  let containerSelector = '[data-employee-container]';
  if (user.role === 'Администратор' || user.role === 'admin') {
    containerSelector = '[data-admin-container]';
  } else if (user.role === 'Руководитель' || user.role === 'manager') {
    containerSelector = '[data-manager-container]';
  } else if (user.role === 'Исполнитель' || user.role === 'employee') {
    containerSelector = '[data-employee-container]';
  } else if (user.role === 'Клиент' || user.role === 'client') {
    containerSelector = '[data-client-container]';
  }

  const container = document.querySelector(containerSelector);
  if (container) container.appendChild(fragment);
}

async function updateUser(id, data) {
  try {

    const body = {
      fullname: data.userName,
      email: data.userEmail,
      role: mapRoleForDB(data.userRole)
    };

    // Добавляем login и password только если они указаны
    if (data.userLogin) {
      body.login = data.userLogin;
    }
    if (data.userPassword) {
      body.password = data.userPassword;
    }

    const response = await fetch(`/api?action=user.update&id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка обновления пользователя');
    }

    console.log('User updated:', data);

    // Обновляем DOM после успешного обновления
    updateUserInDOM(id, data);

  } catch (err) {
    console.error('Update user failed:', err);
    alert('Не удалось обновить пользователя: ' + err.message);
  }
}

function updateUserInDOM(id, data) {
  const li = document.querySelector(`.user-item[data-id="${id}"]`);
  if (!li) return;

  // Обновляем dataset
  li.dataset.role = data.userRole;
  li.dataset.email = data.userEmail;
  if (data.userLogin) {
    li.dataset.login = data.userLogin;
  }

  // Обновляем имя в DOM
  const nameEl = li.querySelector('[data-user-name]');
  if (nameEl) nameEl.textContent = data.userName;
}

async function deleteUser(id) {
  if (!confirm('Вы уверены, что хотите удалить этого пользователя?')) return;

  try {
    const response = await fetch(`/api?action=user.delete&id=${id}`, {
      method: 'POST', // или 'DELETE', если API поддерживает
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка удаления пользователя');
    }

    // Удаляем из DOM
    removeUserFromDOM(id);

    console.log('User deleted:', id);

  } catch (err) {
    console.error('Delete user failed:', err);
    alert('Не удалось удалить пользователя: ' + err.message);
  }
}

function removeUserFromDOM(id) {
  const li = document.querySelector(`.user-item[data-id="${id}"]`);
  if (li) li.remove();
}

function createEntity(type, data) {
  console.log('CREATE', type, data)
}

function updateEntity(type, id, data) {
  console.log('UPDATE', type, id, data)
}

async function createTask(data) {
  try {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');

    const body = {
      Title: data.taskName,
      Description: data.taskDescription,
      ProjectID: data.taskProject,
      TaskBy: data.taskBy || currentUser.id,    // Используем текущего пользователя, если не указан
      ExecutorID: data.taskTo,
      Status: mapTaskStatusForDB(data.taskStatus),
      StartDate: data.taskStartDate,
      EndDate: data.taskEndDate
    };

    const response = await fetch('/api?action=task.create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка создания задачи');
    }

    const newTask = result.data;
    console.log('Task created:', newTask);
    await loadUsersAndProjects();
    addTaskToDOM(newTask);
    closeModal();

  } catch (err) {
    console.error('Create task failed:', err);
    alert('Не удалось создать задачу: ' + err.message);
  }
}

function mapTaskStatusForDB(status) {
  switch (status) {
    case 'pending': return 'К выполнению';
    case 'in-progress': return 'В работе';
    case 'on-review': return 'На проверке';
    case 'completed': return 'Выполнена';
    default: return 'К выполнению';
  }
}

function mapTaskStatusFromDB(status) {
  switch (status) {
    case 'К выполнению': return 'pending';
    case 'В работе': return 'in-progress';
    case 'На проверке': return 'on-review';
    case 'Выполнена': return 'completed';
    default: return 'pending';
  }
}
function addTaskToDOM(task) {
  const template = document.querySelector('[data-task-template]');
  if (!template) return;

  const fragment = document.importNode(template.content, true);
  const item = fragment.querySelector('.activity-item');

  item.dataset.id = task.id;
  item.dataset.type = 'task';
  item.dataset.taskBy = task.TaskBy;
  item.dataset.taskTo = task.ExecutorID ?? '';
  item.dataset.projectId = task.ProjectID ?? '';

  // Название и описание
  item.querySelector('[data-task-name]').textContent = task.Title || '—';
  item.querySelector('[data-task-description]').textContent = task.Description || '—';
  // taskBy
  // project
  const projHidden = item.querySelector('[data-task-project]');
  if (projHidden) {
    projHidden.dataset.projectId = task.ProjectID ?? '';
  }

  // taskBy
  const byHidden = item.querySelector('[data-task-by]');
  if (byHidden) {
    byHidden.dataset.userId = task.TaskBy ?? '';
  }

  // taskTo
  const toHidden = item.querySelector('[data-task-to]');
  if (toHidden) {
    toHidden.dataset.userId = task.ExecutorID ?? '';
  }

  // Создатель
  const creator = users.find(u => u.id == task.TaskBy);
  const byEl = item.querySelector('[data-task-by-worker]');
  if (byEl) {
    byEl.dataset.userId = task.TaskBy ?? '';
    byEl.textContent = creator?.fullname || '—';
  }

  // Исполнитель
  const executor = users.find(u => u.id == task.ExecutorID);
  const toEl = item.querySelector('[data-task-to-worker]');
  if (toEl) {
    toEl.dataset.userId = task.ExecutorID ?? '';
    toEl.textContent = executor?.fullname || '—';
  }

  // Проект
  const project = projects.find(p => p.id == task.ProjectID);
  const projEl = item.querySelector('[data-task-project]');
  if (projEl) {
    projEl.dataset.projectId = task.ProjectID ?? '';
    projEl.textContent = project?.title || '—';
  }


  // Скрытые поля для редактирования
  const statusEl = item.querySelector('[data-task-status]');
  if (statusEl) statusEl.textContent = task.Status || 'В работе';

  const startEl = item.querySelector('[data-task-start-date]');
  if (startEl) startEl.value = task.StartDate ? task.StartDate.split('T')[0] : '';

  const endEl = item.querySelector('[data-task-end-date]');
  if (endEl) endEl.value = task.EndDate ? task.EndDate.split('T')[0] : '';

  const container = document.querySelector('[data-task-container]');
  if (container) container.appendChild(fragment);
}

async function updateTask(id, data) {
  try {
    const body = {
      Title: data.taskName,
      Description: data.taskDescription,
      ProjectID: data.taskProject,
      TaskBy: data.taskBy,    // добавлено
      TaskTo: data.taskTo,
      Status: mapTaskStatusForDB(data.taskStatus),
      StartDate: data.taskStartDate,
      EndDate: data.taskEndDate
    };


    const response = await fetch(`/api?action=task.update&id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка обновления задачи');
    }

    console.log('Task updated:', data);

    updateTaskInDOM(id, data);

  } catch (err) {
    console.error('Update task failed:', err);
    alert('Не удалось обновить задачу: ' + err.message);
  }
}

function updateTaskInDOM(id, data) {
  const item = document.querySelector(`.activity-item[data-id="${id}"]`);
  if (!item) return;


  // Название и описание
  item.querySelector('[data-task-name]').textContent = data.taskName || '—';
  item.querySelector('[data-task-description]').textContent = data.taskDescription || '—';
  const taskByHidden = item.querySelector('[data-task-by]');
  if (taskByHidden) {
    taskByHidden.dataset.userId = data.taskBy ?? '';
  }

  const byText = item.querySelector('[data-task-by-worker]');
  if (byText) {
    const user = users.find(u => String(u.id) === String(data.taskBy));
    byText.textContent = user?.fullname || '—';
  }

  const taskToHidden = item.querySelector('[data-task-to]');
  if (taskToHidden) {
    taskToHidden.dataset.userId = data.taskTo ?? '';
  }

  const toText = item.querySelector('[data-task-to-worker]');
  if (toText) {
    const user = users.find(u => String(u.id) === String(data.taskTo));
    toText.textContent = user?.fullname || '—';
  }

  // Создатель
  const byEl = item.querySelector('[data-task-by-worker]');
  if (byEl) {
    byEl.dataset.userId = data.taskBy ?? '';
    const creator = users.find(u => String(u.id) === String(data.taskBy));
    byEl.textContent = creator?.fullname || '—';
  }

  // Исполнитель
  const toEl = item.querySelector('[data-task-to-worker]');
  if (toEl) {
    toEl.dataset.userId = data.taskTo ?? '';
    const executor = users.find(u => String(u.id) === String(data.taskTo));
    toEl.textContent = executor?.fullname || '—';
  }

  // Проект
  const projEl = item.querySelector('[data-task-project]');
  if (projEl) {
    projEl.dataset.projectId = data.taskProject ?? '';
    const project = projects.find(p => String(p.id) === String(data.taskProject));
    projEl.textContent = project?.title || '—';
  }

  // Статус
  const statusEl = item.querySelector('[data-task-status]');
  if (statusEl) statusEl.textContent = data.taskStatus || 'К выполнению';

  // Даты
  const startEl = item.querySelector('[data-task-start-date]');
  if (startEl) startEl.value = data.taskStartDate ? data.taskStartDate.split('T')[0] : '';

  const endEl = item.querySelector('[data-task-end-date]');
  if (endEl) endEl.value = data.taskEndDate ? data.taskEndDate.split('T')[0] : '';
}


async function deleteTask(id) {
  if (!confirm('Вы уверены, что хотите удалить эту задачу?')) return;

  try {
    const response = await fetch(`/api?action=task.delete&id=${id}`, {
      method: 'POST' // можно 'DELETE', если API поддерживает
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка удаления задачи');
    }

    removeTaskFromDOM(id);

    console.log('Task deleted:', id);

  } catch (err) {
    console.error('Delete task failed:', err);
    alert('Не удалось удалить задачу: ' + err.message);
  }
}

function removeTaskFromDOM(id) {
  const item = document.querySelector(`.activity-item[data-id="${id}"]`);
  if (item) item.remove();
}





async function createProject(data) {
  try {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const body = {
      title: data.projectName,
      detaileddescription: data.projectDescription,
      clientid: data.projectClient,
      startdate: data.projectStartDate,
      plannedenddate: data.projectEndDate,
      status: "Черновик",
      managerid: currentUser.id // добавляем managerid
    };

    const res = await fetch('/api?action=projects.create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await res.json();
    if (!result.success) throw new Error(result.error || 'Ошибка создания проекта');

    addProjectToDOM(result.data);
    closeModal();
  } catch (err) {
    console.error(err);
    alert('Не удалось создать проект: ' + err.message);
  }
}


function addProjectToDOM(project) {
  const container = document.querySelector('[data-project-container]');
  const template = document.querySelector('[data-project-template]');
  if (!container || !template) return;

  const fragment = document.importNode(template.content, true);
  const item = fragment.querySelector('.project__item');

  item.dataset.id = project.id;
  item.dataset.type = 'project';

  item.querySelector('[data-project-name]').textContent = project.title || '—';
  item.querySelector('[data-project-description]').textContent = project.detaileddescription || '—';

  item.querySelector('[data-project-active]').textContent = project.status || '—';

  item.querySelector('[data-project-date]').textContent =
    project.startdate ? project.startdate.split('T')[0] : '—';

  item.querySelector('[data-project-assigned]').textContent =
    project.client_fullname || '—';

  // ===== скрытые данные =====
  const clientEl = item.querySelector('[data-project-client]');
  if (clientEl) {
    clientEl.dataset.userId = project.clientid ?? '';
  }
  const assignedEl = item.querySelector('[data-project-assigned]');
  if (assignedEl) {
    assignedEl.dataset.userId = project.clientid ?? '';
  }
  const statusEl = item.querySelector('[data-project-status]');
  if (statusEl) {
    statusEl.textContent = project.status ?? '';
  }

  // Сохраняем даты в скрытых полях для редактирования
  const startDateEl = item.querySelector('[data-project-start-date]');
  if (startDateEl) {
    startDateEl.value = project.startdate ? project.startdate.split('T')[0] : '';
  }
  const endDateEl = item.querySelector('[data-project-end-date]');
  if (endDateEl) {
    endDateEl.value = project.plannedenddate ? project.plannedenddate.split('T')[0] : '';
  }

  container.appendChild(fragment);
}

async function updateProject(id, data) {
  try {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    const body = {
      title: data.projectName || null,
      detaileddescription: data.projectDescription || null,
      clientid: data.projectClient || null,
      startdate: data.projectStartDate || null,
      plannedenddate: data.projectEndDate || null,
      status: data.projectStatus || null,
      managerid: data.projectManager || currentUser.id // используем projectManager если есть, иначе текущий пользователь
    };

    const response = await fetch(`/api?action=projects.update&id=${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка обновления проекта');
    }

    console.log('Project updated:', result.data);
    updateProjectInDOM(id, data);

  } catch (err) {
    console.error('Update project failed:', err);
    alert('Update project failed: ' + err.message);
  }
}


function updateProjectInDOM(id, data) {
  const item = document.querySelector(`.project__item[data-id="${id}"]`);
  if (!item) return;

  item.querySelector('[data-project-name]').textContent = data.projectName || '—';
  item.querySelector('[data-project-description]').textContent = data.projectDescription || '—';
  item.querySelector('[data-project-active]').textContent = data.projectStatus || '—';
  item.querySelector('[data-project-date]').value = data.projectEndDate || '';

  // Можно обновить assigned (имя клиента) если нужно:
  const client = users.find(u => String(u.id) === String(data.projectClient));
  item.querySelector('[data-project-assigned]').textContent = client?.fullname || '—';
}


async function deleteProject(id) {
  if (!confirm('Вы уверены, что хотите удалить проект?')) return;

  try {
    const res = await fetch(`/api?action=projects.delete&id=${id}`, { method: 'POST' });
    const result = await res.json();
    if (!result.success) throw new Error(result.error || 'Ошибка удаления проекта');

    const el = document.querySelector(`.project__item[data-id="${id}"]`);
    if (el) el.remove();
  } catch (err) {
    console.error(err);
    alert('Не удалось удалить проект: ' + err.message);
  }
}

function initModalButtons() {
  // Удаляем старые обработчики, если они есть
  document.querySelectorAll('[data-open]').forEach(btn => {
    // Клонируем элемент, чтобы удалить все обработчики
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    newBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const type = newBtn.dataset.open;
      console.log('Opening modal for type:', type);
      openModal({
        type: type,
        mode: 'create'
      });
    });
  });

  document.querySelectorAll('[data-close-modal]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeModal();
    });
  });

  console.log('Modal buttons initialized. Found', document.querySelectorAll('[data-open]').length, 'buttons');
}

// document.addEventListener('click', e => {
//   const btn = e.target.closest('[data-edit-task]')
//   if (!btn) return

//   const taskData = {
//     id: 5,
//     taskName: 'Fix UI',
//     taskDescription: 'Modal redesign',
//     taskStatus: 'in-process',
//     taskBy: '1',
//     taskTo: '2',
//     taskStartDate: '2025-03-01',
//     taskEndDate: '2025-03-10'
//   }

//   openModal({
//     type: 'task',
//     mode: 'edit',
//     data: taskData
//   })
// })

function populateSelect(selectEl, items, valueKey, textKey, selectedValue = '') {
  if (!selectEl) {
    console.error('Select element not found for populateSelect');
    return;
  }

  console.log(`Populating select with ${items ? items.length : 0} items, valueKey=${valueKey}, textKey=${textKey}`);
  console.log('Items to populate:', items);

  selectEl.innerHTML = ''; // очистим старые опции
  const defaultOption = document.createElement('option');
  defaultOption.value = '';
  defaultOption.textContent = '— Select —';
  selectEl.appendChild(defaultOption);

  if (!items || items.length === 0) {
    console.warn('No items to populate select with!');
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = '— Нет доступных вариантов —';
    emptyOption.disabled = true;
    selectEl.appendChild(emptyOption);
    return;
  }

  let addedCount = 0;
  items.forEach(item => {
    const value = item[valueKey];
    const text = item[textKey];

    if (value === null || value === undefined) {
      console.warn('Skipping item with null/undefined value:', item);
      return;
    }

    if (!text) {
      console.warn('Skipping item with empty text:', item);
      return;
    }

    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    if (String(value) === String(selectedValue)) {
      option.selected = true;
    }
    selectEl.appendChild(option);
    addedCount++;
  });

  console.log(`Select populated: ${addedCount} options added`);

}

export function initCreateForm() {
  // Инициализируем элементы модального окна
  if (!initModalElements()) {
    console.error('Modal elements not found, retrying...');
    setTimeout(() => {
      if (initModalElements()) {
        initModalButtons();
        initFormTabs();
        initFormSubmit();
      }
    }, 100);
    return;
  }

  initModalButtons();
  initFormTabs();
  initFormSubmit();
}

let openedMenu = null

document.addEventListener('click', e => {
  const trigger = e.target.closest('.action-menu__trigger')
  const menu = e.target.closest('.action-menu__dropdown')

  // открыть / закрыть меню
  if (trigger) {
    const wrapper = trigger.closest('[data-actions]')
    const dropdown = wrapper.querySelector('.action-menu__dropdown')

    if (openedMenu && openedMenu !== dropdown) {
      openedMenu.classList.add('hidden')
    }

    dropdown.classList.toggle('hidden')
    openedMenu = dropdown
    return
  }

  // клик по пункту меню
  if (menu) {
    const action = e.target.dataset.action
    const item = e.target.closest('[data-id]')
    const type = item?.dataset.type
    const id = item?.dataset.id

    if (action === 'edit') {
      handleEdit(type, id)
    }

    if (action === 'delete') {
      handleDelete(type, id)
    }

    menu.classList.add('hidden')
    return
  }

  // клик вне меню
  if (openedMenu) {
    openedMenu.classList.add('hidden')
    openedMenu = null
  }
})

async function handleEdit(type, id) {
  let data;
  if (type === 'project') {
    data = await getEntityData(type, id);
  } else {
    data = getEntityData(type, id);
  }
  console.log('Data from DOM before fillForm:', data);
  const form = document.querySelector(`.form[data-form="${type}"]`);

  form.dataset.editId = id;

  openModal({
    type,
    mode: 'edit',
    data
  });
}
function mapTaskDataForForm(task) {
  return {
    taskName: task.Title || '',
    taskDescription: task.Description || '',
    taskProject: task.ProjectID || '',
    taskBy: task.TaskBy || '',
    taskTo: task.ExecutorID || '',
    taskStatus: task.Status || '',
    taskStartDate: task.StartDate || '',
    taskEndDate: task.EndDate || ''
  };
}

function getEntityData(type, id) {
  if (type === 'user') {
    const li = document.querySelector(`.user-item[data-id="${id}"]`);
    return {
      id,
      userName: li.dataset.name || li.querySelector('[data-user-name]')?.textContent,
      userEmail: li.dataset.email,
      userLogin: li.dataset.login || '',
      userRole: li.dataset.role
    };
  }

  if (type === 'task') {
    const item = document.querySelector(`.activity-item[data-id="${id}"]`);
    if (!item) {
      console.error('Task item not found for id:', id);
      return null;
    }

    const statusText = item.querySelector('[data-task-status]')?.textContent || '';
    const statusValue = mapTaskStatusFromDB(statusText);

    // Получаем ID из dataset элемента или из скрытых полей
    // Приоритет: скрытые элементы [data-task-by], затем dataset.taskBy
    const taskByEl = item.querySelector('[data-task-by]');
    const taskBy = (taskByEl?.dataset.userId) || item.dataset.taskBy || '';
    
    const taskToEl = item.querySelector('[data-task-to]');
    const taskTo = (taskToEl?.dataset.userId) || item.dataset.taskTo || '';
    
    const taskProjectEl = item.querySelector('[data-task-project]');
    const taskProject = (taskProjectEl?.dataset.projectId) || item.dataset.projectId || '';
    
    console.log('Extracted task data:', { taskBy, taskTo, taskProject, 
      taskByFromEl: taskByEl?.dataset.userId, 
      taskByFromDataset: item.dataset.taskBy,
      taskToFromEl: taskToEl?.dataset.userId,
      taskToFromDataset: item.dataset.taskTo
    });

    return {
      id,
      taskName: item.querySelector('[data-task-name]')?.textContent || '',
      taskDescription: item.querySelector('[data-task-description]')?.textContent || '',
      taskStatus: statusValue,
      taskBy: taskBy,
      taskTo: taskTo,
      taskProject: taskProject,
      taskStartDate: item.querySelector('[data-task-start-date]')?.value || '',
      taskEndDate: item.querySelector('[data-task-end-date]')?.value || ''
    };
  }



  if (type === 'project') {
    const el = document.querySelector(`.project__item[data-id="${id}"]`);

    // Получаем данные проекта из API для точности
    return fetch(`/api?action=projects.get&id=${id}`)
      .then(res => res.json())
      .then(result => {
        if (result.success && result.data) {
          const project = result.data;
          return {
            id,
            projectName: project.title || '',
            projectDescription: project.detaileddescription || '',
            projectClient: project.clientid || '',
            projectStatus: project.status || '',
            projectStartDate: project.startdate ? project.startdate.split('T')[0] : '',
            projectEndDate: project.plannedenddate ? project.plannedenddate.split('T')[0] : ''
          };
        }
        // Fallback на DOM данные
        return {
          id,
          projectName: el.querySelector('[data-project-name]')?.textContent || '',
          projectDescription: el.querySelector('[data-project-description]')?.textContent || '',
          projectClient: el.querySelector('[data-project-client]')?.dataset.userId || el.querySelector('[data-project-assigned]')?.dataset.userId || '',
          projectStatus: el.querySelector('[data-project-status]')?.textContent || '',
          projectStartDate: el.querySelector('[data-project-start-date]')?.value || '',
          projectEndDate: el.querySelector('[data-project-end-date]')?.value || ''
        };
      })
      .catch(() => {
        // Fallback на DOM данные при ошибке
        return {
          id,
          projectName: el.querySelector('[data-project-name]')?.textContent || '',
          projectDescription: el.querySelector('[data-project-description]')?.textContent || '',
          projectClient: el.querySelector('[data-project-client]')?.dataset.userId || el.querySelector('[data-project-assigned]')?.dataset.userId || '',
          projectStatus: el.querySelector('[data-project-status]')?.textContent || '',
          projectStartDate: '',
          projectEndDate: ''
        };
      });
  }

}


function handleDelete(type, id) {
  if (type === 'user') deleteUser(id);
  if (type === 'task') deleteTask(id);
  if (type === 'project') deleteProject(id);
}

// document.addEventListener('click', e => {
//   const btn = e.target.closest('[data-action]');
//   if (!btn) return;

//   const action = btn.dataset.action;
//   const item = btn.closest('.activity-item'); // ищем именно activity-item
//   if (!item) return;

//   const type = item.dataset.type;
//   const id = item.dataset.id;

//   if (!id) {
//     console.error('Не удалось найти ID задачи для удаления');
//     return;
//   }

//   if (action === 'edit') handleEdit(type, id);
//   if (action === 'delete') {
//     if (type === 'task') deleteTask(id);
//     if (type === 'user') deleteUser(id);
//     if (type === 'project') deleteProject(id);
//   }
// });
// Удален дублирующий обработчик удаления задач - используется общий handleDelete

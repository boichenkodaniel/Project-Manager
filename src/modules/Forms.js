const modal = document.getElementById('create-modal')
const tabs = document.querySelectorAll('.tab')
const forms = document.querySelectorAll('.form')
const modalTitle = document.querySelector('[data-modal-title]')
let modalMode = 'create' // 'create' | 'edit'
let currentEntity = null
let currentId = null
const TITLES = {
  task: 'Add Task',
  user: 'Add User',
  project: 'Add Project'
}

function switchTab(type) {
  tabs.forEach(tab => {
    tab.classList.toggle('tab--active', tab.dataset.tab === type)
  })

  forms.forEach(form => {
    form.classList.toggle('form--active', form.dataset.form === type)
  })
  modalTitle.textContent = TITLES[type]
}

function openModal({ type, mode = 'create', data = null }) {
  modalMode = mode
  currentEntity = type
  currentId = data?.id ?? null

  modal.classList.remove('hidden')

  switchTab(type)
  updateModalTitle(type, mode)
  updateSubmitButton(type, mode)

  if (mode === 'edit' && data) {
    fillForm(type, data)
  } else {
    clearForm(type)
  }
}

function updateModalTitle(type, mode) {
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

function fillForm(type, data) {
  const form = document.querySelector(`.form[data-form="${type}"]`)
  Object.entries(data).forEach(([key, value]) => {
    const field = form.querySelector(`[name="${key}"]`)
    if (!field) return

    if (field.type === 'radio') {
      form.querySelector(`[name="${key}"][value="${value}"]`).checked = true
    } else {
      field.value = value
    }
  })
}
function clearForm(type) {
  const form = document.querySelector(`.form[data-form="${type}"]`)
  form.reset()
}


function closeModal() {
  modal.classList.add('hidden')
}

function initFormTabs() {
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      switchTab(tab.dataset.tab)
    })
  })
}

function initFormSubmit() {
  forms.forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();

      const data = Object.fromEntries(new FormData(form));
      const editId = form.dataset.editId;

      if (editId) {
        updateUser(editId, data);
        delete form.dataset.editId;
      } else {
        createUser(data);
      }

      form.reset();
      closeModal();
    });
  });
}
function mapRoleForDB(role) {
  switch (role) {
    case 'admin': return 'Администратор';
    case 'employee': return 'Исполнитель'; // или 'Руководитель' в зависимости от того, что нужно
    case 'client': return 'Клиент';
    default: return role; // на всякий случай
  }
}
async function updateUser(id, data) {
  try {

    const body = {
      fullname: data.userName,
      email: data.userEmail,
      role: mapRoleForDB(data.userRole)
    };

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

// временные заглушки (позже подключишь store / API)
function createTask(data) {
  console.log('Create task:', data)
}

async function createUser(data) {
  try {
    // Формируем тело запроса
    const body = {
      fullname: data.userName,
      email: data.userEmail,
      role: mapRoleForDB(data.userRole) // приводим к значению для базы
    };

    const response = await fetch(`/api?action=user.create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Ошибка создания пользователя');
    }

    // result.data теперь содержит объект нового пользователя с id
    const newUser = result.data;

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
  const containerSelector = {
    'Администратор': '[data-admin-container]',
    'Исполнитель': '[data-employee-container]',
    'Клиент': '[data-client-container]'
  }[user.role] || '[data-employee-container]';

  const container = document.querySelector(containerSelector);
  if (container) container.appendChild(fragment);
}




function createProject(data) {
  console.log('Create project:', data)
}

function initModalButtons() {
  document.querySelectorAll('[data-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      openModal({
        type: btn.dataset.open,
        mode: 'create'
      })

    })
  })

  document.querySelectorAll('[data-close-modal]').forEach(el => {
    el.addEventListener('click', closeModal)
  })
}
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-edit-task]')
  if (!btn) return

  const taskData = {
    id: 5,
    taskName: 'Fix UI',
    taskDescription: 'Modal redesign',
    taskStatus: 'in-process',
    taskBy: '1',
    taskTo: '2',
    taskStartDate: '2025-03-01',
    taskEndDate: '2025-03-10'
  }

  openModal({
    type: 'task',
    mode: 'edit',
    data: taskData
  })
})


export function initCreateForm() {
  initModalButtons()
  initFormTabs()
  initFormSubmit()
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

function handleEdit(type, id) {
  const data = getEntityData(type, id);
  const form = document.querySelector(`.form[data-form="${type}"]`);

  // Устанавливаем id редактируемого объекта
  form.dataset.editId = id;

  openModal({
    type,
    mode: 'edit',
    data
  });
}

function getEntityData(type, id) {
  if (type === 'user') {
    const li = document.querySelector(`.user-item[data-id="${id}"]`)
    return {
      id,
      userName: li.dataset.name || li.querySelector('[data-user-name]')?.textContent,
      userEmail: li.dataset.email,
      userRole: li.dataset.role
    }
  }
  // Для задач и проектов оставляем заглушку
  return {
    id,
    taskName: 'Fix UI',
    taskDescription: 'Modal redesign',
    taskStatus: 'in-process',
    taskBy: '1',
    taskTo: '2'
  }
}

function handleDelete(type, id) {
  if (type === 'user') {
    deleteUser(id);
  }
  // для других сущностей потом добавим
}

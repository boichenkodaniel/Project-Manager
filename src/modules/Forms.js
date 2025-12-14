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
      e.preventDefault()

      const type = form.dataset.form
      const data = Object.fromEntries(new FormData(form))

      if (modalMode === 'create') {
        createEntity(type, data)
      } else {
        updateEntity(type, currentId, data)
      }

      closeModal()
    })
  })
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

function createUser(data) {
  console.log('Create user:', data)
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
  const data = getEntityData(type, id)

  openModal({
    type,
    mode: 'edit',
    data
  })
}
function getEntityData(type, id) {
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
  const confirmed = confirm(`Delete this ${type}?`)
  if (!confirmed) return

  console.log('DELETE', type, id)

  // тут потом API + удаление из DOM
}


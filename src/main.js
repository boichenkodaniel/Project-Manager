import './style.css';

import Auth from './modules/Auth';
import ChartModule from './modules/ChartModule';
import Task from "./modules/Task";
import Issue from "./modules/Issue";
import Project from "./modules/Project";
import User from "./modules/User";
import { initCreateForm } from './modules/Forms.js'

// Инициализация авторизации
const auth = new Auth();


let taskModuleInstance = null;
let issueModuleInstance = null;
let projectModuleInstance = null;
let userModuleInstance = null;

// Ждем загрузки DOM и проверки авторизации
document.addEventListener('DOMContentLoaded', async () => {
  // Ждем завершения проверки авторизации
  await waitForAuth();
  
  // Проверяем, что пользователь авторизован
  if (auth.getUser()) {
    // Инициализируем формы
    initCreateForm();
    
    // Определяем текущую страницу
    const currentPage = window.location.pathname.split('/').pop();
    
    // Инициализируем модули и загружаем данные в зависимости от страницы
    switch (currentPage) {
      case 'index.html':
      case '': // для случая, если index.html - корневой URL
        userModuleInstance = new User(); // для инициализации users, проектов и задач
        projectModuleInstance = new Project();
        taskModuleInstance = new Task();
        issueModuleInstance = new Issue();

        taskModuleInstance.loadRecentCompletedTasks();
        issueModuleInstance.loadTopIssues();
        new ChartModule();
        break;
      case 'tasks.html':
        taskModuleInstance = new Task();
        taskModuleInstance.loadAndRender(); // Загружаем все задачи на странице задач
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        issueModuleInstance = new Issue();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        break;
      case 'issues.html':
        issueModuleInstance = new Issue();
        issueModuleInstance.loadAndRender(); // Загружаем все issues на странице Issues
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        taskModuleInstance = new Task();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        break;
      case 'users.html':
        userModuleInstance = new User();
        userModuleInstance.loadAndRender(); // Загружаем всех пользователей на странице пользователей
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        taskModuleInstance = new Task();
        issueModuleInstance = new Issue();
        projectModuleInstance = new Project();
        break;
      case 'projects.html':
        projectModuleInstance = new Project();
        projectModuleInstance.loadAndRender(); // Загружаем все проекты на странице проектов
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        taskModuleInstance = new Task();
        issueModuleInstance = new Issue();
        userModuleInstance = new User();
        break;
      default:
        console.warn('Неизвестная страница:', currentPage);
        // Тем не менее инициализируем все модули на всякий случай
        taskModuleInstance = new Task();
        issueModuleInstance = new Issue();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        new ChartModule();
    }
    
    // Добавляем кнопку выхода
    addLogoutButton();
  }
});

// Функция ожидания завершения проверки авторизации
function waitForAuth() {
  return new Promise((resolve) => {
    // Если пользователь уже загружен, сразу разрешаем
    if (auth.getUser()) {
      resolve();
      return;
    }
    
    // Иначе ждем максимум 3 секунды
    let attempts = 0;
    const checkInterval = setInterval(() => {
      attempts++;
      if (auth.getUser() || attempts > 30) {
        clearInterval(checkInterval);
        resolve();
      }
    }, 100);
  });
}

function addLogoutButton() {
  const profile = document.querySelector('.profile');
  if (profile && !document.querySelector('.logout-button')) {
    const logoutBtn = document.createElement('button');
    logoutBtn.className = 'logout-button';
    logoutBtn.textContent = 'Выйти';
    logoutBtn.style.cssText = 'margin-top: 1rem; padding: 0.5rem 1rem; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;';
    logoutBtn.addEventListener('click', () => auth.logout());
    profile.appendChild(logoutBtn);
  }
}


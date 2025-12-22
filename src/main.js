import './style.css';

import Auth from './modules/Auth';
import Task from "./modules/Task";
import Project from "./modules/Project";
import User from "./modules/User";
import Dashboard from "./modules/Dashboard";
import Notification from "./modules/Notification";
import ReportModule from "./modules/Report";
import { initCreateForm } from './modules/Forms.js'

// Инициализация авторизации
const auth = new Auth();


let taskModuleInstance = null;
let projectModuleInstance = null;
let userModuleInstance = null;
let notificationModuleInstance = null;

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
        // Загружаем список задач для блока задач на дашборде
        taskModuleInstance.loadAndRender();
        new Dashboard(); // Загружаем Dashboard с проектами и задачами
        notificationModuleInstance = new Notification(); // Уведомления (тосты + счетчик)
        break;
      case 'tasks.html':
        taskModuleInstance = new Task();
        taskModuleInstance.loadAndRender(); // Загружаем все задачи на странице задач
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        notificationModuleInstance = new Notification();
        break;
      case 'users.html':
        userModuleInstance = new User();
        userModuleInstance.loadAndRender(); // Загружаем всех пользователей на странице пользователей
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        taskModuleInstance = new Task();
        projectModuleInstance = new Project();
        notificationModuleInstance = new Notification();
        break;
      case 'projects.html':
        projectModuleInstance = new Project();
        projectModuleInstance.loadAndRender(); // Загружаем все проекты на странице проектов
        // Для остальных модулей - только инициализация без рендера, если не нужны на этой странице
        taskModuleInstance = new Task();
        userModuleInstance = new User();
        notificationModuleInstance = new Notification();
        break;
      case 'notifications.html':
        // Страница уведомлений
        notificationModuleInstance = new Notification();
        // Инициализируем базовые модули (для навигации и модалки)
        taskModuleInstance = new Task();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        break;
      case 'reports.html':
        // Страница отчётов для руководителя
        // Базовые модули для модалки и навигации
        taskModuleInstance = new Task();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
        notificationModuleInstance = new Notification();
        // Модуль отчётов
        new ReportModule();
        break;
      default:
        console.warn('Неизвестная страница:', currentPage);
        // Тем не менее инициализируем все модули на всякий случай
        taskModuleInstance = new Task();
        projectModuleInstance = new Project();
        userModuleInstance = new User();
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
    logoutBtn.style.cssText = 'padding: 0.5rem 1rem; background: #a12424ff; color: white; border: none; border-radius: 5px; cursor: pointer;margin-left:auto';
    logoutBtn.addEventListener('click', () => auth.logout());
    profile.appendChild(logoutBtn);
  }
}


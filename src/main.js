import './style.css';
import ChartModule from './modules/ChartModule';
let chartModule = null;

const contentMap = {
  'dashboard': {
    title: 'Dashboard',
    file: 'src/templates/dashboard-content.html',
    contentClass: 'main__content'
  },
  'tasks': {
    title: 'Tasks',
    file: 'src/templates/tasks-content.html',
    contentClass: 'task__content'
  },
  'projects': {
    title: 'Projects',
    file: 'src/templates/projects-content.html',
    contentClass: 'project__content'
  },
  'issues': {
    title: 'Issues',
    file: 'src/templates/issues-content.html',
    contentClass: 'issues__content'
  },
  'users': {
    title: 'Users',
    file: 'src/templates/users-content.html',
    contentClass: 'users__content'
  }
};

// Функция загрузки контента
async function loadContent(page) {
  const config = contentMap[page];
  if (!config) return;

  try {
    // Загружаем HTML контент
    const response = await fetch(config.file);
    const content = await response.text();
    // Обновляем страницу
    document.getElementById('content').innerHTML = content;
    document.getElementById('content').className = config.contentClass;
    document.getElementById('page_title').textContent = config.title;
    document.querySelector('title').textContent = "Project manager - " + config.title;

    // Обновляем активную ссылку в навигации
    document.querySelectorAll('.nav-link').forEach(link => {
      link.classList.remove('nav-link--active');
    });
    document.querySelector(`[data-page="${page}"]`).classList.add('nav-link--active');
    setTimeout(() => {
      if (chartModule) {
        chartModule.initCharts();
      }
    }, 100);
    // Обновляем URL без перезагрузки страницы
    history.pushState({ page }, '', `?page=${page}`);
  } catch (error) {
    console.error('Error loading content:', error);
    document.getElementById('content').innerHTML = '<p>Error loading content</p>';
  }
}

// Обработчики событий
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const page = e.target.closest('a').dataset.page;
    loadContent(page);
  });
});

// Обработка кнопок браузера (назад/вперед)
window.addEventListener('popstate', (event) => {
  if (event.state && event.state.page) {
    loadContent(event.state.page);
  }
});

document.addEventListener('DOMContentLoaded', () => {
  // Создаем экземпляр ChartModule
  chartModule = new ChartModule();

  // Загрузка начальной страницы
  const urlParams = new URLSearchParams(window.location.search);
  const initialPage = urlParams.get('page') || 'dashboard';
  loadContent(initialPage);
});
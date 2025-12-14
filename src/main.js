import './style.css';

import ChartModule from './modules/ChartModule';
import Task from "./modules/Task";
import Issue from "./modules/Issue";
import Project from "./modules/Project";
import User from "./modules/User";
import { initCreateForm } from './modules/Forms.js'
document.addEventListener('DOMContentLoaded', () => {
  initCreateForm()
})

new ChartModule();
new Task();
new Issue();
new Project();
new User();


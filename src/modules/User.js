class User {
  root = {
    dataTemplate: "[data-user-template]",
    dataName: "[data-user-name]",
    dataCreated: "[data-user-created]",
    dataAdminContainer: "[data-admin-container]",
    dataEmployeeContainer: "[data-employee-container]",
    dataClientContainer: "[data-client-container]"
  };

  userList = [
    {
      name: 'Tommy Fury',
      created: 'Created: 15 Jan 25',
      type: 'admin'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 10 Jan 25',
      type: 'admin'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 12 Jan 25',
      type: 'employee'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 08 Jan 25',
      type: 'employee'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 14 Jan 25',
      type: 'employee'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 05 Jan 25',
      type: 'client'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 20 Jan 25',
      type: 'client'
    },
    {
      name: 'Tommy Fury',
      created: 'Created: 12 Jan 25',
      type: 'employee'
    }
  ];
  constructor() {
    this.render();
  }

  render() {
    const adminContainer = document.querySelector(this.root.dataAdminContainer);
    const employeeContainer = document.querySelector(this.root.dataEmployeeContainer);
    const clientContainer = document.querySelector(this.root.dataClientContainer);
    const template = document.querySelector(this.root.dataTemplate);

    adminContainer.innerHTML = '';
    employeeContainer.innerHTML = '';
    clientContainer.innerHTML = '';

    // Распределяем пользователей по контейнерам
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
          console.warn(`Unknown user type: ${user.type}`);
      }
    });

    // Проверяем если контейнеры пустые, добавляем сообщение
    this.checkEmptyContainers(adminContainer, employeeContainer, clientContainer);
  }

  createUserLayout(user, template) {
    const { name, created } = user;
    const userElement = document.importNode(template.content, true);
    userElement.querySelector(this.root.dataName).textContent = name;
    userElement.querySelector(this.root.dataCreated).textContent = created;

    return userElement;
  }

  checkEmptyContainers(adminContainer, employeeContainer, clientContainer) {
    if (adminContainer.children.length === 0) {
      adminContainer.innerHTML = '<li class="no-users">No admin users found</li>';
    }
    if (employeeContainer.children.length === 0) {
      employeeContainer.innerHTML = '<li class="no-users">No employee users found</li>';
    }
    if (clientContainer.children.length === 0) {
      clientContainer.innerHTML = '<li class="no-users">No client users found</li>';
    }
  }

}

export default User;
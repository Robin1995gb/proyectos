// Funciones de utilidad
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const formatNumber = (number) => {
    return parseFloat(number).toFixed(2);
};

const showAlert = (message, type = 'success') => {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.textContent = message;

    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
};

// Funciones de validación
const validateRequired = (value, fieldName) => {
    if (!value || value.trim() === '') {
        throw new Error(`El campo ${fieldName} es requerido`);
    }
    return value.trim();
};

const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        throw new Error('El correo electrónico no es válido');
    }
    return email;
};

const validateNumber = (number, min, max, fieldName) => {
    const num = parseFloat(number);
    if (isNaN(num)) {
        throw new Error(`El campo ${fieldName} debe ser un número`);
    }
    if (min !== undefined && num < min) {
        throw new Error(`El campo ${fieldName} debe ser mayor o igual a ${min}`);
    }
    if (max !== undefined && num > max) {
        throw new Error(`El campo ${fieldName} debe ser menor o igual a ${max}`);
    }
    return num;
};

// Funciones de API
const apiRequest = async (url, method = 'GET', data = null) => {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        return result;
    } catch (error) {
        showAlert(error.message, 'danger');
        throw error;
    }
};

// Funciones de manejo de formularios
const handleFormSubmit = async (form, url, method = 'POST') => {
    try {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        const result = await apiRequest(url, method, data);
        showAlert(result.message);
        return result;
    } catch (error) {
        console.error('Error al enviar el formulario:', error);
        return null;
    }
};

// Funciones de manejo de tablas
const createTable = (data, columns) => {
    const table = document.createElement('table');
    table.className = 'table';

    // Crear encabezado
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    columns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column.label;
        headerRow.appendChild(th);
    });

    thead.appendChild(headerRow);
    table.appendChild(thead);

    // Crear cuerpo
    const tbody = document.createElement('tbody');
    
    data.forEach(item => {
        const row = document.createElement('tr');
        
        columns.forEach(column => {
            const td = document.createElement('td');
            td.textContent = column.render ? column.render(item[column.key]) : item[column.key];
            row.appendChild(td);
        });

        tbody.appendChild(row);
    });

    table.appendChild(tbody);
    return table;
};

// Funciones de manejo de select
const populateSelect = (select, data, valueKey, labelKey) => {
    select.innerHTML = '<option value="">Seleccione una opción</option>';
    
    data.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueKey];
        option.textContent = item[labelKey];
        select.appendChild(option);
    });
};

// Funciones de manejo de sesión
const checkSession = async () => {
    try {
        const response = await fetch('php/verificar_sesion.php');
        const result = await response.json();
        
        if (!result.success) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Error al verificar la sesión:', error);
        window.location.href = 'index.php';
    }
};

// Funciones de manejo de permisos
const checkPermission = (requiredRole) => {
    const userRole = document.body.dataset.userRole;
    return userRole === requiredRole || userRole === 'Administrador';
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    // Manejar menú móvil
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Manejar formularios
    const forms = document.querySelectorAll('form[data-api]');
    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const url = form.dataset.api;
            const method = form.dataset.method || 'POST';
            await handleFormSubmit(form, url, method);
        });
    });

    // Verificar sesión en páginas protegidas
    if (document.body.dataset.requiresAuth === 'true') {
        checkSession();
    }
}); 
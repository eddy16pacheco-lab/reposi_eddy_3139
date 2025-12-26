// ============================================
// FUNCIONES DE INTERFAZ DE USUARIO
// ============================================

// Mostrar mensaje modal
function showMessage(title, message, callback = null) {
    const modal = document.getElementById('messageModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalBtn = document.getElementById('modalBtn');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modal.style.display = 'flex';
    
    modalBtn.onclick = function() {
        modal.style.display = 'none';
        if (callback) callback();
    };
}

// Formatear fecha en español
function formatDate(date) {
    return date.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Formatear número como moneda
function formatCurrency(amount) {
    return '$' + amount.toLocaleString('es-CL');
}

// Formatear fecha corta
function formatShortDate(date) {
    return date.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short'
    });
}

// Mostrar página de login
function showLoginPage() {
    document.getElementById('loginPage').classList.remove('hidden');
    document.getElementById('registerPage').classList.add('hidden');
    document.getElementById('appContainer').classList.add('hidden');
}

// Mostrar página de registro
function showRegisterPage() {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('registerPage').classList.remove('hidden');
    document.getElementById('appContainer').classList.add('hidden');
    
    // Establecer fecha mínima para fecha de nacimiento (18 años atrás)
    const minDate = new Date();
    minDate.setFullYear(minDate.getFullYear() - 18);
    document.getElementById('regBirthDate').max = minDate.toISOString().split('T')[0];
}

// Mostrar aplicación principal
function showApp() {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('registerPage').classList.add('hidden');
    document.getElementById('appContainer').classList.remove('hidden');
    
    updateUI();
}

// Cambiar de pestaña
function changeTab(tabName) {
    const navTabs = document.querySelectorAll('.nav-tab');
    const pages = document.querySelectorAll('.page');
    
    // Actualizar pestañas activas
    navTabs.forEach(tab => {
        if (tab.dataset.tab === tabName) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
    
    // Mostrar la página correspondiente
    pages.forEach(page => {
        if (page.id === tabName + 'Page') {
            page.classList.remove('hidden');
        } else {
            page.classList.add('hidden');
        }
    });
    
    // Actualizar contenido específico de la pestaña
    switch(tabName) {
        case 'home':
            updateDailyStats();
            loadRecentTransactions();
            break;
        case 'summary':
            updateWeeklyStats();
            break;
        case 'adjustments':
            updateProfileInfo();
            break;
        case 'users':
            loadUsersList();
            break;
    }
}

// Actualizar fecha actual
function updateCurrentDate() {
    const now = new Date();
    document.getElementById('currentDate').textContent = formatDate(now);
    document.getElementById('transactionDate').textContent = formatDate(now);
}

// Actualizar estadísticas diarias
function updateDailyStats() {
    const stats = getDailyStats();
    
    document.getElementById('dailyProfit').textContent = formatCurrency(stats.profit);
    document.getElementById('dailySales').textContent = formatCurrency(stats.sales);
    document.getElementById('dailyExpenses').textContent = formatCurrency(stats.expenses);
}

// Cargar transacciones recientes
function loadRecentTransactions() {
    const transactions = getUserTransactions();
    const recentTransactions = document.getElementById('recentTransactions');
    recentTransactions.innerHTML = '';
    
    // Ordenar por fecha (más recientes primero) y tomar las últimas 5
    const recent = transactions
        .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
        .slice(0, 5);
    
    if (recent.length === 0) {
        recentTransactions.innerHTML = '<li style="text-align: center; padding: 20px; color: #666;">No hay transacciones registradas</li>';
        return;
    }
    
    recent.forEach(transaction => {
        const li = document.createElement('li');
        li.className = 'transaction-item';
        
        const isSale = transaction.tipo_transaccion === 'Venta';
        const typeClass = isSale ? 'sale-icon' : 'expense-icon';
        const typeIcon = isSale ? 'fas fa-cash-register' : 'fas fa-receipt';
        const amountClass = isSale ? 'sale-amount' : 'expense-amount';
        const amountPrefix = isSale ? '+' : '-';
        
        const date = new Date(transaction.fecha);
        const formattedDate = formatShortDate(date);
        
        li.innerHTML = `
            <div class="transaction-info">
                <div class="transaction-icon ${typeClass}">
                    <i class="${typeIcon}"></i>
                </div>
                <div>
                    <div class="transaction-name">${transaction.descripcion || 'Sin descripción'}</div>
                    <div class="transaction-date">${formattedDate} • ${transaction.forma_pago}</div>
                </div>
            </div>
            <div class="transaction-amount ${amountClass}">${amountPrefix}${formatCurrency(transaction.monto_transacion)}</div>
        `;
        
        recentTransactions.appendChild(li);
    });
}

// Actualizar estadísticas semanales
function updateWeeklyStats() {
    const stats = getWeeklyStats();
    
    document.getElementById('weeklyProfit').textContent = formatCurrency(stats.profit);
    document.getElementById('weeklySales').textContent = formatCurrency(stats.sales);
    document.getElementById('weeklyExpenses').textContent = formatCurrency(stats.expenses);
    
    // Actualizar rango de fechas
    const startFormatted = stats.startDate.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long'
    });
    const endFormatted = stats.endDate.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long'
    });
    document.getElementById('weekRange').textContent = `Semana del ${startFormatted} al ${endFormatted}`;
    
    // Actualizar días de la semana
    const weekDays = document.getElementById('weekDays');
    weekDays.innerHTML = '';
    
    stats.days.forEach(day => {
        const dayItem = document.createElement('div');
        dayItem.className = 'day-item';
        
        const dayName = day.date.toLocaleDateString('es-ES', { weekday: 'long' });
        const dayNumber = day.date.getDate();
        
        dayItem.innerHTML = `
            <div class="day-name">${dayName.charAt(0).toUpperCase() + dayName.slice(1)} ${dayNumber}</div>
            <div class="day-amount">${formatCurrency(day.profit)}</div>
        `;
        
        weekDays.appendChild(dayItem);
    });
}

// Actualizar información del perfil
function updateProfileInfo() {
    const user = getCurrentUser();
    if (!user) return;
    
    const userIcon = document.getElementById('userIcon');
    const userProfileInfo = document.getElementById('userProfileInfo');
    
    // Actualizar icono de usuario con la primera letra del nombre
    userIcon.textContent = user.nombre.charAt(0).toUpperCase();
    
    // Actualizar información del perfil
    const birthDate = new Date(user.fecha_nacimiento);
    const formattedBirthDate = birthDate.toLocaleDateString('es-ES');
    
    userProfileInfo.innerHTML = `
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="background-color: #3498db; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px; margin-right: 15px;">
                ${user.nombre.charAt(0).toUpperCase()}
            </div>
            <div>
                <div style="font-weight: 600; font-size: 18px;">${user.nombre} ${user.apellido}</div>
                <div style="color: #666;">${user.rol}</div>
            </div>
        </div>
        <div style="border-top: 1px solid #eee; padding-top: 15px;">
            <p><strong>Usuario:</strong> ${user.nombre_usuario}</p>
            <p><strong>Correo:</strong> ${user.correo}</p>
            <p><strong>Fecha de nacimiento:</strong> ${formattedBirthDate}</p>
        </div>
    `;
}

// Cargar lista de usuarios (solo para administradores)
function loadUsersList() {
    const users = getAllUsers();
    const currentUser = getCurrentUser();
    const usersList = document.getElementById('usersList');
    
    usersList.innerHTML = '';
    
    if (!isAdmin()) {
        usersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">No tienes permisos para ver esta sección</div>';
        return;
    }
    
    if (users.length === 0) {
        usersList.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;">No hay usuarios registrados</div>';
        return;
    }
    
    users.forEach(user => {
        const userItem = document.createElement('div');
        userItem.className = 'transaction-item';
        userItem.style.cursor = 'default';
        
        userItem.innerHTML = `
            <div class="transaction-info">
                <div class="transaction-icon" style="background-color: ${user.rol === 'Administrador' ? '#e74c3c' : '#3498db'};">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="transaction-name">${user.nombre} ${user.apellido}</div>
                    <div class="transaction-date">${user.nombre_usuario} • ${user.rol}</div>
                </div>
            </div>
            <div>
                <button class="delete-user-btn" data-id="${user.id}" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px;" ${user.id === currentUser.id ? 'disabled' : ''}>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        usersList.appendChild(userItem);
    });
    
    // Agregar event listeners a los botones de eliminar
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const userId = parseInt(this.dataset.id);
            const result = deleteUser(userId);
            
            if (result.success) {
                showMessage('Éxito', result.message, () => {
                    loadUsersList();
                });
            } else {
                showMessage('Error', result.message);
            }
        });
    });
}

// Configurar formulario de registro de transacción
function setupTransactionForm(isSale) {
    const formTitle = document.getElementById('formTitle');
    const amountInput = document.getElementById('amountInput');
    const descriptionInput = document.getElementById('descriptionInput');
    
    if (isSale) {
        formTitle.textContent = 'Registrar Venta';
        amountInput.placeholder = '$ 7.000';
        descriptionInput.placeholder = 'Ej: Venta de comida rápida';
    } else {
        formTitle.textContent = 'Registrar Gasto';
        amountInput.placeholder = '$ 15.000';
        descriptionInput.placeholder = 'Ej: Compra de mercadería';
    }
    
    // Limpiar formulario
    amountInput.value = '';
    descriptionInput.value = '';
    
    // Cambiar a pestaña de transacción
    changeTab('transaction');
}

// Actualizar toda la interfaz
function updateUI() {
    updateCurrentDate();
    updateProfileInfo();
    updateDailyStats();
    loadRecentTransactions();
    
    // Mostrar/ocultar elementos según permisos
    const manageUsersBtn = document.getElementById('manageUsersBtn');
    if (manageUsersBtn) {
        manageUsersBtn.style.display = isAdmin() ? 'flex' : 'none';
    }
}
// ============================================
// SISTEMA DE GESTIÓN DE TRANSACCIONES
// ============================================

// Registrar una nueva transacción
function registerTransaction(transactionData) {
    const data = getStorageData();
    const user = getCurrentUser();
    
    if (!user) {
        return { success: false, message: 'No hay usuario activo' };
    }
    
    // Validar datos
    if (!transactionData.monto || transactionData.monto <= 0) {
        return { success: false, message: 'El monto debe ser mayor a 0' };
    }
    
    if (!transactionData.tipo || !['Venta', 'Gasto'].includes(transactionData.tipo)) {
        return { success: false, message: 'Tipo de transacción inválido' };
    }
    
    if (!transactionData.forma_pago || !['Efectivo', 'Transferencia', 'Tarjeta'].includes(transactionData.forma_pago)) {
        return { success: false, message: 'Forma de pago inválida' };
    }
    
    const newTransaction = {
        id_transaccion: data.nextTransaccionId,
        id_usuario: user.id,
        fecha: new Date().toISOString().split('T')[0], // Fecha actual YYYY-MM-DD
        tipo_transaccion: transactionData.tipo,
        monto_transacion: parseFloat(transactionData.monto),
        descripcion: transactionData.descripcion || '',
        forma_pago: transactionData.forma_pago
    };
    
    data.transacciones.push(newTransaction);
    data.nextTransaccionId++;
    saveStorageData(data);
    
    return { success: true, message: 'Transacción registrada exitosamente', transaction: newTransaction };
}

// Obtener transacciones del usuario actual
function getUserTransactions() {
    const data = getStorageData();
    const user = getCurrentUser();
    
    if (!user) return [];
    
    // Si es administrador, ver todas las transacciones
    if (user.rol === 'Administrador') {
        return data.transacciones;
    }
    
    // Si es empleado, solo ver sus propias transacciones
    return data.transacciones.filter(trans => trans.id_usuario === user.id);
}

// Obtener transacciones por fecha
function getTransactionsByDate(date) {
    const dateStr = typeof date === 'string' ? date : date.toISOString().split('T')[0];
    const transactions = getUserTransactions();
    return transactions.filter(trans => trans.fecha === dateStr);
}

// Obtener transacciones por rango de fechas
function getTransactionsByDateRange(startDate, endDate) {
    const transactions = getUserTransactions();
    const start = new Date(startDate).toISOString().split('T')[0];
    const end = new Date(endDate).toISOString().split('T')[0];
    
    return transactions.filter(trans => {
        const transDate = trans.fecha;
        return transDate >= start && transDate <= end;
    });
}

// Obtener estadísticas diarias
function getDailyStats(date = new Date()) {
    const dateStr = date.toISOString().split('T')[0];
    const transactions = getTransactionsByDate(dateStr);
    
    const sales = transactions
        .filter(trans => trans.tipo_transaccion === 'Venta')
        .reduce((sum, trans) => sum + trans.monto_transacion, 0);
    
    const expenses = transactions
        .filter(trans => trans.tipo_transaccion === 'Gasto')
        .reduce((sum, trans) => sum + trans.monto_transacion, 0);
    
    const profit = sales - expenses;
    
    return {
        sales: sales,
        expenses: expenses,
        profit: profit,
        transactions: transactions,
        date: dateStr
    };
}

// Obtener estadísticas semanales
function getWeeklyStats() {
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0 = Domingo, 1 = Lunes, etc.
    const startDate = new Date(today);
    startDate.setDate(today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1)); // Lunes de esta semana
    
    const weekDaysData = [];
    let weeklySales = 0;
    let weeklyExpenses = 0;
    
    for (let i = 0; i < 7; i++) {
        const currentDay = new Date(startDate);
        currentDay.setDate(startDate.getDate() + i);
        
        const dayStats = getDailyStats(currentDay);
        weeklySales += dayStats.sales;
        weeklyExpenses += dayStats.expenses;
        
        weekDaysData.push({
            date: currentDay,
            ...dayStats
        });
    }
    
    const weeklyProfit = weeklySales - weeklyExpenses;
    
    return {
        sales: weeklySales,
        expenses: weeklyExpenses,
        profit: weeklyProfit,
        days: weekDaysData,
        startDate: startDate,
        endDate: today
    };
}

// Obtener estadísticas mensuales
function getMonthlyStats(year = new Date().getFullYear(), month = new Date().getMonth()) {
    const startDate = new Date(year, month, 1);
    const endDate = new Date(year, month + 1, 0);
    
    const transactions = getTransactionsByDateRange(startDate, endDate);
    
    const sales = transactions
        .filter(trans => trans.tipo_transaccion === 'Venta')
        .reduce((sum, trans) => sum + trans.monto_transacion, 0);
    
    const expenses = transactions
        .filter(trans => trans.tipo_transaccion === 'Gasto')
        .reduce((sum, trans) => sum + trans.monto_transacion, 0);
    
    const profit = sales - expenses;
    
    return {
        sales: sales,
        expenses: expenses,
        profit: profit,
        transactions: transactions,
        startDate: startDate,
        endDate: endDate
    };
}

// Eliminar transacción
function deleteTransaction(transactionId) {
    const data = getStorageData();
    const user = getCurrentUser();
    
    if (!user) {
        return { success: false, message: 'No hay usuario activo' };
    }
    
    // Buscar la transacción
    const transactionIndex = data.transacciones.findIndex(trans => trans.id_transaccion === transactionId);
    
    if (transactionIndex === -1) {
        return { success: false, message: 'Transacción no encontrada' };
    }
    
    // Verificar permisos (solo administradores pueden eliminar transacciones de otros)
    const transaction = data.transacciones[transactionIndex];
    if (user.rol !== 'Administrador' && transaction.id_usuario !== user.id) {
        return { success: false, message: 'No tienes permiso para eliminar esta transacción' };
    }
    
    // Eliminar transacción
    data.transacciones.splice(transactionIndex, 1);
    saveStorageData(data);
    
    return { success: true, message: 'Transacción eliminada exitosamente' };
}
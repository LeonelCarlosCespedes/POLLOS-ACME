// Verificar que el usuario esté logueado
document.addEventListener("DOMContentLoaded", function() {
    const usuario = JSON.parse(localStorage.getItem("usuario"));
    
    if (!usuario) {
        alert("⚠️ Debes iniciar sesión primero.");
        window.location.href = "index.html";
        return;
    }

    // Cargar dashboard por defecto
    cargarSeccion('dashboard');
});

// Función para cargar diferentes secciones
function cargarSeccion(seccion) {
    const content = document.getElementById("admin-content");
    
    // Actualizar menú activo
    document.querySelectorAll(".admin-sidebar li").forEach(li => li.classList.remove("active"));
    event.target.classList.add("active");

    switch(seccion) {
        case 'dashboard':
            content.innerHTML = `
                <div class="admin-header">
                    <h1>📊 Dashboard</h1>
                    <span>Bienvenido, ${JSON.parse(localStorage.getItem("usuario")).nombre}</span>
                </div>
                <div class="stats-grid" id="stats-dashboard">
                    <div class="stat-card">
                        <h3>Ventas Hoy</h3>
                        <div class="stat-value" id="stat-ventas-hoy">Cargando...</div>
                        <div class="stat-label">Monto total del día</div>
                    </div>
                    <div class="stat-card">
                        <h3>Productos Activos</h3>
                        <div class="stat-value" id="stat-productos">Cargando...</div>
                        <div class="stat-label">En el menú</div>
                    </div>
                    <div class="stat-card">
                        <h3>Usuarios</h3>
                        <div class="stat-value" id="stat-usuarios">Cargando...</div>
                        <div class="stat-label">Empleados registrados</div>
                    </div>
                    <div class="stat-card">
                        <h3>Clientes</h3>
                        <div class="stat-value" id="stat-clientes">Cargando...</div>
                        <div class="stat-label">Registrados</div>
                    </div>
                </div>
            `;
            cargarDashboard();
            break;

        case 'productos':
            content.innerHTML = `
                <div class="admin-header">
                    <h1> Gestión de Productos</h1>
                    <button class="btn-add" onclick="alert('Función en desarrollo')">+ Agregar Producto</button>
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Tipo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-productos">
                            <tr><td colspan="6" style="text-align:center;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            `;
            cargarProductos();
            break;

        case 'usuarios':
            content.innerHTML = `
                <div class="admin-header">
                    <h1> Gestión de Usuarios</h1>
                    <button class="btn-add" onclick="alert('Función en desarrollo')">+ Agregar Usuario</button>
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-usuarios">
                            <tr><td colspan="5" style="text-align:center;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            `;
            cargarUsuarios();
            break;

        case 'clientes':
            content.innerHTML = `
                <div class="admin-header">
                    <h1>👤 Gestión de Clientes</h1>
                    <button class="btn-add" onclick="alert('Función en desarrollo')">+ Agregar Cliente</button>
                </div>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-clientes">
                            <tr><td colspan="5" style="text-align:center;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            `;
            cargarClientes();
            break;

        case 'reportes':
            window.location.href = "reportes.html";
            break;
    }
}

// Cargar datos del dashboard
async function cargarDashboard() {
    try {
        // Ventas del día
        const ventas = await obtenerDatos("reportes.php?tipo=ventas_dia");
        if (ventas && ventas.data) {
            document.getElementById("stat-ventas-hoy").textContent = `S/ ${parseFloat(ventas.data.monto_total || 0).toFixed(2)}`;
        }

        // Productos
        const productos = await obtenerDatos("productos.php");
        if (productos && productos.data) {
            document.getElementById("stat-productos").textContent = productos.data.length;
        }

        // Usuarios
        const usuarios = await obtenerDatos("usuarios.php");
        if (usuarios && usuarios.data) {
            document.getElementById("stat-usuarios").textContent = usuarios.data.length;
        }

        // Clientes
        const clientes = await obtenerDatos("clientes.php");
        if (clientes && clientes.data) {
            document.getElementById("stat-clientes").textContent = clientes.data.length;
        }
    } catch (error) {
        console.error("Error al cargar dashboard:", error);
    }
}

// Cargar productos en la tabla
async function cargarProductos() {
    const productos = await obtenerDatos("productos.php");
    const tbody = document.getElementById("tabla-productos");
    
    if (productos && productos.data && productos.data.length > 0) {
        tbody.innerHTML = productos.data.map(p => `
            <tr>
                <td>${p.id_producto}</td>
                <td>${p.nombre}</td>
                <td>${p.categoria}</td>
                <td>S/ ${parseFloat(p.precio).toFixed(2)}</td>
                <td>${p.tipo}</td>
                <td>
                    <button class="btn-action btn-edit">Editar</button>
                    <button class="btn-action btn-delete">Eliminar</button>
                </td>
            </tr>
        `).join("");
    } else {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No hay productos</td></tr>';
    }
}

// Cargar usuarios en la tabla
async function cargarUsuarios() {
    const usuarios = await obtenerDatos("usuarios.php");
    const tbody = document.getElementById("tabla-usuarios");
    
    if (usuarios && usuarios.data && usuarios.data.length > 0) {
        tbody.innerHTML = usuarios.data.map(u => `
            <tr>
                <td>${u.id_usuario}</td>
                <td>${u.nombre_completo}</td>
                <td>${u.username}</td>
                <td><span class="badge badge-info">${u.rol}</span></td>
                <td>
                    <button class="btn-action btn-edit">Editar</button>
                    <button class="btn-action btn-delete">Eliminar</button>
                </td>
            </tr>
        `).join("");
    } else {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay usuarios</td></tr>';
    }
}

// Cargar clientes en la tabla
async function cargarClientes() {
    const clientes = await obtenerDatos("clientes.php");
    const tbody = document.getElementById("tabla-clientes");
    
    if (clientes && clientes.data && clientes.data.length > 0) {
        tbody.innerHTML = clientes.data.map(c => `
            <tr>
                <td>${c.id_cliente}</td>
                <td>${c.tipo_documento}: ${c.numero_documento}</td>
                <td>${c.nombre_razon_social}</td>
                <td>${c.telefono || '-'}</td>
                <td>
                    <button class="btn-action btn-edit">Editar</button>
                    <button class="btn-action btn-delete">Eliminar</button>
                </td>
            </tr>
        `).join("");
    } else {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay clientes</td></tr>';
    }
}

// Cerrar sesión
function cerrarSesion() {
    if (confirm("¿Estás seguro de que deseas cerrar sesión?")) {
        localStorage.removeItem("usuario");
        window.location.href = "index.html";
    }
}
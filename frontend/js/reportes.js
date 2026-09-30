document.addEventListener("DOMContentLoaded", function () {
    // Verificar sesión
    const usuario = JSON.parse(localStorage.getItem("usuario"));
    if (!usuario) {
        alert("⚠️ Debes iniciar sesión primero.");
        window.location.href = "index.html";
        return;
    }

    // Establecer fechas por defecto (hoy)
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById("fecha-inicio").value = hoy;
    document.getElementById("fecha-fin").value = hoy;

    // Cargar reportes al iniciar
    cargarReportes();
});

async function cargarReportes() {
    const fechaInicio = document.getElementById("fecha-inicio").value;
    const fechaFin = document.getElementById("fecha-fin").value;

    if (!fechaInicio || !fechaFin) {
        alert("Por favor selecciona un rango de fechas.");
        return;
    }

    // Cargar ventas del período
    const ventas = await obtenerDatos(`reportes.php?tipo=ventas_periodo&inicio=${fechaInicio}&fin=${fechaFin}`);

    if (ventas && ventas.data) {
        let totalMonto = 0;
        let totalVentas = 0;

        ventas.data.forEach(v => {
            totalMonto += parseFloat(v.monto_total);
            totalVentas += parseInt(v.total_ventas);
        });

        const promedio = totalVentas > 0 ? totalMonto / totalVentas : 0;

        document.getElementById("total-ventas").textContent = `S/ ${totalMonto.toFixed(2)}`;
        document.getElementById("num-ventas").textContent = totalVentas;
        document.getElementById("promedio-venta").textContent = `S/ ${promedio.toFixed(2)}`;
    }

    // Cargar top productos
    const topProductos = await obtenerDatos("reportes.php?tipo=productos_top");
    const tbodyTop = document.getElementById("tabla-top-productos");

    if (topProductos && topProductos.data && topProductos.data.length > 0) {
        tbodyTop.innerHTML = topProductos.data.map((p, index) => `
            <tr>
                <td><strong>${index + 1}</strong></td>
                <td>${p.nombre}</td>
                <td>${p.total_vendido}</td>
                <td>S/ ${parseFloat(p.monto_total).toFixed(2)}</td>
            </tr>
        `).join("");
    } else {
        tbodyTop.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888;">No hay datos disponibles</td></tr>';
    }

    // Cargar métodos de pago
    const metodosPago = await obtenerDatos("reportes.php?tipo=metodos_pago");
    const tbodyMetodos = document.getElementById("tabla-metodos-pago");

    if (metodosPago && metodosPago.data && metodosPago.data.length > 0) {
        // Calcular total para porcentajes
        const totalPagos = metodosPago.data.reduce((sum, m) => sum + parseFloat(m.total), 0);

        tbodyMetodos.innerHTML = metodosPago.data.map(m => {
            const porcentaje = totalPagos > 0 ? (parseFloat(m.total) / totalPagos * 100).toFixed(1) : 0;
            return `
                <tr>
                    <td><strong>${m.metodo_pago}</strong></td>
                    <td>${m.cantidad}</td>
                    <td>S/ ${parseFloat(m.total).toFixed(2)}</td>
                    <td>${porcentaje}%</td>
                </tr>
            `;
        }).join("");
    } else {
        tbodyMetodos.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888;">No hay datos disponibles</td></tr>';
    }
}
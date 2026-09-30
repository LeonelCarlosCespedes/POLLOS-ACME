let carrito = [];

// Imagen por defecto como SVG inline (no depende de servicios externos)
const IMAGEN_DEFAULT = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='120' viewBox='0 0 150 120'%3E%3Crect fill='%23d32f2f' width='150' height='120'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='40' fill='white'%3E🐔%3C/text%3E%3C/svg%3E";

async function cargarMenu() {
    const contenedor = document.getElementById("menu-container");
    contenedor.innerHTML = "<h2>Cargando menu...</h2>";

    const respuesta = await obtenerDatos("productos.php");

    if (respuesta && respuesta.status === "success") {
        const productos = respuesta.data;
        contenedor.innerHTML = "";

        if (productos.length === 0) {
            contenedor.innerHTML = "<p>No hay productos disponibles.</p>";
            return;
        }

        productos.forEach(prod => {
            const tarjeta = document.createElement("div");
            tarjeta.className = "producto-tarjeta";

            const imagenSrc = prod.imagen
                ? "../" + prod.imagen
                : IMAGEN_DEFAULT;

            const nombreEscapado = prod.nombre.replace(/'/g, "\\'");

            tarjeta.innerHTML = `
                <img src="${imagenSrc}" alt="${prod.nombre}" class="producto-imagen" 
                     onerror="this.src='${IMAGEN_DEFAULT}'">
                <h3>${prod.nombre}</h3>
                <span class="categoria">${prod.categoria}</span>
                <p class="precio">Bs. ${parseFloat(prod.precio).toFixed(2)}</p>
                <button onclick="agregarAlCarrito(${prod.id_producto}, '${nombreEscapado}', ${prod.precio})">
                    Agregar +
                </button>
            `;
            contenedor.appendChild(tarjeta);
        });
    }
}

function agregarAlCarrito(id, nombre, precio) {
    const existe = carrito.find(item => item.id === id);

    if (existe) {
        existe.cantidad++;
    } else {
        carrito.push({ id, nombre, precio, cantidad: 1 });
    }

    actualizarCarrito();
}

function disminuirCantidad(id) {
    const item = carrito.find(item => item.id === id);
    if (item) {
        item.cantidad--;
        if (item.cantidad <= 0) {
            carrito = carrito.filter(item => item.id !== id);
        }
    }
    actualizarCarrito();
}

function aumentarCantidad(id) {
    const item = carrito.find(item => item.id === id);
    if (item) {
        item.cantidad++;
    }
    actualizarCarrito();
}

function eliminarProducto(id) {
    carrito = carrito.filter(item => item.id !== id);
    actualizarCarrito();
}

function actualizarCarrito() {
    const contenedor = document.getElementById("carrito-items");
    const totalElemento = document.getElementById("total-venta");

    contenedor.innerHTML = "";

    let total = 0;

    if (carrito.length === 0) {
        contenedor.innerHTML = "<p style='color:#888; text-align:center;'>El carrito esta vacio</p>";
        totalElemento.textContent = "Bs. 0.00";
        return;
    }

    carrito.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        const fila = document.createElement("div");
        fila.className = "carrito-item";
        fila.innerHTML = `
            <div class="item-info">
                <strong>${item.nombre}</strong>
                <span class="item-precio">Bs. ${item.precio.toFixed(2)}</span>
            </div>
            <div class="item-controles">
                <button class="btn-cantidad" onclick="disminuirCantidad(${item.id})">-</button>
                <span class="item-cantidad">${item.cantidad}</span>
                <button class="btn-cantidad" onclick="aumentarCantidad(${item.id})">+</button>
                <button class="btn-eliminar" onclick="eliminarProducto(${item.id})">🗑️</button>
            </div>
            <div class="item-subtotal">Bs. ${subtotal.toFixed(2)}</div>
        `;
        contenedor.appendChild(fila);
    });

    totalElemento.textContent = "Bs. " + total.toFixed(2);
}

function procesarVenta() {
    if (carrito.length === 0) {
        alert("El carrito esta vacio");
        return;
    }

    const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    document.getElementById("modal-total").textContent = "Bs. " + total.toFixed(2);
    document.getElementById("modal-cobro").style.display = "flex";
}

function cerrarModal() {
    document.getElementById("modal-cobro").style.display = "none";
}

async function confirmarVenta() {
    const metodoPago = document.getElementById("metodo-pago").value;
    const tipoPedido = document.getElementById("tipo-pedido").value;
    const usuario = JSON.parse(localStorage.getItem("usuario"));

    const datosVenta = {
        id_cliente: null,
        id_usuario: usuario ? usuario.id : 1,
        tipo_pedido: tipoPedido,
        metodo_pago: metodoPago,
        productos: carrito.map(item => ({
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            cantidad: item.cantidad,
            observacion: null
        }))
    };

    console.log("Enviando datos:", datosVenta);

    try {
        const respuesta = await fetch("http://172.16.4.227:80/pollos_acme/api/pedidos.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(datosVenta)
        });

        console.log("Status:", respuesta.status);

        const textoRespuesta = await respuesta.text();
        console.log("Respuesta:", textoRespuesta);

        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            console.error("JSON invalido:", textoRespuesta);
            alert("Error: respuesta invalida del servidor\n\n" + textoRespuesta.substring(0, 300));
            return;
        }

        if (resultado.status === "success") {
            alert("Venta registrada!\n\nID: " + resultado.id_pedido + "\nTotal: Bs. " + resultado.total.toFixed(2));
            carrito = [];
            actualizarCarrito();
            cerrarModal();
        } else {
            alert("Error: " + resultado.message);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Error de conexion: " + error.message);
    }
}

function limpiarCarrito() {
    if (carrito.length === 0) return;
    if (confirm("Limpiar carrito?")) {
        carrito = [];
        actualizarCarrito();
    }
}

document.addEventListener("DOMContentLoaded", cargarMenu);
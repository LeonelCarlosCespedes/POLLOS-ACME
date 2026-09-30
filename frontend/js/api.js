// Configuración base de la API
const API_URL = "http://172.16.4.227:80/pollos_acme/api/";

// Función genérica para hacer peticiones GET
async function obtenerDatos(endpoint) {
    try {
        const respuesta = await fetch(API_URL + endpoint);
        if (!respuesta.ok) throw new Error("Error en la red");
        const datos = await respuesta.json();
        return datos;
    } catch (error) {
        console.error("Error al conectar con la API:", error);
        alert("⚠️ Error de conexión con el servidor. Verifica que XAMPP esté encendido.");
        return null;
    }
}
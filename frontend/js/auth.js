// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");
    const errorMessage = document.getElementById("error-message");

    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();

        // Validar campos vacíos
        if (!username || !password) {
            mostrarError("Por favor completa todos los campos.");
            return;
        }

        try {
            const respuesta = await fetch("http://172.16.4.227:80/pollos_acme/api/auth.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ username, password })
            });

            const resultado = await respuesta.json();

            if (resultado.status === "success") {
                // Guardar datos del usuario en localStorage
                localStorage.setItem("usuario", JSON.stringify(resultado.usuario));

                // Redirigir al POS
                alert(`¡Bienvenido ${resultado.usuario.nombre}!`);
                window.location.href = "pos.html";
            } else {
                mostrarError(resultado.message);
            }
        } catch (error) {
            console.error("Error al iniciar sesión:", error);
            mostrarError("Error de conexión con el servidor.");
        }
    });

    function mostrarError(mensaje) {
        errorMessage.textContent = mensaje;
        errorMessage.style.display = "block";
        setTimeout(() => {
            errorMessage.style.display = "none";
        }, 3000);
    }
});
<?php
// 1. CONEXION Y CREACION DE BASE DE DATOS

$conexion = new mysqli("127.0.0.1", "root", "", "cafeteria", 3306);

if ($conexion->connect_error) {
    die("Error de conexion: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

// Crear base de datos
$sqlBD = "CREATE DATABASE IF NOT EXISTS cafeteria";

if (!$conexion->query($sqlBD)) {
    die("Error al crear la base de datos: " . $conexion->error);
}

// Seleccionar base de datos
$conexion->select_db("cafeteria");

// Crear tabla de clientes
$sqlTabla = "CREATE TABLE IF NOT EXISTS cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL
)";

if (!$conexion->query($sqlTabla)) {
    die("Error al crear la tabla: " . $conexion->error);
}

// 2. FUNCION PARA LIMPIAR DATOS

function limpiar($dato) {
    return htmlspecialchars($dato, ENT_QUOTES, "UTF-8");
}

// 3. PROCESAMIENTO DE FORMULARIOS

$mensajeSistema = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // GUARDAR CLIENTE

    if (isset($_POST["accion"]) && $_POST["accion"] === "guardar") {

    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $mensaje = trim($_POST["mensaje"] ?? "");

    if ($nombre === "" || $correo === "" || $mensaje === "") {

        $mensajeSistema = "Todos los campos son obligatorios.";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensajeSistema = "Correo no valido.";

    } else {

        $sql = "INSERT INTO cliente (nombre, correo, mensaje)
                VALUES (?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $mensajeSistema = "Error SQL: " . $conexion->error;

        } else {

            $stmt->bind_param("sss", $nombre, $correo, $mensaje);

            if ($stmt->execute()) {

                $mensajeSistema = "Registro guardado. ID: " . $conexion->insert_id;

            } else {

                $mensajeSistema = "Error al guardar: " . $stmt->error;

            }

            $stmt->close();
        }
    }
}

    // ELIMINAR CLIENTE

    elseif (isset($_POST["accion"]) && $_POST["accion"] === "eliminar") {

        $correo = trim($_POST["correo"] ?? "");

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

            $mensajeSistema = "Ingresa un correo electronico valido.";

        } else {

            $sql = "DELETE FROM cliente WHERE correo = ? LIMIT 1";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {

                $mensajeSistema = "Error al preparar la eliminacion.";

            } else {

                $stmt->bind_param("s", $correo);

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {
                        $mensajeSistema = "Registro eliminado correctamente.";
                    } else {
                        $mensajeSistema = "No se encontro el correo indicado.";
                    }

                } else {

                    $mensajeSistema = "Error al eliminar el registro.";
                }

                $stmt->close();
            }
        }
    }
}

// 4. CONSULTAR REGISTROS DE CLIENTES
$sqlConsulta = "SELECT * FROM cliente ORDER BY id DESC";

$resultado = $conexion->query($sqlConsulta);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cafetería Escolar</title>

    <style>

        body {
            font-family: Arial;
            margin: 20px;
            background-color: #ee8080;
        }

        header, footer {
            background-color: #830404;
            color: white;
            text-align: center;
            padding: 15px;
        }

        .titulo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .titulo img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
            border: none;
            margin: 0;
        }

        .titulo h1 {
            margin: 0;
        }

        .titulo p {
            margin: 8px 0 0;
        }

        nav {
            background-color: #ddd;
            padding: 10px;
            text-align: center;
        }

        nav a {
            margin: 10px;
            text-decoration: none;
            color: #830404;
            font-weight: bold;
        }

        section {
            background-color: white;
            padding: 15px;
            margin-top: 10px;
        }

        /* TABLA */

        table {
            width: 70%;
            margin: 15px auto;
            border-collapse: collapse;
        }

        th {
            background-color: #8a2626;
            color: white;
            padding: 10px;
        }

        td {
            border: 1px solid #791919;
            padding: 8px;
            text-align: center;
        }

        /* PRODUCTOS */

        .productos {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .producto {
            width: 220px;
            background-color: #fff5f5;
            border: 2px solid #d82929;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .producto img {
            width: 150px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: none;
            display: block;
            margin: 10px auto;
        }

        .producto h3 {
            color: #830404;
        }

        .producto strong {
            font-size: 20px;
            color: #d82929;
        }

        /* BOTONES */

        button {
            background-color: #830404;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #d82929;
        }

        /* CARRITO */

        #carrito {
            background-color: #fff5f5;
            border: 3px solid #830404;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        #listaPedido {
            padding: 0;
        }

        #listaPedido li {
            list-style: none;
            background-color: white;
            margin: 8px;
            padding: 10px;
            border-radius: 5px;
        }

        #total {
            color: #830404;
            font-size: 24px;
        }

        .vaciar {
            background-color: #555;
            margin-top: 10px;
        }

        /* PROMOCION */

        .promocion {
            background-color: #ffe0e0;
            border: 3px dashed #830404;
            text-align: center;
            padding: 20px;
        }

        .promocion h3 {
            font-size: 28px;
            color: #d82929;
        }

        /* HORARIO */

        .horario {
            text-align: center;
            border-left: 6px solid #830404;
        }

        /* AVISO */

        .aviso {
            background-color: #fff0c2;
            text-align: center;
            border-radius: 10px;
        }

        /* FORMULARIO */

        form {
            width: 350px;
            max-width: 90%;
            margin: auto;
            padding: 20px;
            background-color: #eee;
            border-radius: 10px;
        }

        input, textarea {
            width: 95%;
            padding: 9px;
            margin-top: 5px;
        }

        textarea {
            height: 80px;
        }

        /* MENSAJES DEL SISTEMA */

        .mensajeSistema {
            background-color: #fff0c2;
            color: #830404;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            margin: 10px 0;
            border-radius: 8px;
        }

    </style>

</head>

<body>

<header>

    <div class="titulo">

        <img src="cafeteria.jpg" alt="Cafetería">

        <div>
            <h1>Cafetería Escolar</h1>
            <p>Alimentos y bebidas para estudiantes</p>
        </div>

    </div>

</header>

<nav>

    <a href="#menu">Menú</a>
    <a href="#productos">Productos</a>
    <a href="#carrito">Pedido 🛒</a>
    <a href="#contacto">Contacto</a>

</nav>

<main>

<!-- MENSAJE DEL SISTEMA -->

<?php if ($mensajeSistema !== ""): ?>

    <div class="mensajeSistema">
        <?php echo limpiar($mensajeSistema); ?>
    </div>

<?php endif; ?>


<!-- MENÚ -->

<section id="menu">

    <h2>Menú</h2>

    <table>

        <tr>
            <th>Producto</th>
            <th>Precio</th>
        </tr>

        <tr>
            <td>Hamburguesa</td>
            <td>$45</td>
        </tr>

        <tr>
            <td>Jugo</td>
            <td>$20</td>
        </tr>

        <tr>
            <td>Sandwich</td>
            <td>$30</td>
        </tr>

    </table>

</section>


<!-- PRODUCTOS -->

<section id="productos">

    <h2>Productos destacados</h2>

    <div class="productos">

        <div class="producto">

            <img src="hamburguesa.jpg" alt="Hamburguesa">

            <h3>🍔 Hamburguesa</h3>

            <p>
                Hamburguesa con queso,
                lechuga y jitomate.
            </p>

            <strong>$45</strong>

            <br><br>

            <button type="button"
                onclick="agregarProducto('Hamburguesa', 45)">

                Agregar al pedido

            </button>

        </div>


        <div class="producto">

            <img src="jugo.jpg" alt="Jugo">

            <h3>🥤 Jugo natural</h3>

            <p>
                Jugo fresco de naranja.
            </p>

            <strong>$20</strong>

            <br><br>

            <button type="button"
                onclick="agregarProducto('Jugo natural', 20)">

                Agregar al pedido

            </button>

        </div>


        <div class="producto">

            <img src="sandwich.jpg" alt="Sandwich">

            <h3>🥪 Sandwich</h3>

            <p>
                Sandwich de jamón,
                queso y verduras.
            </p>

            <strong>$30</strong>

            <br><br>

            <button type="button"
                onclick="agregarProducto('Sandwich', 30)">

                Agregar al pedido

            </button>

        </div>

    </div>

</section>


<!-- CARRITO -->

<section id="carrito">

    <h2>🛒 Mi pedido</h2>

    <ul id="listaPedido">

        <li>No hay productos en tu pedido.</li>

    </ul>

    <h3 id="total">
        Total: $0
    </h3>

    <button type="button" class="vaciar"
        onclick="vaciarPedido()">

        Vaciar pedido

    </button>

</section>


<!-- PROMOCIÓN -->

<section class="promocion">

    <h2>🔥 Promoción del día 🔥</h2>

    <p>🍔 Hamburguesa + 🥤 Jugo natural</p>

    <h3>¡Solo $55!</h3>

</section>


<!-- HORARIO -->

<section class="horario">

    <h2>⏰ Horario de atención</h2>

    <p><strong>Lunes a Viernes</strong></p>

    <p>7:00 AM - 3:00 PM</p>

    <p>📍 Cafetería escolar</p>

</section>


<!-- AVISO -->

<section class="aviso">

    <h2>📢 Aviso</h2>

    <p>
        Recuerda conservar limpia la cafetería
        y depositar la basura en su lugar.
    </p>

</section>


<!-- CONTACTO -->

<section id="contacto">

    <h2>Contacto</h2>

    <form id="formulario"
        action="cafeteria.php"
        method="POST">

        <input type="hidden" name="accion" value="guardar">

        <label for="nombre">Nombre:</label>

        <input
            type="text"
            id="nombre"
            name="nombre"
            placeholder="Escribe tu nombre"
            required
        >

        <br><br>

        <label for="correo">Correo:</label>

        <input
            type="email"
            id="correo"
            name="correo"
            placeholder="Escribe tu correo"
            required
        >

        <br><br>

        <label for="mensajeTexto">Mensaje:</label>

        <textarea
            id="mensajeTexto"
            name="mensaje"
            placeholder="Escribe tu mensaje"
            required
        ></textarea>

        <br><br>

        <button type="submit">
            Guardar
        </button>

    </form>

</section>


<!-- ELIMINAR REGISTRO -->

<section>

    <h2>Eliminar registro</h2>

    <form id="formularioEliminar"
        action="cafeteria.php"
        method="POST">

        <input type="hidden" name="accion" value="eliminar">

        <label for="correoEliminar">
            Correo electrónico:
        </label>

        <input
            type="email"
            id="correoEliminar"
            name="correo"
            placeholder="Escribe el correo"
            required
        >

        <br><br>

        <button
            type="submit"
            onclick="return confirm('¿Deseas eliminar este registro?')">

            Eliminar

        </button>

    </form>

</section>

</main>


<footer>

    <p>Cafetería Escolar © 2026</p>

</footer>


<!-- REGISTROS DE MYSQL -->

<section>

    <h2>Registros de clientes</h2>

    <div class="registros">

        <table>

            <thead>

                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Mensaje</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($fila = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo limpiar($fila["nombre"]); ?>
                            </td>

                            <td>
                                <?php echo limpiar($fila["correo"]); ?>
                            </td>

                            <td>
                                <?php echo limpiar($fila["mensaje"]); ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="3">
                            No hay registros guardados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- JAVASCRIPT -->

<script>

    let pedido = [];

    // AGREGAR PRODUCTOS

    function agregarProducto(nombre, precio) {

        pedido.push({
            nombre: nombre,
            precio: precio
        });

        mostrarPedido();

    }


    // MOSTRAR PEDIDO

    function mostrarPedido() {

        let lista = document.getElementById("listaPedido");

        let total = document.getElementById("total");

        lista.innerHTML = "";

        let suma = 0;

        if (pedido.length === 0) {

            lista.innerHTML =
                "<li>No hay productos en tu pedido.</li>";

            total.innerHTML = "Total: $0";

            return;

        }

        pedido.forEach(function(producto) {

            let elemento = document.createElement("li");

            elemento.textContent =
                producto.nombre + " - $" + producto.precio;

            lista.appendChild(elemento);

            suma += producto.precio;

        });

        total.innerHTML = "Total: $" + suma;

    }


    // VACIAR PEDIDO

    function vaciarPedido() {

        pedido = [];

        mostrarPedido();

    }


    // VALIDAR FORMULARIO DE CONTACTO

    document.getElementById("formulario").addEventListener("submit", function(event) {

        let nombre = document.getElementById("nombre").value.trim();

        let correo = document.getElementById("correo").value.trim();

        let mensaje = document.getElementById("mensajeTexto").value.trim();

        if (nombre === "" || correo === "" || mensaje === "") {

            event.preventDefault();

            alert("Por favor, completa todos los campos.");

            return;

        }

    });

</script>

<?php

$conexion->close();
?>

</body>
</html>
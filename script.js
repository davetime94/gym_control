const secreto = document.getElementById("secreto");
const contactinfo = { email: 'davetime94@gmail.com', numero: 3312345678 };
const menu_actions = document.getElementById("menu_actions");
const menu_buscar_clientes = document.getElementById("menu_buscar_clientes");
const usuario = "David";
const addmemberdiv = document.getElementById("addmemberdiv");

// 1. Obtener la fecha actual
const fecha = new Date();

// 2. Sumar 30 días
const fecha_30_dias = fecha.setDate(fecha.getDate() + 30);



const logs = [];

function mostrar_addmembers() {

    addmemberdiv.style.transform = "translateX(0px)";
    addmemberdiv.style.transition = ".4s";
}

function esconder_addmembers() {
    addmemberdiv.style.transform = "translateX(400px)";
    addmemberdiv.style.transition = ".4s";



}

function mostrar_menu() {

    menu_actions.style.transform = "translateX(0px)";
    menu_actions.style.transition = ".4s";
}

function esconder_menu() {
    menu_actions.style.transform = "translateX(400px)";
    menu_actions.style.transition = ".4s";
    menu_buscar_clientes.style.transform = "translateX(700px)";
    menu_buscar_clientes.style.transition = ".4s";


}



function buscar_clientes() {
    menu_buscar_clientes.style.transform = "translateX(0px)";
    menu_buscar_clientes.style.transition = ".4s";
}




function mostrar() {
    console.log(addmemberdiv);
}
mostrar();




estados.forEach(el => {
    const texto = el.innerText.trim();

    if (texto === 'Activa') {
        el.classList("activo");

    } else if (texto === 'Vencida') {
        el.style.color = "orange";
    } else if (texto === 'Cancelada') {
        el.style.color = "red";
    }
});


//Para ña tabla de logs
const buscador = document.getElementById("buscador");
const filtroUsuario = document.getElementById("filtroUsuario");
const filtroUserID = document.getElementById("filtroUserID");
const fechaDesde = document.getElementById("fechaDesde");
const fechaHasta = document.getElementById("fechaHasta");
const filtroAccion = document.getElementById("filtroAccion");
const contador = document.getElementById("contador");

function filtrar() {

    const texto = buscador.value.toLowerCase();
    const usuario = filtroUsuario.value.toLowerCase();
    const userID = filtroUserID.value;
    const desde = fechaDesde.value;
    const hasta = fechaHasta.value;
    const accion = filtroAccion.value.toLowerCase();

    const filas = document.querySelectorAll("#tablaLogs tbody tr");

    let visibles = 0;

    filas.forEach(fila => {

        const contenido = fila.innerText.toLowerCase();
        const nombre = fila.querySelector(".usuario").innerText.toLowerCase();
        const descripcion = fila.querySelector(".descripcion").innerText.toLowerCase();
        const id = fila.querySelector(".userid").innerText;
        const fecha = fila.querySelector(".fecha").innerText.split(" ")[0];

        let mostrar = true;

        // 🔍 búsqueda general
        if (texto && !contenido.includes(texto)) mostrar = false;

        // 👤 usuario
        if (usuario && !nombre.includes(usuario)) mostrar = false;

        // 🆔 user id
        if (userID && id !== userID) mostrar = false;

        // 📅 fechas
        if (desde && fecha < desde) mostrar = false;
        if (hasta && fecha > hasta) mostrar = false;

        // ⚡ acción (texto dentro de descripción)
        if (accion && !descripcion.includes(accion)) mostrar = false;

        fila.style.display = mostrar ? "" : "none";

        if (mostrar) visibles++;
    });

    contador.innerText = "Resultados: " + visibles;
}

// eventos
document.querySelectorAll("input, select").forEach(el => {
    el.addEventListener("input", filtrar);
});

// limpiar
function limpiarFiltros() {
    buscador.value = "";
    filtroUsuario.value = "";
    filtroUserID.value = "";
    fechaDesde.value = "";
    fechaHasta.value = "";
    filtroAccion.value = "";
    filtrar();
}

// inicial
filtrar();



/**SCRIPT PARA LA TABLA DE MANAGER */


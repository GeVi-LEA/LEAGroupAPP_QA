var btnAgregar = document.getElementById("btnAgregar");
var btnGen = document.getElementById("btnGenerar");
var numDetalles =
	document.getElementById("numDetalles") == undefined
		? null
		: document.getElementById("numDetalles").value;
var seccionDesc =
	document.getElementById("seccionDescripcion") == undefined
		? null
		: document.getElementById("seccionDescripcion");
var formRequisicion =
	document.getElementById("formRequisicion") == undefined
		? null
		: document.getElementById("formRequisicion");
var divEspecif =
	document.getElementById("divEspecificacion") == undefined
		? null
		: document.getElementById("divEspecificacion");
const buscador = document.querySelector("#buscador");
const buscadorDirectorio = document.querySelector("#buscarDirectorio");
if (formRequisicion != null) {
	var descrEsp = formRequisicion.descEspecif,
		unidEsp = formRequisicion.unidadEspecif,
		cantEsp = formRequisicion.cantEspecif,
		precioEsp = formRequisicion.precioEspecif,
		moneda = formRequisicion.moneda,
		idReq = formRequisicion.id,
		empresa = formRequisicion.empresa,
		solicitud = formRequisicion.solicitud,
		proveedor = formRequisicion.proveedor,
		fechaSolicitud = formRequisicion.fechaSolicitud,
		fechaRequerida = formRequisicion.fechaRequerida;

	if (idReq.value != "" && numDetalles > 0) {
		seccionDesc.style.display = "block";
	}
}
var tablaDesc = document.getElementById("tablaDescripcion");
var abrirCatalogo = document.getElementById("abrirCatalogo");

function agregar(e) {
	e.preventDefault();
	var valid = validarEspecificaciones();
	if (seccionDesc != null && valid) {
		if (seccionDesc.style.display == "") {
			// Agregar valores
			var fila = 1;
			agregarDescripcion(fila);
			seccionDesc.style.display = "block";
		} else {
			var filas = seccionDesc.childElementCount;
			var clon = tablaDesc.cloneNode(true);
			seccionDesc.appendChild(clon);
			var nodo = seccionDesc.lastChild;

			nodo.children[0].children[0].setAttribute(
				"id",
				"descripcion[" + filas + "]"
			);
			nodo.children[0].children[0].setAttribute(
				"name",
				"descripcion[" + filas + "]"
			);
			nodo.children[1].children[0].setAttribute(
				"id",
				"unidad[" + filas + "]"
			);
			nodo.children[1].children[0].setAttribute(
				"name",
				"unidad[" + filas + "]"
			);
			nodo.children[2].children[0].setAttribute(
				"id",
				"cantidad[" + filas + "]"
			);
			nodo.children[2].children[0].setAttribute(
				"name",
				"cantidad[" + filas + "]"
			);
			nodo.children[3].children[0].setAttribute(
				"id",
				"precioUnitario[" + filas + "]"
			);
			nodo.children[3].children[0].setAttribute(
				"name",
				"precioUnitario[" + filas + "]"
			);
			nodo.children[4].setAttribute("id", "idDetalle[" + filas + "]");
			nodo.children[4].setAttribute("name", "idDetalle[" + filas + "]");
			agregarDescripcion(filas);
		}
		descrEsp.value = null;
		cantEsp.value = null;
		precioEsp.value = null;
		unidEsp.value = "";
	}
}

function agregarDescripcion(fila) {
	var descripcion = document.getElementById("descripcion[" + fila + "]"),
		unidad = document.getElementById("unidad[" + fila + "]"),
		cantidad = document.getElementById("cantidad[" + fila + "]"),
		precioUnit = document.getElementById("precioUnitario[" + fila + "]"),
		idDetalle = document.getElementById("idDetalle[" + fila + "]");

	descripcion.value = descrEsp.value;
	unidad.selectedIndex = unidEsp.selectedIndex;
	cantidad.value = cantEsp.value;
	precioUnit.value = precioEsp.value;
	idDetalle.value = "";
}

function generarRequisicion(e) {
	var estatus = document.getElementById("idEstatus").value;
	if (estatus == 5) {
		e.preventDefault();
		var folio = document.getElementById("folioOc").value;
		$.confirm({
			title: "<span class='material-icons i-warning'>warning</span><span>¡Atención!<span>",
			content:
				"La requisición ya cuenta con orden de compra, Se actualizara la orden de compra <b>" +
				folio +
				"</b>",
			type: "orange",
			typeAnimated: true,
			animation: "zoom",
			closeAnimation: "right",
			backgroundDismiss: false,
			backgroundDismissAnimation: "shake",
			buttons: {
				tryAgain: {
					text: "Entendido",
					btnClass: "btn btn-warning",
					action: function () {
						generar(e);
						formRequisicion.submit();
					},
				},
				Cancelar: function () {},
			},
		});
	} else {
		generar(e);
	}
}

function generar(e) {
	var valid = validarFormulario(e);
	if (valid) {
		btnGen.removeAttribute("disabled");
	} else {
		btnGen.setAttribute("disabled", true);
	}
	if (seccionDesc.style.display == "") {
		e.preventDefault();
		valid = true;
		$.confirm({
			title: "<span class='material-icons i-warning'>warning</span><span>¡Atención!<span>",
			content: "Debe de agregar especificación.",
			type: "orange",
			typeAnimated: true,
			animation: "zoom",
			closeAnimation: "right",
			backgroundDismiss: false,
			backgroundDismissAnimation: "shake",
			buttons: {
				tryAgain: {
					text: "Entendido",
					btnClass: "btn btn-warning",
					action: function () {
						btnGen.removeAttribute("disabled");
						divEspecif.classList.add("required");
					},
				},
			},
		});
	} else {
		if (
			descrEsp.value != "" ||
			unidEsp.value != "" ||
			cantEsp.value != "" ||
			precioEsp.value != ""
		) {
			e.preventDefault();
			divEspecif.classList.add("required");
			valid = true;
			$.confirm({
				title: "<span class='material-icons i-warning'>warning</span><span>¡Atención!<span>",
				content:
					"Tiene un detalle sin agregar. ¿Generar/enviar de todos modos?",
				type: "orange",
				typeAnimated: true,
				animation: "zoom",
				closeAnimation: "right",
				backgroundDismiss: false,
				backgroundDismissAnimation: "shake",
				buttons: {
					tryAgain: {
						text: "Aceptar",
						btnClass: "btn btn-warning",
						action: function (e) {
							formRequisicion.submit();
						},
					},
					Cancelar: function (e) {
						btnGen.removeAttribute("disabled");
					},
				},
			});
		}
	}
	if (!valid) {
		var elementHab = seccionDesc.querySelectorAll("input,select");
		for (var i = 0; i < elementHab.length; i++) {
			elementHab[i].removeAttribute("disabled");
		}
		var elementDes = divEspecif.querySelectorAll("input,select");
		for (var i = 0; i < elementDes.length; i++) {
			elementDes[i].setAttribute("disabled", true);
		}
	}
}

function abrirCatalogos(e) {
	e.preventDefault();
	window.open(
		__url__ + "views/catalogos/index.php",
		"Catalogos",
		"width=1450,height=750"
	);
}

function cambiarInputFile(id, span) {
	var pdrs = document.getElementById(id).files[0].name;
	document.getElementById(span).innerHTML = pdrs;
}

function validarFormulario(e) {
	var msg = false;
	if (empresa.value === "" || empresa.value === null) {
		empresa.classList.add("required");
		msg = true;
	}
	if (solicitud.value === "" || solicitud.value === null) {
		solicitud.classList.add("required");
		msg = true;
	}
	if (proveedor.value === "" || proveedor.value === null) {
		proveedor.classList.add("required");
		msg = true;
	}
	if (fechaSolicitud.value === "" || fechaSolicitud.value === null) {
		fechaSolicitud.classList.add("required");
		msg = true;
	}
	if (fechaRequerida.value === "" || fechaRequerida.value === null) {
		fechaRequerida.classList.add("required");
		msg = true;
	}
	if (moneda.value === "" || moneda.value === null) {
		moneda.classList.add("required");
		msg = true;
	}
	if (msg) {
		e.preventDefault();
	}
	return msg;
}

function validarEspecificaciones() {
	var valid = true;
	var validNum = false;
	if (descrEsp.value === "" || descrEsp.value === null) {
		descrEsp.classList.add("required");
		valid = false;
	}
	if (unidEsp.value === "" || unidEsp.value === null) {
		unidEsp.classList.add("required");
		valid = false;
	}
	if (cantEsp.value === "" || cantEsp.value === null) {
		cantEsp.classList.add("required");
		valid = false;
	} else {
		if (!validarNumerico(cantEsp)) {
			valid = false;
			validNum = true;
		}
	}
	if (precioEsp.value === "" || precioEsp.value === null) {
		precioEsp.classList.add("required");
		valid = false;
	} else {
		if (!validarNumerico(precioEsp)) {
			valid = false;
			validNum = true;
		}
	}

	if (validNum) {
		$.confirm({
			title: "<span class='material-icons i-warning'>warning</span><span>¡Atención!<span>",
			content:
				"<strong>Cantidades y precios</strong> deben de ser valores numericos",
			type: "orange",
			typeAnimated: true,
			animation: "zoom",
			closeAnimation: "right",
			backgroundDismiss: false,
			backgroundDismissAnimation: "shake",
			buttons: {
				tryAgain: {
					text: "Entendido",
					btnClass: "btn btn-warning",
					action: function () {},
				},
			},
		});
	}
	return valid;
}

function validarNumerico(campo) {
	var valid = true;
	if (isNumeric(campo.value)) {
		campo.classList.add("required");
		valid = false;
	}
	return valid;
}

function isNumeric(value) {
	const regex = /,/g;
	var num = value.replace(regex, "");
	var valid = isNaN(Number(num));
	return valid;
}

function buscarDatos(e) {
	const dato = new RegExp(e.target.value.toLowerCase()),
		registros = document.querySelectorAll("tbody .tr ");
	registros.forEach((registro) => {
		registro.style.display = "none";
		if (
			registro.childNodes[7]
				.querySelector("strong , a")
				.textContent.toLowerCase()
				.replace(/\s/g, " ")
				.search(dato) != -1
		) {
			registro.style.display = "table-row";
		}
	});
}

function buscarDirectorio(e) {
	const dato = new RegExp(e.target.value.toLowerCase()),
		registros = document.querySelectorAll("#tablaDirectorio tbody tr");
	registros.forEach((registro) => {
		registro.style.display = "none";
		if (
			registro.childNodes[0].textContent
				.toLowerCase()
				.replace(/\s/g, " ")
				.search(dato) != -1
		) {
			registro.style.display = "table-row";
		}
	});
}

buscadorDirectorio == null
	? null
	: buscadorDirectorio.addEventListener("input", buscarDirectorio);
buscador == null ? null : buscador.addEventListener("input", buscarDatos);
btnAgregar == null ? null : btnAgregar.addEventListener("click", agregar);
formRequisicion == null
	? null
	: formRequisicion.addEventListener("submit", generarRequisicion);
abrirCatalogo == null
	? null
	: abrirCatalogo.addEventListener("click", abrirCatalogos);

if (window.history.replaceState) {
	window.history.replaceState(null, null, window.location.href);
}

function padTo2Digits(num) {
	return num.toString().padStart(2, "0");
}
function formatDate(date) {
	return (
		[
			date.getFullYear(),
			padTo2Digits(date.getMonth() + 1),
			padTo2Digits(date.getDate()),
		].join("/") +
		" " +
		[
			padTo2Digits(date.getHours()),
			padTo2Digits(date.getMinutes()),
			padTo2Digits(date.getSeconds()),
		].join(":")
	);
}

const loadingPage = () => {
	let htmlLoading_global = ` 
                        <div>
                              <div class="row">
                                    <div class="col-12">
                                          <i class="fa fa-spinner fa-spin" style="font-size:24px"></i>
                                    </div>
                              </div>
                              <div class="row">
                                    <div class="col-12">
                                          <img src="http://192.168.0.22/LEAGroupAPP/assets/img/logo_lea_260.png"></img>
                                    </div>
                              </div>
                        </div>
                        `;

	Swal.fire({
		grow: "fullscreen",
		title: " ",
		background: "#c3c3c3",
		html: htmlLoading_global,
		showCloseButton: false,
		showConfirmButton: false,
	});
};

function htmlNum(num) {
	return Number(num).toLocaleString("en");
}
function isNumeric(value) {
	const regex = /,/g;
	var num = value.replace(regex, "");
	var valid = !isNaN(Number(num));
	return valid;
}

function formatDateToString(date) {
	return $.datepicker.formatDate("dd/mm/yy", date);
}
function formatDateHourToString(date) {
	return date.toLocaleString();
}

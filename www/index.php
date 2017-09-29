<html>
<head>
<link rel="stylesheet" type="text/css" href="css/main.css"> 

<script src="js/accent-remover.js"></script>
<script src="js/filter.js"></script>
<script src="js/ajax.js"></script>
<script src="js/crel2.js"></script>

<!-- Tokenfield -->
<script type="text/javascript" src="js/libs/jquery/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/libs/jquery/jquery-ui-1.10.3.min.js"></script>
<script type="text/javascript" src="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.js"></script>
<link href="js/libs/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="js/libs/jquery/themes/smoothness/jquery-ui.min.css" type="text/css" rel="stylesheet">
<link href="js/libs/bootstrap-tokenfield/bootstrap-tokenfield.min.css" type="text/css" rel="stylesheet">
</head>
<body>



<div class="buscador">
	Búsqueda: <input id="buscador" type="text" placeholder="Búsqueda" class="form-control"/>
</div>

<div id="inventario"></div>
<div class="clearer"></div>

<button>+ Objeto</button>

<div class="popup" id="popup" style="display:none">
	<div class="bg" id="bg" onclick="closePopup()"></div>
	<div class="closeButton"><button onclick="closePopup()">X</botton></div>
	<div class="msg" id="msg"></div>
</div>



<script>

var tagsArrayAutocomplete = [];

AJAX('php/ajax.php?action=getinventario', null, function(x) {
	var lista = JSON.parse(x.responseText);
	
	FixTags(lista.objetos);
	
	// Dibujar toda la lista en el DOM
	C(document.getElementById("inventario"), DrawInventory(lista));
	
	tagsArrayAutocomplete = GetAutocompleteTags(lista.objetos);
	
	// Preparar buscador
	var buscador = document.getElementById("buscador");
	buscador.onkeyup = function() {
		FilterSearch.process(buscador.value, lista,
			function(DOM) { DOM.style.display = "unset"; },
			function(DOM) { DOM.style.display = "none"; }
		);
	};
	
}, console.log);


function FixTags(objetos) {
	for (var i = 0; i < objetos.length; i++) {
		objetos[i].tags = objetos[i].tags.split(",");
	}
}


function closePopup() {
	document.getElementById("popup").style = "display:none";
	document.getElementById("msg").innerHTML = "";
}
function showPopup(contentsDOM) {
	closePopup();
	document.getElementById("popup").style = "";
	C(document.getElementById("msg"), contentsDOM);
}



function GetAutocompleteTags(objetos) {
	var arr = [];
	for (var i in objetos) {
		arr = arr.concat(objetos[i].tags.filter(function(x){ return arr.indexOf(x) === -1; }));
	}
	return arr
}



function DrawInventory(lista) {
	var contenedor = C("div");
	
	for (var i in lista.objetos) {
		C(contenedor, DrawObjeto(lista.objetos[i], lista));
	}
	
	return contenedor;
}

function DrawObjeto(objeto, lista) {
	var cantidad = GetCantidad(objeto);
	var tags;
	objeto["DOM"] = C("button", ["class", "objeto", "onclick", edit],
		C("div", ["class", "titulo"],
			C("div", ["class", "nombre"], objeto["nombre"]),
			C("div", ["class", "descripcion"], objeto["descripcion"])
		),
		C("div", ["class", "cantidad"], "Cantidad: ", cantidad),
		C("div", ["class", "minimo"], "Mínimo: ", objeto["minimo_alerta"]),
		C("div", ["class", "tags"], "Tags: ", tags = C("span", ["class", "tags-list"]))
	);
	var tagsArr = objeto["tagsArray"] = objeto["tags"].filter(function(x){return x !== ""});
	for (var i in tagsArr) C(tags, C("span", tagsArr[i]));
	
	objeto["DOM"]["objeto"] = objeto;
	var cb = cantidad < parseInt(objeto["minimo_alerta"]) ? AddClass : RemoveClass;
	cb(objeto["DOM"], "alerta");
	
	
	return objeto["DOM"];

	function edit() {
		cantidad = GetCantidad(objeto);
		var actualizarStr = "Actualizar";
		var cantidadROInput = C("input", ["type", "text", "value", cantidad, "class", "form-control", "readonly", 1], cantidad);
		var tags;
		var cantidades;
		var popupDOM = C("div",
			C("form", ["onsubmit", function(){ return false; }],
				C("div", "Nombre"),
				C("div", C("input", ["type", "text", "value", objeto["nombre"], "class", "form-control"])),
				C("div", C("button", ["class", "btn btn-primary"], actualizarStr))
			),
			C("form", ["onsubmit", function(){ return false; }],
				C("div", "Descripción"),
				C("div", C("input", ["type", "text", "value", objeto["descripcion"], "class", "form-control"])),
				C("div", C("button", ["class", "btn btn-primary"], actualizarStr))
			),
			C("form", ["onsubmit", function(){ return false; }],
				C("div", "Cantidad mínima"),
				C("div", C("input", ["type", "text", "value", objeto["minimo_alerta"], "class", "form-control", "onchange", onMinimoChange])),
				C("div", C("button", ["class", "btn btn-primary"], actualizarStr))
			),
			C("form", ["onsubmit", function(){ return false; }],
				C("div", "Cantidad"),
				C("div", C("div", ["class", "cantidades"],
					cantidades = C("div"), 
					C("div", ["class", "btn btn-primary add", "onclick", function(){
						objeto.secciones[objeto.secciones.length] = {cantidad: 0, id_seccion: Object.keys(lista.secciones)[0]};
						C(cantidades, DrawCantidadInput(objeto["secciones"][objeto.secciones.length - 1]));
						return false;
					}], "+ Añadir a otro lugar"),
					C("span", C("span", "Total:"), cantidadROInput)
				)),
				C("div", C("button", ["class", "btn btn-primary"], actualizarStr))
			),
			C("form", ["onsubmit", function(){ return false; }],
				C("div", "Tags"),
				C("div", tags = C("input", ["type", "text", "value", objeto["tags"], "class", "form-control"])),
				C("div", C("button", ["class", "btn btn-primary"], actualizarStr))
			),
			"ID: ", objeto.id,
		);
		
		for (var i = 0; i < objeto["secciones"].length; i++) {
			C(cantidades, DrawCantidadInput(objeto["secciones"][i]));
		}
		
		$(tags).tokenfield({
			autocomplete: {
				source: tagsArrayAutocomplete,
				delay: 100
			  },
			showAutocompleteOnFocus: true,
			allowEditing: true
		});
		
		showPopup(popupDOM);
		
		
		
		function onMinimoChange(ev) {
			if (ev.target.value < 0) ev.target.value = 0;
			onNumberChange(ev);
		}
		function onNumberChange(ev) {
			var numeroSinCerosDelante = /^0*(.*)/.exec(ev.target.value)[1];
			ev.target.value = eval(numeroSinCerosDelante);
			if (isNaN(ev.target.value)) ev.target.value = 0;
		}
		
		function DrawCantidadInput(seccionObjeto) {
			console.log(seccionObjeto);
			var seccion = lista.secciones[seccionObjeto["id_seccion"]];
			var almacen = lista.almacenes[seccion.id_almacen];
			var seccionesSelect, almacenesSelect;
			var cantidadBlock = C("div", ["class", "cantidad-block"],
				C("div", ["class", "contenido"],
					almacenesSelect = C("select"),
					seccionesSelect = C("select"),
					C("input", ["type", "text", "value", seccionObjeto.cantidad, "class", "form-control", "onchange", function(ev) {
						onMinimoChange(ev);
						seccionObjeto.cantidad = ev.target.value;
						UpdateROCantidad();
					}])
				),
				C("div", ["class", "borrar"],
					C("div", ["class", "btn btn-danger", "onclick", function(){
						cantidadBlock.parentNode.removeChild(cantidadBlock);
						objeto.secciones = objeto.secciones.filter(function(x){ return x.id_seccion !== seccionObjeto.id_seccion; });
						UpdateROCantidad();
					}], "X")
				)
			);
			ToOptions(almacenesSelect, lista.almacenes, almacen);
			ToOptions(seccionesSelect, filterSecciones(almacen), seccion);
			almacenesSelect.onchange = function(ev) {
				almacen = lista.almacenes[ev.target.value];
				ToOptions(seccionesSelect, filterSecciones(almacen), seccion);
				
				var event = new Event('change');
				seccionesSelect.dispatchEvent(event);
			}
			seccionesSelect.onchange = function(ev) {
				seccionObjeto.id_seccion = parseInt(ev.target.value);
			}
			return cantidadBlock;
		}
		
		function UpdateROCantidad() {
			cantidadROInput.value = GetCantidad(objeto);
		}
		
		function ToOptions(parentElement, elementos, selected) {
			for (var i = parentElement.childNodes.length - 1; i >= 0; i--) {
				parentElement.removeChild(parentElement.childNodes[i]);
			}
			for (var i in elementos) {
				var option = C("option", ["value", elementos[i].id], elementos[i].nombre);
				if (selected.id === elementos[i].id) option.setAttribute("selected", 1);
				C(parentElement, option);
			}
		}
		
		function filterSecciones(almacen) {
			var seccionesFiltradas = {};
			for (var i in lista.secciones) {
				if (lista.secciones[i].id_almacen === almacen.id) {
					seccionesFiltradas[i] = lista.secciones[i];
				}
			}
			return seccionesFiltradas;
		}		
	}
}

function GetCantidad(objeto) {
	return objeto.secciones.reduce(function(prev, cur) {
		return { cantidad: parseInt(prev.cantidad) + parseInt(cur.cantidad) };
	})["cantidad"];
}

function AddClass(dom, className) {
	dom.className += " " + className;
}
function RemoveClass(dom, className) {
	dom.className = dom.className.split(" " + className).join("");
}





function GetTagList(lista) {
	var allTags = [];
	var allTagsTmp = {};
	for (var a in lista) {
		var secciones = lista[a]["secciones"];
		for (var s in secciones) {
			var objetos = secciones[s]["objetos"];
			for (var o in objetos) {
				var tags = objetos[o]["tags"];
				for (var l in tags) {
					allTagsTmp[tags[l]] = true;
				}
			}
		}
	}
	for (var i in allTagsTmp) allTags.push(i);
	return allTags;
}


</script>



</body>
</html>
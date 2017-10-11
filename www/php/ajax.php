<?php
require_once 'lib/DB.php';


if (isset($_GET['action']) || isset($_POST['action'])) {
	$db = new DB();
	$db->open();
	if ($db->is_away()) {
		header("HTTP/1.1 503 Service Unavailable");
		exit;
	}
}

if (isset($_GET['action'])) {
	switch($_GET['action']) {
		case 'getinventario':
			$objetos = $db->get_objetos();
			foreach ($objetos as &$objeto) $objeto["secciones"] = $db->get_objeto_secciones($objeto["id"]);
			foreach ($db->get_almacenes() as &$almacen) $almacenes[$almacen["id"]] = &$almacen;
			foreach ($db->get_secciones() as &$seccion) $secciones[$seccion["id"]] = &$seccion;
			
			echo json_encode(array(
				"almacenes" => $almacenes,
				"secciones" => $secciones,
				"objetos" => $objetos
			), 1);
			break;
		case 'getfile':
			if (isset($_GET["id"])) {
				$file = $db->get_file($_GET["id"]);
				if ($file[0]) {
					$file = $file[0];
					$etag = base64_encode(md5($file['id']));
					
					header('Etag: ' . $etag);
					header('Cache-Control: max-age=120, public'); // 2 min. This is a problem when developing. Force check.
					
					if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
						header('HTTP/1.1 304 Not Modified');
						exit;
					} else {
						header('Content-type: ' . $file["mimetype"]);
						echo $file["bin"];
					}
				}
			}
			break;
	}
} else {
	print_r($_GET);
	print_r($_POST);
	print_r($_FILES);
	
	switch($_POST['action']) {
		case 'update-object-image':
		if (!isset($_POST["id-object"])) {
			echo json_encode(array(
				"STATUS" => "ERROR",
				"MESSAGE" => "No se ha enviado la id del objeto. Por favor comunica este error a un encargado de la app."
			));
		} else if (!isset($_FILES["imagen"]) || $_FILES["imagen"]["name"] == "") {
			echo json_encode(array(
				"STATUS" => "ERROR",
				"MESSAGE" => "No se ha enviado una imagen"
			));
		} else {
			$file_index = "";
			$db->add_file($_FILES["imagen"]["type"], file_get_contents($_FILES["imagen"]["tmp_name"]), $file_index);
			echo $file_index;
			$db->object_set_image($_POST["id-object"], $file_index);
		}
		break;
	}
}
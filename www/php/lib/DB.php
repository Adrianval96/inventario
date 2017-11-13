<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../functions/generic.php';
// All the queries to the database are here. Change the database engine or the queries only have to be done here.
class DB {
	// Login data to access the database. change in the config file.
	private $host = MYSQL_HOST;
	private $user = MYSQL_USER;
	private $pass = MYSQL_PASSWORD;
	private $bd   = MYSQL_DATABASE;
	
	var $mysqli;
	
	private $opened_connection = false;
	
	// Auto inserted id number
	var $LAST_MYSQL_ID = '';
	
	function Open($host=null, $user=null, $pass=null, $bd=null) {
		if($this->d) $this->debug('Opening database');
		if ($host !== null)
			$this->host = $host;
		if ($user !== null)
			$this->user = $user;
		if ($pass !== null)
			$this->pass = $pass;
		if ($bd !== null)
			$this->bd = $bd;
			
		// Persistent connection:
		// http://www.php.net/manual/en/mysqli.persistconns.php
		// To open a persistent connection you must prepend p: to the hostname when connecting. 
		$this->mysqli = new mysqli('p:'.$this->host, $this->user, $this->pass, $this->bd);
		if ($this->mysqli->connect_errno) {
			if($this->d) $this->debug('Failed to connect to MySQL: (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error);
			$this->away = true;
			return false;
		}
		$this->away = false;
		$this->mysqli->set_charset('utf8');
		if($this->d) $this->debug('Database opened');
		return true;
	}
	
	// Make a SQL query. Returns false if there is an error, and throws an exception.
	// Queries are only done here. This way a connection can be opened if necessary
	// $this->LAST_MYSQL_ID stores the ID of the last insert query
	private function query($query) {
		if ($this->opened_connection === false) {
			if (!$this->Open()) {
				if($this->d) $this->debug('Can\'t open the database');
				return false;
			}
			$this->opened_connection = true;
		}
		
		$result = $this->mysqli->query($query, MYSQLI_USE_RESULT);
		if (strpos($query, 'INSERT') !== false) {
			$this->LAST_MYSQL_ID = $this->mysqli->insert_id;
		} else {
			$this->LAST_MYSQL_ID = null;
		}
		if ($result === false || $result === true) {
			if($this->d) $this->debug('<span class="info">query</span>: <span class="query">'.$this->query_debug_str($query)."</span>\r\n<span class='info'>result</span>: <b class=\"".($result?'ok">TRUE':'fail">FALSE ('.$this->mysqli->error.')')."</b>\r\n");
			return $result;
		}
		
		$resultArray = array();
		while ($rt = $result->fetch_array(MYSQLI_ASSOC)) $resultArray[] = $rt;
		if($this->d) $this->debug('<span class="info">query</span>: <span class="query">'.$this->query_debug_str($query)."</span>\r\n<span class='info'>result</span>: ".print_r($resultArray)."\r\n");
		return $resultArray;
	}
	
	// Variables
	private $away = false;
	
	function is_away() {
		return $this->away;
	}
	
	//debug mode
	var $debug_array = array();
	private $d = false;
	private $d_array = false;
	private $d_queryLength = 256;
	function debug_mode($bool) {
		$this->d = $bool;
		$this->debug('<span class="info">debug mode: ' . $bool.'</span>');
	}
	function debug_to_array($bool) {
		$this->d_array = $bool;
	}
	private function debug($txt) {
		if ($this->d) {
			if ($this->d_array) {
				$this->debug_array[] = $txt;
			} else {
				echo $txt . "\r\n";
			}
		}
	}
	private function query_debug_str(&$query) {
		return strlen($query) > $this->d_queryLength ? substr($query, 0, $this->d_queryLength) : $query;
	}
	
	
	
	// Not the best option
	function create_tables(&$content) {
		//remove comments
		$instructions = preg_replace('/--.*?[\r\n]/', '', $content);
		$instructions = preg_replace('|/\*.*?\*/|', '', $instructions);
		$instructions = str_replace("\n", '', $instructions);
		$instructions = str_replace("\r", '', $instructions);
		$instructions = explode(";", $instructions);
		foreach ($instructions as $instruction)
			if ($instruction !== '')
				$this->query($instruction);
	}
	
	
	
	function get_almacenes() {
		return $this->query("SELECT * FROM almacen");
	}
	function get_secciones() {
		return $this->query("SELECT * FROM seccion");
	}
	function get_objetos() {
		return $this->query("SELECT * FROM objeto");
	}
	
	function get_objeto_secciones($id_objeto) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		return $this->query("SELECT id_seccion, cantidad FROM objeto_seccion WHERE id_objeto = {$id_objeto}");
	}
	
	function add_file($mimetype, $blob, &$file_index) {
		$file_index = md5($blob);
		$mimetype = mysql_escape_mimic($mimetype);
		$blob = mysql_escape_mimic($blob);
		return $this->query("INSERT INTO files (id, mimetype, bin) VALUES ('$file_index', '$mimetype', '$blob')");
	}
	function get_file($file_index) {
		$file_index = mysql_escape_mimic($file_index);
		return $this->query("SELECT * FROM files WHERE id = '$file_index'");
	}
	
	function object_set_image($id_objeto, $id_file) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		$id_file = mysql_escape_mimic($id_file);
		return $this->query("UPDATE objeto SET imagen = '$id_file' WHERE id = '$id_objeto' LIMIT 1");
	}
	function object_set_name($id_objeto, $name) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		$name = mysql_escape_mimic($name);
		return $this->query("UPDATE objeto SET nombre = '$name' WHERE id = '$id_objeto' LIMIT 1");
	}
	function object_set_minimo($id_objeto, $minimo) {
		$id_objeto = mysql_escape_mimic($id_objeto);
		$minimo = mysql_escape_mimic($minimo);
		return $this->query("UPDATE objeto SET minimo_alerta = '$minimo' WHERE id = '$id_objeto' LIMIT 1");
	}
}
// Copy of mysql_real_escape_string to use it without an opened connection.
// http://es1.php.net/mysql_real_escape_string
function mysql_escape_mimic($inp) {
	if (is_array($inp))
		return array_map(__METHOD__, $inp);
	if (!empty($inp) && is_string($inp))
		return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
	return $inp;
}
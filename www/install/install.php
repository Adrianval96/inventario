
Importante: Cambiar en la configuración de MySQL los parametros a:
max_allowed_packet = 16M
innodb_log_file_size = 160M


<form method="post">
MYSQL_HOST: <input type="text" name="MYSQL_HOST"/><br/>
MYSQL_USER: <input type="text" name="MYSQL_USER"/><br/>
MYSQL_PASSWORD: <input type="text" name="MYSQL_PASSWORD"/><br/>
MYSQL_DATABASE: <input type="text" name="MYSQL_DATABASE"/><br/>
<input type="submit"/>
</form>






<?php

private $host = MYSQL_HOST;
private $user = MYSQL_USER;
private $pass = MYSQL_PASSWORD;
private $bd   = MYSQL_DATABASE;
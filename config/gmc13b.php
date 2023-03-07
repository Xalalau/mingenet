<?php
// Close connecion
if (isset($CONNECTION))
    CloseConnection($CONNECTION);

// Host setup
$db_num = $_GET ? $_GET['db'] : $force_db;
$sql = array(
    "host" => "minge-mariadb",
    "login" => getenv('MYSQL_USER'),
    "password" => getenv('MYSQL_PASSWORD'),
    "database" => "gmc13b" . (float) $db_num,
);

// Database CONNECTION
$CONNECTION = OpenConnection($sql);

if ( ! isset($CONNECTION)) {
    echo "Database CONNECTION failed";
    exit(1);    
}

// Charset
SetCharset($CONNECTION);
?>

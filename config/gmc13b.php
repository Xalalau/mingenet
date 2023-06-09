<?php
// Close connecion
if (isset($CONNECTION))
    CloseConnection($CONNECTION);

// Host setup
$db_num = $_GET ? $_GET['db'] : $force_db;
if (is_numeric($db_num)) {
    $db_num = (float) $db_num;
} else {
    $db_num = 1;
}

$sql = array(
    "host" => "minge-mariadb",
    "login" => getenv('MYSQL_USER'),
    "password" => getenv('MYSQL_PASSWORD'),
    "database" => "gmc13b" . $db_num,
);

// Database CONNECTION
$CONNECTION = OpenConnection($sql);

if ( ! isset($CONNECTION)) {
    echo "Database CONNECTION failed";
    exit(1);    
}

// Charset
SetCharset($CONNECTION);

// Config
$config_idx = 1
?>

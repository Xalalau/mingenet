<?php
// Close connecion
if (isset($CONNECTION))
    CloseConnection($CONNECTION);

// Host setup
$db_num = $_GET ? $_GET['db'] : $force_db;
$sql = array(
    "host" => "localhost",
    "login" => "gmc13b" . $db_num,
    "password" => "~Zf&4sFtN59cEh1X",
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
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

// Set the char format
function SetCharset($CONNECTION) {
    $CONNECTION->set_charset("utf8");
}

// Get the root folder
function GetRoot() {
    $awords = explode('/', getcwd()); 
    $public_html = "";
    foreach ($awords as $word) {
        $public_html .= $word . "/";
        if ($word == 'public_html')
            break;
    }
    return $public_html;
}

// Get the root folder
function GetLocal() {
    $awords = explode('/', substr($_SERVER['PHP_SELF'], 1)); 
    $local = "";

    foreach ($awords as $word) {
        if (strpos($word, '.php') !== false) 
            break;
        $local .= $word . "/";
    }
    return $local;
}

// Connect to the database
function OpenConnection($sql) {
    $CONNECTION = mysqli_connect($sql["host"], $sql["login"], $sql["password"], $sql["database"]);
    if ($CONNECTION) {
        return $CONNECTION;
    } else {
        echo "Database CONNECTION failed.";
        echo mysqli_errno($CONNECTION);
        echo "<br/>";
        exit(1);
    }
}

// bye bye
function CloseConnection($CONNECTION) {
    if ($CONNECTION) {
        // Note for error 1226
        if (mysqli_errno($CONNECTION) == 1226) {
            echo "Note: this error means \"Max queries per hour exceeded\". Wait some time.";
            exit(1);
        // Close the CONNECTION
        } else
            mysqli_close($CONNECTION);
    } else {
        echo "Error: No CONNECTION to close.";
        exit(1);
    }
}

// Check MySQLi operation
function CheckMySQLiOperation($operation_id, $operation, $CONNECTION) {
    if ($operation && mysqli_affected_rows($CONNECTION)) {
        return true;
    } else {
        echo "Failed to run " . $operation_id . " operation. MySQLi error code: " . mysqli_errno($CONNECTION) . "\n";

        return false;
    }
}

// Safe query against sql injection
function SafeMysqliQuery($CONNECTION, $sql, $stmt_types, ...$stmt_vars) {
    $stmt = mysqli_stmt_init($CONNECTION);
    if ( ! mysqli_stmt_prepare($stmt, $sql)) {
        printf("Error: %s.\n", $stmt->error);
        require "general/footer.php";
        exit(0);
    } else {
        mysqli_stmt_bind_param($stmt, $stmt_types, ...$stmt_vars);
        $was_successful = mysqli_stmt_execute($stmt);

        if ($was_successful) {
            $true_result = $stmt->get_result();
            if ($true_result)
                return $true_result;
            else
                return true;
        }

        return false;
    }
}

// Basic address setub
$WEBSITE = array(
    "link" => "http://$_SERVER[HTTP_HOST]/", // Website address
    "root" => GetRoot() . "/", // Root folder
    "current" => $_SERVER['PHP_SELF'], // Current file
    "rel_local" => GetLocal(), // Current location relative to the root
    "full_local" => GetRoot() . GetLocal() // Root folder + Current location relative to the root
);

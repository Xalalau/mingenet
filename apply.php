<?php
require "general/header.php";
require "config/gmc13b.php";

$config = mysqli_fetch_array(mysqli_query($CONNECTION, "SELECT dev_ipx, next_lobby_dt, accept_info_s, start_checks_s FROM config WHERE idx='1'"));

$next_lobby_dt = new DateTime($config['next_lobby_dt']);
$now_dt = new DateTime(date("Y-m-d H:i:s"));
$next_lobby_s = $next_lobby_dt->getTimestamp() - $now_dt->getTimestamp();

// Insert the entry data if we are in the moment to apply for the lobby
if ($next_lobby_s <= ($config["accept_info_s"] + $config["start_checks_s"]) && $next_lobby_s >= $config["start_checks_s"]) {
    $ipx = str_replace(".", "", $_SERVER['HTTP_CF_CONNECTING_IP']);
    $ipx .= $_POST["gameID"] ?? ""; // Enables multiple connections from the same IP

    $insert = false;

    $is_entry_waiting = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT idx FROM waiting WHERE ipx=$ipx"));

    if ($is_entry_waiting) {
        $player_num = $_POST['plyNum'];
        $map = $_POST["map"];

        mysqli_query($CONNECTION, "DELETE FROM waiting WHERE ipx=$ipx");

        $insert = SafeMysqliQuery($CONNECTION, "INSERT INTO competing (ipx, map, player_num) VALUES (?, ?, ?)", "dsi", $ipx, $map, $player_num);
    }

    if ($insert)
        echo "1";
    else
        echo "0";
}

require "general/footer.php";
?>
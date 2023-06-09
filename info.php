<?php
require "general/header.php";
require "config/gmc13b.php";

$config = mysqli_fetch_array(mysqli_query($CONNECTION, "SELECT * FROM config WHERE idx=$config_idx"));

// Check addon version
$version = $_POST['version'] ?? "1";
$is_updated = true;
if ($config['version'] > $version)
    $is_updated = false;

$force_disconnect = $config['force_disconnect'] == 1;

if ($is_updated && ! $force_disconnect) {
    $next_lobby_dt = new DateTime($config['next_lobby_dt']);
    $now_dt = new DateTime(date("Y-m-d H:i:s"));
    $next_lobby_s = $next_lobby_dt->getTimestamp() - $now_dt->getTimestamp();

    // Initialize a new lobby
    if ($next_lobby_s < 0) {
        $next_lobby_s = $config['cooldown_s'];

        if (mt_rand(1, 100) <= 5) {
            mysqli_query($CONNECTION, "UPDATE config SET is_big_lobby=1 WHERE idx=$config_idx");
            $next_lobby_s += 350;
        }

        $new_timestamp = $now_dt->getTimestamp() + $next_lobby_s;
        $new_time_dt = DateTime::createFromFormat('U', $new_timestamp);
        $new_time = $new_time_dt->format('Y-m-d H:i:s');

        mysqli_query($CONNECTION, "UPDATE config SET next_lobby_dt='$new_time' WHERE idx=$config_idx");

        mysqli_query($CONNECTION, "TRUNCATE TABLE waiting");
        mysqli_query($CONNECTION, "TRUNCATE TABLE competing");

        // Start statistics
        mysqli_query($CONNECTION, "INSERT INTO statistics (lobby_dt) VALUES ('$new_time')");
    }

    // Waiting list
    if ($next_lobby_s >= ($config["accept_info_s"] + $config["start_checks_s"])) {
        $gameID = $_POST["gameID"] ?? ""; // Enable multiple game instances to connect from the same IP
        $ip = str_replace(".", "", $_SERVER['HTTP_CF_CONNECTING_IP']);
        $sub_ip = substr($ip, 0, -4);
        $ipx = $sub_ip - $gameID;

        $is_entry_already_waiting = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT * FROM waiting WHERE ipx=$ipx"));

        if ( ! $is_entry_already_waiting) {
            $map = $_POST["map"];
            $player_num = $_POST["plyNum"];

            SafeMysqliQuery($CONNECTION, "INSERT INTO waiting (ipx, map, player_num) VALUES (?, ?, ?)", "dsi", $ipx, $map, $player_num);
        }
    }

    $cur_entries_num = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT * FROM waiting"));

    // Return the lobby info
    echo json_encode([
        'cooldown_s' => (int) $config['cooldown_s'],
        'playing_time_s' => (int) $config['playing_time_s'],
        'tick_s' => (float) $config['tick_s'],
        'accept_info_s' => (int) $config['accept_info_s'],
        'start_checks_s' => (int) $config['start_checks_s'],
        'extra_sync_s' => (int) $config['extra_sync_s'],
        'next_lobby_s' => (int) $next_lobby_s,
        'cur_entries_num' => (int) $cur_entries_num,
        'lobby_dt' => $config['next_lobby_dt'],
        'is_updated' => $is_updated,
        'force_disconnect' => $force_disconnect
    ]);

// Connection blocked
} else {
    // Return the lobby info
    echo json_encode([
        'is_updated' => $is_updated,
        'force_disconnect' => $force_disconnect
    ]);
}

require "general/footer.php";
?>
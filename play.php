<?php
require "general/header.php";
require "config/gmc13b.php";

/*
    Player status during the lobby:

        0 = ongoing
        1 = expired
        2 = defeated
        3 = disconnected

    Lobby results:

        maxtime
        unsolved
        survived
        nolobby
        defeated
        disconnected

    In the same GMod server:

        ent_index is used to identify each player because they share the same ipx
        idx is used to identify each mingebag
*/

// Decode json
$decoded_post = [];
foreach ($_POST as $ent_index => $ply_data_json) {
    $decoded_ply_data = json_decode($ply_data_json);
    $decoded_post[$ent_index] = [];
    foreach ($decoded_ply_data as $key => $value) {
        $decoded_post[$ent_index][$key] = $value;
    }
}

// Get the config
$config = mysqli_fetch_array(mysqli_query($CONNECTION, "SELECT max_afk_s FROM config WHERE idx=$config_idx"));

// Ipx
$gameID = $decoded_post["1"]["gameID"] ?? ""; // Enable multiple game instances to connect from the same IP
$ip = str_replace(".", "", $_SERVER['HTTP_CF_CONNECTING_IP']);
$sub_ip = substr($ip, 0, -4);
$ipx = $sub_ip - $gameID;

// Add new players
function AddNewPlayers($CONNECTION, $decoded_post, $ipx, $first_entry) {
    // Get the datetime data
    $last_refresh_dt = $first_entry['last_refresh_dt'];
    $start_dt = $first_entry['start_dt'];
    $end_dt = $first_entry['end_dt'];
    $map = $first_entry['map'];

    // Clear dummy entry
    mysqli_query($CONNECTION, "DELETE FROM lobby WHERE ipx=$ipx AND ent_index=-1");

    // Insert players if they don't exist
    foreach ($decoded_post as $ent_index => $ply_data) {
        $is_player_registered = 0;

        $player_query = SafeMysqliQuery($CONNECTION, "SELECT idx FROM lobby WHERE ipx=$ipx AND ent_index=?", "i", $ent_index);

        if ($player_query)
            $is_player_registered = mysqli_num_rows($player_query);

        if ( ! $is_player_registered) {
            SafeMysqliQuery(
                $CONNECTION,
                "INSERT INTO lobby (map, ipx, ent_index, last_refresh_dt, start_dt, end_dt) VALUES ('$map', $ipx, ?, '$last_refresh_dt', '$start_dt', '$end_dt')",
                "i",
                $ent_index
            );
        }
    }
}

// Register general incomming player lobby data
function RegisterIncommindData($CONNECTION, $decoded_post, $ipx, $now_date) {
    $changed_status = null;
    $updated = null;

    foreach ($decoded_post as $ent_index => $ply_data) {
        $player = mysqli_fetch_array(SafeMysqliQuery($CONNECTION, "SELECT pos, ang, is_firing, used_chat FROM lobby WHERE ipx=$ipx AND ent_index=?", "i", $ent_index));

        $pos = $ply_data["pos"] ?? null;
        $ang = $ply_data["ang"] ?? null;
        $is_firing = $ply_data["is_firing"] ?? null;
        $used_chat = $ply_data["used_chat"] ?? null;
        $invader_injured = $ply_data["invader_injured"] ?? null;
        $disconnected = $ply_data["disconnected"] ?? null;

        // Only update players that are doing something
        $changed_status = ! $disconnected && (
                            empty($player['pos']) ||
                            $player['pos'] != $pos ||
                            $player['ang'] != $ang ||
                            $player['is_firing'] != $is_firing ||
                            $player['used_chat'] != $used_chat
                        );

        $updated = false;
        if ($changed_status) {
            $updated = SafeMysqliQuery(
                $CONNECTION,
                "UPDATE lobby SET pos=?, ang=?, is_firing=?, used_chat=?, last_refresh_dt='$now_date' WHERE ipx=$ipx AND ent_index=?", 
                "ssiii",
                $pos, $ang, $is_firing, $used_chat, $ent_index
            );
        }

        // Defeated someone
        if (isset($invader_injured) && $invader_injured != "") {
            $updated = SafeMysqliQuery($CONNECTION, "UPDATE lobby SET status=2 WHERE idx=?", "i", $invader_injured);
        }

        // Disconnected player
        if ($disconnected != null) {
            $updated = SafeMysqliQuery($CONNECTION, "UPDATE lobby SET status=3 WHERE ipx=$ipx AND ent_index=?", "i", $ent_index);
        }
    }

    return [ $changed_status, $updated ];
}

$final_result = null;
$are_invaders_unsolved = false;
$in_fight_count = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT idx FROM lobby WHERE ipx=$ipx"));
$first_entry = mysqli_fetch_array(mysqli_query($CONNECTION, "SELECT ent_index, map, last_refresh_dt, start_dt, end_dt FROM lobby WHERE ipx=$ipx LIMIT 1"));

if ($in_fight_count != 0) {
    // Add new players
    if (count($decoded_post) > $in_fight_count || $first_entry['ent_index'] == -1) {
        AddNewPlayers($CONNECTION, $decoded_post, $ipx, $first_entry);
    }

    $now_date = date("Y-m-d H:i:s");
    $now_dt = new DateTime($now_date);
    $end_dt = new DateTime($first_entry['end_dt']);

    // Max time reached
    if ($now_dt >= $end_dt)
        $final_result = 'maxtime';

    // Register general incomming player lobby data
    $register_data = RegisterIncommindData($CONNECTION, $decoded_post, $ipx, $now_date);
    $changed_status = $register_data[0];
    $updated = $register_data[1];

    if ($updated || ! $changed_status) {
        // Count players by status (as needed)
        $players = mysqli_query($CONNECTION, "SELECT idx, ipx, ent_index, pos, ang, is_firing, used_chat, status, last_refresh_dt FROM lobby WHERE end_dt='" . $first_entry['end_dt'] . "'");
        $status = [
            'players' => [
                'num' => 0,
                'alive' => 0,
                'killed' => 0,
                'expired' => 0,
                'disconnected' => 0
            ],
            'invaders' => [
                'num' => 0,
                'alive' => 0,
                'killed' => 0,
                'expired' => 0,
                'disconnected' => 0
            ]
        ];

        while ($player = mysqli_fetch_array($players)) {
            $group = $player['ipx'] == $ipx ? 'players' : 'invaders';

            $status[$group]['num'] += 1;

            if ($player['status'] == 1) {
                $status[$group]['expired'] += 1;
            } elseif ($player['status'] == 2) {
                $status[$group]['killed'] += 1;
            } elseif ($player['status'] == 3) {
                $status[$group]['disconnected'] += 1;
            } elseif ($player['status'] == 0) {
                $last_player_refresh_dt = new DateTime($player['last_refresh_dt']);
                $seconds_since_last_player_refresh = $now_dt->getTimestamp() - $last_player_refresh_dt->getTimestamp();

                if ($seconds_since_last_player_refresh > $config['max_afk_s']) {
                    mysqli_query($CONNECTION, "UPDATE lobby SET status=1 WHERE idx=" . $player['idx']);
                    $status[$group]['expired'] += 1;
                } else {
                    $status[$group]['alive'] += 1;
                }
            }
        }

        /*
            Result: Unsolved
                all players expired and/or disconnected
                or
                all invaders expired and/or disconnected
        */
        $are_invaders_unsolved = ($status['invaders']['expired'] + $status['invaders']['disconnected']) == $status['invaders']['num'];
  
        if (($status['players']['expired'] + $status['players']['disconnected']) == $status['players']['num'] || $are_invaders_unsolved)
            $final_result = 'unsolved';
        /*
            Result: Survived
                some player alive
                and
                some invader killed
                and
                no invader alive (expired + disconnected + killed = total)
        */
        elseif ($status['players']['alive'] > 0 && $status['invaders']['killed'] > 0 && (($status['invaders']['killed'] + $status['invaders']['expired'] + $status['invaders']['disconnected']) == $status['invaders']['num']))
            $final_result = 'survived';
        /*
            Result: Defeated
                no player alive
                and
                some invader alive
        */
        elseif ($status['players']['alive'] == 0 && $status['invaders']['alive'] > 0)
            $final_result = 'defeated';

        // Return invaders info with the final result if it's ready
        $user_data = [
            'players' => [],
            'invaders' => []
        ];

        if ($final_result)
            $user_data['result'] = $final_result;

        $player_status = [
            "ongoing",
            "expired",
            "defeated",
            "disconnected",
        ];

        mysqli_data_seek($players, 0);
        while ($player = mysqli_fetch_array($players)) {
            $index = $player['ipx'] == $ipx ? 'players' : 'invaders';

            if ($index == 'players') {
                $user_data[$index][$player['ent_index']] = [
                    'status' => $player_status[$player['status']]
                ];
            } else {
                $user_data[$index][$player['idx']] = [
                    'status' => $player_status[$player['status']]
                ];

                if ($player['status'] == 0) {
                    $user_data[$index][$player['idx']]['pos'] = $player['pos'];
                    $user_data[$index][$player['idx']]['ang'] = $player['ang'];
                    $user_data[$index][$player['idx']]['is_firing'] = $player['is_firing'];
                    $user_data[$index][$player['idx']]['used_chat'] =$player['used_chat'];
                }
            }
        }

        echo json_encode($user_data);
    }
// The player isn't in a lobby
} else {
    echo json_encode([ 'result' => 'nolobby' ]);
}

// Finish lobby
if ($final_result) {
    // Adjustments for defeated and expired encounters
    if ($final_result == 'defeated' || $final_result == 'unsolved') {
        // Clear ipx (so the player will be able to join other lobbies while keeping their stats accessible on the one the're leaving)
        mysqli_query($CONNECTION, "UPDATE lobby SET ipx=0 WHERE ipx=$ipx");
    }

    // Current time
    $now_dt = new DateTime(date("Y-m-d H:i:s"));
    $now_s = $now_dt->getTimestamp();

    // Advance end of round if someone wins or the invaders expire
    if ($final_result == 'survived' || $are_invaders_unsolved) {
        $new_end = $now_dt->format('Y-m-d H:i:s');
        mysqli_query($CONNECTION, "UPDATE lobby SET end_dt='$new_end' WHERE end_dt='" . $first_entry['end_dt'] . "'");
    }

    // Record statistics whenever some group arrives at a result
    $start_dt = new DateTime($first_entry['start_dt']);
    $start_s = $start_dt->getTimestamp();
    $playing_time_s = $now_s - $start_s;

    if ($final_result == "defeated")
        $final_result = "unsolved"; // Defeats are "unsolved" because they aren't an expected end result

    if ($final_result == "survived")
        $final_result = "finished"; // Survival is the real expected end result for a match, so it's finished

    $killed_player_num = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT idx FROM lobby WHERE start_dt='" . $first_entry["start_dt"] . "'"));

    mysqli_query($CONNECTION, "UPDATE statistics SET playing_time_s=$playing_time_s, result='$final_result', killed_player_num=$killed_player_num WHERE lobby_dt='" . $first_entry["start_dt"] . "'");
}

require "general/footer.php";
?>
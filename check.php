<?php
require "general/header.php";
require "config/gmc13b.php";

// Get the config
$config = mysqli_fetch_array(mysqli_query($CONNECTION, "SELECT dev_ipx, next_lobby_dt, start_checks_s, playing_time_s FROM config WHERE idx=1"));
$dev_ipx = str_replace(".", "", $config['dev_ipx']);

// Check the time
$now_dt = new DateTime(date("Y-m-d H:i:s"));
$next_lobby_dt = new DateTime($config['next_lobby_dt']);
$next_lobby_s = $next_lobby_dt->getTimestamp() - $now_dt->getTimestamp();

if ($next_lobby_s > $config["start_checks_s"]) {
    require "general/footer.php";
    exit(0);
}

// Get the competing entries
$competing_entries = mysqli_query($CONNECTION, "SELECT * FROM competing");
$candidate_num = mysqli_num_rows($competing_entries);

if ($candidate_num > 0) {
    // Remove older encounters
    $current_lobby_entries = mysqli_query($CONNECTION, "SELECT DISTINCT idx, end_dt FROM lobby GROUP BY(end_dt)");

    if (mysqli_num_rows($current_lobby_entries) > 0) {
        while ($entry = mysqli_fetch_array($current_lobby_entries)) {
            $end_dt = new DateTime($entry['end_dt']);

            if ($now_dt > $end_dt)
                mysqli_query($CONNECTION, "DELETE FROM lobby WHERE end_dt='" . $entry['end_dt'] . "'");
        }
    }

    // Statistics: record candidates quantity
    mysqli_query($CONNECTION, "UPDATE statistics SET candidate_num='$candidate_num' WHERE lobby_dt='".$config["next_lobby_dt"]."'");

    // Split entries by map getting only 1 entry per server
    $candidates = [];
    $servers = [];

    while ($entry = mysqli_fetch_array($competing_entries)) {
        $candidates[$entry['map']] = $candidates[$entry['map']] ?? [];
        array_push($candidates[$entry['map']], [ $entry['ipx'] => $entry['player_num'] ]);
    }

    // Remove maps with one entry
    foreach ($candidates as $map => $ipx_num_list) {
        if (count($ipx_num_list) == 1) {
            unset($candidates[$map]);
        }
    }

    // Select map and entries randomly
    // Note: it'll select the developer in case he's doing tests
    $candidates_count = count($candidates);
    if ($candidates_count > 0) {
        $competing_dev = mysqli_query($CONNECTION, "SELECT * FROM competing WHERE ipx=$dev_ipx");
        $force_dev_to_play = false;

        $is_dev_competing = mysqli_num_rows($competing_dev) > 0;
        if ($is_dev_competing) {
            $competing_dev = mysqli_fetch_array($competing_dev);
            $is_dev_map_valid = in_array($competing_dev['map'], $candidates);
            if ( ! $is_dev_map_valid)
                $force_dev_to_play = true;
        }

        if ($candidates_count > 1) {
            if ($force_dev_to_play) { 
                $selected_map = $competing_dev['map'];
            } else {
                $selected_map = array_rand($candidates);
            }

            $ipx_num_list = $candidates[$selected_map];
            $selected_entry_num = mt_rand(2, count($ipx_num_list));
            //$selected_entry_num = 2;
            //$selected_entry_num = count($ipx_num_list);
            $chosen_entries = array_rand($ipx_num_list, $selected_entry_num);
        } else {
            foreach ($candidates as $selected_map => $ipx_num_list) {
                $selected_entry_num = mt_rand(2, count($ipx_num_list));
                //$selected_entry_num = 2;
                //$selected_entry_num = count($ipx_num_list);
                $chosen_entries = array_rand($ipx_num_list, $selected_entry_num);
            }
        }

        if ($force_dev_to_play)
            $is_dev_selected = false;

        foreach ($chosen_entries as $key => $chosen_entry) {
            $chosen_entries[$key] = $ipx_num_list[$chosen_entry];

            if ($force_dev_to_play && ! $is_dev_selected) {
                foreach ($chosen_entries[$key] as $entry_ipx => $entry_ply_num) {
                    if ($entry_ipx == $dev_ipx) {
                        $is_dev_selected = true;
                        break;
                    }
                }
            }
        }

        if ($force_dev_to_play && ! $is_dev_selected)
            array_push($chosen_entries, [ $dev_ipx => 1 ]);

        // It's common to see duplicate matches being created, so I believe players are sending the request almost
        // simultaneously and getting the same result. To fix this, just check if the entry is already playing.
        $is_selection_already_done = false;

        foreach ($chosen_entries as $entry) {
            foreach ($entry as $entry_ipx => $entry_ply_num) { 
                if (mysqli_num_rows(mysqli_query($CONNECTION, "SELECT * FROM lobby WHERE ipx=$entry_ipx"))) {
                    $is_selection_already_done = true;
                    break;
                }
            }
        }

        if ( ! $is_selection_already_done) {
            // Add dummy entries to the lobby and count the players
            $playing_time_s = $config['playing_time_s'];
            $new_end_timestamp = $now_dt->getTimestamp() + $playing_time_s + $next_lobby_s;
            $new_end_dt = DateTime::createFromFormat('U', $new_end_timestamp);
            $new_end = $new_end_dt->format('Y-m-d H:i:s');
            $total_player_num = 0;
            foreach ($chosen_entries as $entry) {
                foreach ($entry as $entry_ipx => $entry_ply_num) { 
                    $total_player_num += $entry_ply_num;
                    mysqli_query($CONNECTION, "INSERT INTO lobby (map, ipx, ent_index, last_refresh_dt, start_dt, end_dt) VALUES ('$selected_map', $entry_ipx, -1, '" . $config['next_lobby_dt'] . "', '" . $config['next_lobby_dt'] . "', '$new_end')");
                }
            }

            // Statistics: record map and player count            
            $ipx_num = mysqli_num_rows(mysqli_query($CONNECTION, "SELECT ipx FROM competing"));
            mysqli_query($CONNECTION, "UPDATE statistics SET map='$selected_map', ipx_num=$ipx_num, player_num=$total_player_num WHERE lobby_dt='" . $config["next_lobby_dt"] . "'");

            // Clean the competing entries list
            mysqli_query($CONNECTION, "TRUNCATE TABLE competing");
        }
    }
}

// Check if the player was selected
$ipx = str_replace(".", "", $_SERVER['HTTP_CF_CONNECTING_IP']);
$is_player_selected = mysqli_query($CONNECTION, "SELECT idx FROM lobby WHERE ipx=$ipx AND status=0");

if (mysqli_num_rows($is_player_selected) > 0)
    echo "1";
else
    echo "0";

require "general/footer.php";
?>
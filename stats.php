<?php
require "general/header.php";

$results = [];

$kills = 0;
$encounters = 0;

for ($x = 1; $x <= 3; $x++) {
    $force_db = $x;
    require "config/gmc13b.php";

    $stats = mysqli_query($CONNECTION, "SELECT * FROM statistics");

    if (mysqli_num_rows($stats) > 0) {
        while ($encounter = mysqli_fetch_array($stats)) {
            if ($encounter['result']) {
                if ($encounter['result'] == 'finished')
                    $kills += 1;

                if (empty($results[$encounter['map']])) {
                    $results[$encounter['map']] = 0;
                }

                $results[$encounter['map']] +=  1;
                $encounters += 1;
            }
        }
    }
}

echo "Total encounters: $encounters</br></br>";
echo "Encounters where minges died: $kills</br></br>";

echo "Encounters per map:<br/><br/>";

arsort($results);

echo '<pre>' . var_export($results, true) . '</pre>';

require "general/footer.php";
?>
<?php
require "general/header.php";
require "config/gmc13b.php";

// Quickly test dns and db by returning the entries number
$entries = mysqli_query($CONNECTION, "SELECT idx FROM waiting");

if ($entries)
    echo mysqli_num_rows($entries);
else
    echo "-1";

require "general/footer.php";
?>
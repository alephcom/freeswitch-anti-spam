<?php
require_once 'config.php';
$caller=$argv[1];
$year=$argv[2];
$month=$argv[3];
$day=$argv[4];
mysqli_query($db,"delete from wcallerid where callerid='$caller'");
$query="insert into wcallerid (callerid,SYear,SMonth,SDate) values('$caller','$year','$month','$day')";
mysqli_query($db,$query);
mysqli_close($db);



?>

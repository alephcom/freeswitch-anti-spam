<?php

require_once 'freeSwitchEsl.php';
require_once 'config.php';





$caller=$argv[1];
$uuid=$argv[2];
echo $query="select * from wcallerid where callerid like '%$caller' order by id desc limit 1";
$result=mysqli_query($db,$query);
$row=mysqli_num_rows($result);


if ( $row > 0)
{
$array=mysqli_fetch_array($result);
$year=$array['SYear'];
$month=$array['SMonth'];
$day=$array['SDate'];
$freeswitch = new Freeswitchesl();
$connect = $freeswitch->connect("127.0.0.1","8021","ClueCon");
$freeswitch->api("uuid_setvar $uuid  SYear $year");
$freeswitch->api("uuid_setvar $uuid  SMonth $month");
$freeswitch->api("uuid_setvar $uuid  SDate $day");
$freeswitch->api("uuid_setvar $uuid  Exits 1");
$freeswitch->disconnect();
}
else
{
 $freeswitch = new Freeswitchesl();
 $connect = $freeswitch->connect("127.0.0.1","8021","ClueCon");
 $freeswitch->api("uuid_setvar $uuid  Exits 0");
 $freeswitch->disconnect();

}





?>

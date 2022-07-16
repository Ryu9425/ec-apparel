<?php
 
 
$url = "https://apparel-oroshitonya.com/mail_magazine/fcommit";
$ch = curl_init(); 
 
 
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS,
             "_token=");

$html =  curl_exec($ch);
 
curl_close($ch); //終了

echo $html;
// date_default_timezone_set('UTC');
// $fh = fopen('./crontab.txt', 'a');
// echo $fh;
// echo fwrite($fh, date('Y-m-d H:i:s') . "\r\n");
// fclose($fh);
?>
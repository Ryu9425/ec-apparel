<?php
 
 
$url = "http://localhost/apparel/unsub";
$ch = curl_init();  
 
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS,"_token=");

$html =  curl_exec($ch);
 
curl_close($ch); //終了

 echo $html;
?>
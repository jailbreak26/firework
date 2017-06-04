<?php
function getbetween($content, $start, $end)
{
    $r = explode($start, $content);

    if (isset($r[1]))
    {
        $r = explode($end, $r[1]);
        return $r[0];
    }

    return '';
}
?>
<?php
$url="http://sports4u.ms/ch/Sky-sports-1.php";
$agent= 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, 'http://sports4u.ms/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
?>
<?php
$start = "channel='";
$end = "'";
$output = getBetween($result,$start,$end);
?>
<?php
$url1="http://www.cast4u.tv/hembedplayer/" . $output . "/1/620/480";
$agent= $_SERVER['HTTP_USER_AGENT'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, 'http://sports4u.ms/ch/Sky-sports-1.php');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url1);
$result1=curl_exec($ch);
?>
<?php
$start = 'enableVideo("';
$end = '"';
$output1 = getBetween($result1,$start,$end);
?>
<?php
$url2="http://93.174.93.68:8088/cast4u/skys1/playlist.m3u8?id=20&pk=" . $output1 ;
$agent= $_SERVER['HTTP_USER_AGENT'];
$ip = $_SERVER['REMOTE_ADDR'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Forwarded-For: $ip"));
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, 'http://sports4u.ms/ch/Sky-sports-1.php');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url2);
$result2=curl_exec($ch);
$result2 = preg_replace('/http/', 'http://wizler.net/wave.php/http', $result2);
?>
<?php
echo ($result2);
?>

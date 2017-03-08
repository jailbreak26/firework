<?php
header('Content-Type: text/html');
?>
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

?>
<iframe>
<?php
$url="http://cricfree.sc/update/bteu.php";
$agent= 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, 'http://cricfree.sc/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
?>

<?php
$start = "id='";
$end = "'";
$output = getBetween($result,$start,$end);
?>

<iframe>
<?php
$url1="http://soccerschedule.online/cricembed.php?id=" . $output . "&width=620&height=490&stretching=";
$agent= 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url1);
$result1=curl_exec($ch);
?>
</iframe>
<?php
$start = "id='";
$end = "'";
$output1 = getBetween($result1,$start,$end);
?>

<iframe>
<?php
$url2="http://edge1337.herokuapp.com/http://playstream.club/allcric.php?id=" . $output1 . "&p=0&c=0&stretching=";
$agent= 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_REFERER, 'http://soccerschedule.online/cricembed.php?id=sky1scs&p=0&c=0&stretching=uniform');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_URL,$url2);
$result1=curl_exec($ch);
?></iframe>
<style>
iframe {display:none !important}
</style>
<script>
   var playerInstance = jwplayer("hlstester");
   playerInstance.setup({
   	file: "http://edge1337.herokuapp.com/<?php
$start = 'source: "';
$end = '"';
$output1 = getBetween($result1,$start,$end);
echo ($output1);
?>",
        <?php include 'playerconf.xhtml';?>
</script>

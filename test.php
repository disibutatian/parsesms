<?php
include ("/data/mobile_util/libs/des.php");


//global array

$post_data = array(
  
  'operator' => '中国移动',
  'brand' => '动感地带',
  'province' => '北京',
  'city' => '北京',
  'imsi' => '460028105106350',
  'myver' => 'v1',
  'platform' => 'mifi',
  'version' => 'v3',
);  

function curl_http($url, $info){
    $ch = curl_init();

    //$info = ""; //数据为空的情形

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    
    curl_setopt($ch, CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, '3');
    //$info = http_build_query($info);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $info);
    
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}


function parse_result($smsbody)
{
    global $post_data;
    //var_dump( $post_data);
    
    $post_data["smsbody"] = $smsbody;
    
    //echo $post_data;
    $temp_data = json_encode($post_data);   //采用json的方式传递数据

    $KEY = 'e55a65-@';
    $crypt = new Crypt_DES ( CRYPT_DES_MODE_ECB );
    $crypt->setKey ( $KEY );

    $temp_data = $crypt->encrypt($temp_data);   // 加密后的数据  
    $data_md5 = strtolower ( md5 ( $temp_data ) );
    $token = md5 ( substr ( $data_md5, 0, 16 ) . "_360mobile_" . substr ( $data_md5, 16 ) );    //产生token传送，用来接受方检验数据的合法性


    $url = 'http://w-sweng2.mobi.zzbc.qihoo.net:8810/parsesms_v2/index.php?token='.$token;
    $result = curl_http($url,$temp_data);   //传递的是加密后的数据
    
    //echo $result;
    return $result;
}



$handle = fopen("data.txt","r") or die("Unable to open file!");
$smsbody = "";
$count = 0;

while( !feof($handle) ) 
{
     $temp = fgets($handle);
     if( strlen( $temp ) > 2 )   //以空行为分界符 
     {
         $smsbody = $smsbody.$temp; 
     }
     else                       //process the returning result
     {
         //echo $smsbody."\n";
         if(! empty( $smsbody)) //可以处理连续空行的情形
         { 
            $result = parse_result($smsbody);
            //$result = json_decode($result,true);
            //var_dump($result);
            echo $result."\n";
            $count += 1; 
            $smsbody = ""; 
         }
     }
}
echo "$count"."\n";

fclose($handle);

?>

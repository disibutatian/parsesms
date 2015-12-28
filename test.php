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





function acquire_record($handle_of_result)
{
    $record = array(); 
    while( ! feof($handle_of_result) ) 
    {
        $temp = fgets($handle_of_result);
        //echo $temp;
        if( strlen( $temp ) > 2 )   //以空行为分界符 
        {
            $record = json_decode($temp,true);
            break;
        }
    }
    
    return array($record,$handle_of_result);

}


function is_consistency($arr_of_parse,$arr_of_result)
{
    if( array_key_exists('empty', $arr_of_result) ) //  此时应该解析结果应该无法匹配
    {
        if( $arr_of_parse["msg"] == "no matched pattern")
        {
            return true;
        }
        else
            return false;
    }
    else    //进一步进行比较判断
    {
        if($arr_of_parse["code"] == "100")
        {
            $arr = array();
            if(isset($arr_of_parse["global"] ) )
                $arr = $arr_of_parse["global"];  //获取解析后的数据信息
            
            foreach($arr as $key => $value) //遍历数组   
            {
                $val = int( float($value) );
                if( int( $arr_of_result[$key] ) != $val)
                    return false;
            }
            
            return true;
        }
        else
        {
            return false;
        }

           
    }
}

$handle = fopen("data.txt","r") or die("Unable to open file!");
$handle1 = fopen("result.txt","r") or die("Unable to open file!");

$smsbody = "";
$sum_count = 0;
$true_count = 0;

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
            
            $result = json_decode($result,true);
            //var_dump($result);

            $arr = acquire_record($handle1);
            $handle1 = $arr[1];
            $record = $arr[0];

            //var_dump($record);

            if(is_consistency($result,$record) )
            {
                $true_count += 1;
            }

            $sum_count += 1; 
            $smsbody = ""; 
         }
     }
}

echo "$true_count"."\n";
echo "$sum_count"."\n";

printf("%.2f\n", $true_count / $sum_count);
//echo $true_count / $sum_count;
//echo "\n";


fclose($handle);
fclose($handle1);

?>

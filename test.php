<?php

$handle = fopen("data.txt","r") or die("Unable to open file!");
$str = "";

while( !feof($handle) ) 
{
     $temp = fgets($handle);

     if( strlen( $temp ) > 2 )   //以空行为分界符 
     {
         $str = $str.$temp; 
     }
     else
     {
         echo $str."\n";
         $str = ""; 
     }
}
fclose($handle);

?>

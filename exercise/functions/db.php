<?php 

$con = mysqli_connect('localhost', 'root', '', 'login');

function row_count($result){
    
return mysqli_num_rows($result);
    
}


function escape($str){
    global $con;
     
     return mysqli_real_escape_string($con, $str);
}

 function query($query){
     global $con;
     
     return mysqli_query($con, $query);
     
 }

function confirm($result){
    global $con;
     
     if(!$result){
         die ("QUERY FAILED". mysqli_error($con));
     }
}

function fetch_array($r){
    global $con;
     
     return mysqli_fetch_array($r);
    
}


?>
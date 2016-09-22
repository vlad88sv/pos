<?php
 

if($_POST['consultaType']==="1"){
    if(isset($_POST['consulta'])){
        $c = $_POST['consulta'];
        $r = db_consultar($c);

        if (mysqli_num_rows($r) > 0)
        {
            while ($r && $f = db_fetch($r))
            {
                $json['aux'][] = $f;
            }
        }
        
    }
}else{
    
}
    
?>
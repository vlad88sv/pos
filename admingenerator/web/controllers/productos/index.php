<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/productos/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'ID_producto', 
		'ID_grupo', 
		'ID_menu', 
		'orden', 
		'nombre', 
		'descripcion', 
		'precio', 
		'nodo_sugerido', 
		'autodespacho', 
		'prioridad', 
		'disponible', 
		'descontinuado', 
		'complementar', 
		'creacion', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'tinyint(2)', 
		'varchar(100)', 
		'varchar(500)', 
		'decimal(6,2)', 
		'varchar(20)', 
		'bit(1)', 
		'enum(\'baja\',\'media\',\'alta\')', 
		'tinyint(1)', 
		'tinyint(4)', 
		'tinyint(1)', 
		'timestamp', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `productos`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `productos`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

            if($table_columns[$i] == 'ID_grupo'){
                $findexternal_sql = 'SELECT `descripcion` FROM `productos_grupos` WHERE `ID_grupo` = ?';
                $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
                $rows[$row_key][$table_columns[$i]] = $findexternal_row['descripcion'];

            }else if($table_columns[$i]=="ID_menu"  ){
                $findexternal_sql = 'SELECT catitem FROM `catalogo` WHERE `catalogo_id` = ?';
                $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
                $rows[$row_key][$table_columns[$i]] = $findexternal_row['catitem'];

            
                
            }else{
                $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
            }


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});


/* Download blob img */
$app->match('/productos/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . productos . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});


$app->match('/productos', function () use ($app) {
    
	$table_columns = array(
		'ID_producto', 
		'ID_grupo', 
		'ID_menu', 
		'orden', 
		'nombre', 
		'descripcion', 
		'precio', 
		'nodo_sugerido', 
		'autodespacho', 
		'prioridad', 
		'disponible', 
		'descontinuado', 
		'complementar', 
		'creacion', 

    );

    $primary_key = "ID_producto";	

    return $app['twig']->render('productos/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('productos_list');


$app->match('/productos/create', function () use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    $initial_data = array(
		'ID_grupo' => '', 
		'ID_menu' => '1', 
		'orden' => '0', 
		'nombre' => '', 
		'descripcion' => '', 
		'precio' => '', 
		'nodo_sugerido' => '', 
		'autodespacho' => true, 
		'prioridad' => '', 
		'disponible' => true, 
		'descontinuado' => false, 
		'complementar' => false, 
		'creacion' => date("Y-m-d h:i:sa"), 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
        
	$findexternal_sql = 'SELECT `ID_grupo`, `descripcion` FROM productos_grupos ORDER BY ID_grupo Asc';
        buildCatalog($app,$form, "ID_grupo", $findexternal_sql,"ID_grupo","descripcion", true);
        
        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='prioridad' AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "prioridad", $findexternal_sql,"catalogo_ID","catItem",false);
       
        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='nodo'  AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "nodo_sugerido", $findexternal_sql,"catalogo_ID","catItem",false);

        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='menu'  AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "ID_menu", $findexternal_sql,"catalogo_ID","catItem",true);
     /*  
//	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
//        
//	foreach($findexternal_rows as $findexternal_row){
//	    $options[$findexternal_row['ID_grupo']] = $findexternal_row['descripcion'];
//	}
//	if(count($options) > 0){
//	    $form = $form->add('ID_grupo', 'choice', array(
//	        'required' => true,
//	        'choices' => $options,
//	        'expanded' => false,
//	        'constraints' => new Assert\Choice(array_keys($options))
//	    ));
//	}
//	else{
//	    $form = $form->add('ID_grupo', 'text', array('required' => true));
//	}

       
       
//        $form = $form->add('prioridad', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsPrio,
//	        'expanded' => false,
//	        'constraints' => $optionsPrio
//	    ));

       
       
//        $form = $form->add('nodo_sugerido', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsNodo,
//	        'expanded' => false,
//	        'constraints' => $optionsNodo
//	    ));
        
       
       
//        $form = $form->add('ID_menu', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsMenu,
//	        'expanded' => false,
//	        'constraints' => new Assert\Choice(array_keys($optionsMenu))
//	    ));
        */
	//$form = $form->add('ID_menu', 'text', array('required' => true));
	$form = $form->add('orden', 'text', array('required' => true));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('descripcion', 'text', array('required' => true));
	$form = $form->add('precio', 'text', array('required' => true));
	//$form = $form->add('nodo_sugerido', 'text', array('required' => true));
	$form = $form->add('autodespacho', 'checkbox', array('required' => false));
	//$form = $form->add('prioridad', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => false));
	$form = $form->add('descontinuado', 'checkbox', array('required' => false));
	$form = $form->add('complementar', 'checkbox', array('required' => false));
	$form = $form->add('creacion', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `productos` (`ID_grupo`, `ID_menu`, `orden`, `nombre`, "
                    . "`descripcion`, `precio`, `nodo_sugerido`, `autodespacho`, `prioridad`, "
                    . "`disponible`, `descontinuado`, `complementar`, `creacion`) "
                    . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_grupo'], 
                $data['ID_menu'], $data['orden'], $data['nombre'], $data['descripcion'], 
                $data['precio'], $data['nodo_sugerido'], 
                $data['autodespacho'], 
                $data['prioridad'], 
                $data['disponible'], 
                $data['descontinuado'], 
                $data['complementar'], 
                $data['creacion']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'productos created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('productos_list'));

        }
    }

    return $app['twig']->render('productos/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('productos_create');


$app->match('/productos/edit/{id}', function ($id) use ($app) {
    
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $find_sql = "SELECT * FROM `productos` WHERE `ID_producto` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('productos_list'));
    }

    
    $initial_data = array(
		'ID_grupo' => $row_sql['ID_grupo'], 
		'ID_menu' => $row_sql['ID_menu'], 
		'orden' => $row_sql['orden'], 
		'nombre' => $row_sql['nombre'], 
		'descripcion' => $row_sql['descripcion'], 
		'precio' => $row_sql['precio'], 
		'nodo_sugerido' => $row_sql['nodo_sugerido'], 
		'autodespacho' => $row_sql['autodespacho']==="1"?true:false, 
		'prioridad' => $row_sql['prioridad'], 
		'disponible' => $row_sql['disponible']==="1"?true:false, 
		'descontinuado' => $row_sql['descontinuado']==="1"?true:false, 
		'complementar' => $row_sql['complementar']==="1"?true:false, 
		'creacion' => $row_sql['creacion'], 

    );

//date("m/d/Y h:i:s"),
    
    $form = $app['form.factory']->createBuilder('form', $initial_data);
/*
//	$options = array();
//	$findexternal_sql = 'SELECT `ID_grupo`, `descripcion` FROM `productos_grupos`';
//	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
//	foreach($findexternal_rows as $findexternal_row){
//	    $options[$findexternal_row['ID_grupo']] = $findexternal_row['descripcion'];
//	}
//	if(count($options) > 0){
//	    $form = $form->add('ID_grupo', 'choice', array(
//	        'required' => true,
//	        'choices' => $options,
//	        'expanded' => false,
//	        'constraints' => new Assert\Choice(array_keys($options))
//	    ));
//	}
//	else{
//	    $form = $form->add('ID_grupo', 'text', array('required' => true));
//	}
//
//        //$options = array('baja'=>'baja','media'=>'media','alta'=>'alta');
//        $form = $form->add('prioridad', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsPrio,
//	        'expanded' => false,
//	        'constraints' => $optionsPrio
//	    ));
//
//        //$options = array('comida'=>'comida','bebidas'=>'bebidas','General'=>'General');
//        $form = $form->add('nodo_sugerido', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsNodo,
//	        'expanded' => false,
//	        'constraints' => $optionsNodo
//	    ));
//        
//        //$options = array('entradas','carnes','mariscos','ensaladas','postres','sopas','bebidas calientes','bebidas frias','General');
//        $form = $form->add('ID_menu', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsMenu,
//	        'expanded' => false,
//	        'constraints' => new Assert\Choice(array_keys($optionsMenu))
//	    ));
*/
        $findexternal_sql = 'SELECT `ID_grupo`, `descripcion` FROM productos_grupos ORDER BY ID_grupo Asc';
        buildCatalog($app,$form, "ID_grupo", $findexternal_sql,"ID_grupo","descripcion", true);
        
        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='prioridad' AND  "
                . "active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "prioridad", $findexternal_sql,"catalogo_ID","catItem",false);
       
        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='nodo'  AND  "
                . "active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "nodo_sugerido", $findexternal_sql,"catalogo_ID","catItem",false);

        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='menu'  AND  "
                . "active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "ID_menu", $findexternal_sql,"catalogo_ID","catItem",true);


       //$form = $form->add('ID_menu', 'text', array('required' => true));
	$form = $form->add('orden', 'text', array('required' => true));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('descripcion', 'text', array('required' => true));
	$form = $form->add('precio', 'text', array('required' => true));
	//$form = $form->add('nodo_sugerido', 'text', array('required' => true));
	$form = $form->add('autodespacho', 'checkbox', array('required' => false));
	//$form = $form->add('prioridad', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => false));
	$form = $form->add('descontinuado', 'checkbox', array('required' => false));
	$form = $form->add('complementar', 'checkbox', array('required' => false));
	$form = $form->add('creacion', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
            
            $update_query = "UPDATE `productos` SET `ID_grupo` = ?, `ID_menu` = ?, `orden` = ?, `nombre` = ?, "
                    . "`descripcion` = ?, `precio` = ?, `nodo_sugerido` = ?, `autodespacho` = ?, `prioridad` = ?, "
                    . "`disponible` = ?, `descontinuado` = ?, `complementar` = ? WHERE `ID_producto` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_grupo'], $data['ID_menu'], 
                $data['orden'], $data['nombre'], $data['descripcion'], $data['precio'], $data['nodo_sugerido'], 
                $data['autodespacho'], 
                $data['prioridad'], 
                $data['disponible'], 
                $data['descontinuado'], 
                $data['complementar'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'productos edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('productos_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('productos/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('productos_edit');


$app->match('/productos/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `productos` WHERE `ID_producto` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
//        $delete_query = "DELETE FROM `productos` WHERE `ID_producto` = ?";
        $delete_query = "UPDATE `productos` SET disponible='0' WHERE `ID_producto` = ?";
        
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'productos deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('productos_list'));

})
->bind('productos_delete');







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

$app->match('/inventario/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    
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
		'inventario_id', 
		'producto_id', 
		'tipoOperacion', 
                'cantidadOperacion',
                'fechaOperacion',
                'usuarioOperacion',
		'Active', 

    );
    
    $table_columns_type = array(
		'int(11)', 
                'int(11)',
                'int(11)',
                'decimal(6,2)',
                'timestamp',
		'int(11)',  
		'bit(1)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `inventario`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `inventario`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){		

//            if($table_columns[$i] == 'ID_grupo'){
//                $findexternal_sql = 'SELECT `descripcion` FROM `productos_grupos` WHERE `ID_grupo` = ?';
//                $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
//                $rows[$row_key][$table_columns[$i]] = $findexternal_row['descripcion'];
//
//            }else if($table_columns[$i]=="ID_menu"  ){
//                $findexternal_sql = 'SELECT catitem FROM `catalogo` WHERE `catalogo_id` = ?';
//                $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
//                $rows[$row_key][$table_columns[$i]] = $findexternal_row['catitem'];
//
//            
//                
//            }else{
//                $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
//            }            
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
$app->match('/inventario/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . inventario . " WHERE ".$idfldname." = ?";
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

$app->match('/inventario', function () use ($app) {
    
	$table_columns = array(
		'inventario_id', 
		'CatName', 
		'CatItem', 
		'Active', 

    );

    $primary_key = "inventario_id";	

    return $app['twig']->render('inventario/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('inventario_list');

$app->match('/inventario/create', function () use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    $initial_data = array(
		'CatName' => '', 
		'CatItem' => '', 
		'Active' => true, 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

    $form = $form->add('CatName', 'choice', array(
	        'required' => true,
	        'choices' => $optionsCat,
	        'expanded' => false,
	        'constraints' => $optionsCat
	    ));
            
            
	//$form = $form->add('CatName', 'text', array('required' => true));
	$form = $form->add('CatItem', 'text', array('required' => true));
	$form = $form->add('Active', 'checkbox', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `inventario` (`CatName`, `CatItem`, `Active`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['CatName'], $data['CatItem'], $data['Active']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'inventario created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('inventario_list'));

        }
    }

    return $app['twig']->render('inventario/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('inventario_create');

$app->match('/inventario/edit/{id}', function ($id) use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $find_sql = "SELECT * FROM `inventario` WHERE `inventario_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('inventario_list'));
    }

    
    $initial_data = array(
		'CatName' => $row_sql['CatName'], 
		'CatItem' => $row_sql['CatItem'], 
		'Active' => $row_sql['Active']==="1"?true:false, 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

        $form = $form->add('CatName', 'choice', array(
	        'required' => true,
	        'choices' => $optionsCat,
	        'expanded' => false,
	        'constraints' => $optionsCat
	    ));
	//$form = $form->add('CatName', 'text', array('required' => true));
	$form = $form->add('CatItem', 'text', array('required' => true));
	$form = $form->add('Active', 'checkbox', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `inventario` SET `CatName` = ?, `CatItem` = ?, `Active` = ? WHERE `inventario_id` = ?";
            $app['db']->executeUpdate($update_query, array($data['CatName'], $data['CatItem'], $data['Active'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'inventario edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('inventario_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('inventario/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('inventario_edit');

$app->match('/inventario/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `inventario` WHERE `inventario_id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "UPDATE `inventario` SET active='0' WHERE `inventario_id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'inventario deleted!',
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

    return $app->redirect($app['url_generator']->generate('inventario_list'));

})
->bind('inventario_delete');







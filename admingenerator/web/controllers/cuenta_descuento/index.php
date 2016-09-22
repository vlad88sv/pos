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

$app->match('/cuenta_descuento/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_descuento', 
		'ID_cuenta', 
		'cantidad', 
		'fecha', 
		'razon', 

    );
    
    $table_columns_type = array(
		'int(10) unsigned', 
		'int(10) unsigned', 
		'decimal(8,2)', 
		'datetime', 
		'varchar(200)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `cuenta_descuento`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `cuenta_descuento`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
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
$app->match('/cuenta_descuento/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . cuenta_descuento . " WHERE ".$idfldname." = ?";
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



$app->match('/cuenta_descuento', function () use ($app) {
    
	$table_columns = array(
		'ID_descuento', 
		'ID_cuenta', 
		'cantidad', 
		'fecha', 
		'razon', 

    );

    $primary_key = "ID_descuento";	

    return $app['twig']->render('cuenta_descuento/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('cuenta_descuento_list');



$app->match('/cuenta_descuento/create', function () use ($app) {
    
    $initial_data = array(
		'ID_cuenta' => '', 
		'cantidad' => '', 
		'fecha' => '', 
		'razon' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ID_cuenta', 'text', array('required' => true));
	$form = $form->add('cantidad', 'text', array('required' => true));
	$form = $form->add('fecha', 'text', array('required' => true));
	$form = $form->add('razon', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `cuenta_descuento` (`ID_cuenta`, `cantidad`, `fecha`, `razon`) VALUES (?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_cuenta'], $data['cantidad'], $data['fecha'], $data['razon']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuenta_descuento created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuenta_descuento_list'));

        }
    }

    return $app['twig']->render('cuenta_descuento/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('cuenta_descuento_create');



$app->match('/cuenta_descuento/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cuenta_descuento` WHERE `ID_descuento` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('cuenta_descuento_list'));
    }

    
    $initial_data = array(
		'ID_cuenta' => $row_sql['ID_cuenta'], 
		'cantidad' => $row_sql['cantidad'], 
		'fecha' => $row_sql['fecha'], 
		'razon' => $row_sql['razon'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ID_cuenta', 'text', array('required' => true));
	$form = $form->add('cantidad', 'text', array('required' => true));
	$form = $form->add('fecha', 'text', array('required' => true));
	$form = $form->add('razon', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `cuenta_descuento` SET `ID_cuenta` = ?, `cantidad` = ?, `fecha` = ?, `razon` = ? WHERE `ID_descuento` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_cuenta'], $data['cantidad'], $data['fecha'], $data['razon'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuenta_descuento edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuenta_descuento_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('cuenta_descuento/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('cuenta_descuento_edit');



$app->match('/cuenta_descuento/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cuenta_descuento` WHERE `ID_descuento` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `cuenta_descuento` WHERE `ID_descuento` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'cuenta_descuento deleted!',
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

    return $app->redirect($app['url_generator']->generate('cuenta_descuento_list'));

})
->bind('cuenta_descuento_delete');







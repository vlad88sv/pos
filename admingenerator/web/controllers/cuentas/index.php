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

$app->match('/cuentas/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_cuenta', 
		'ID_domicilio', 
		'ID_mesa', 
		'flag_pagado', 
		'flag_nopropina', 
		'flag_exento', 
		'flag_tiquetado', 
		'flag_anulado', 
		'metodo_pago', 
		'ID_mesero', 
		'ID_usuario', 
		'fechahora_pagado', 
		'fechahora_anulado', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'varchar(20)', 
		'bit(1)', 
		'bit(1)', 
		'bit(1)', 
		'bit(1)', 
		'bit(1)', 
		'enum(\'efectivo\',\'tarjeta\')', 
		'int(11)', 
		'int(11)', 
		'datetime', 
		'datetime', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `cuentas`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `cuentas`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/cuentas/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . cuentas . " WHERE ".$idfldname." = ?";
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



$app->match('/cuentas', function () use ($app) {
    
	$table_columns = array(
		'ID_cuenta', 
		'ID_domicilio', 
		'ID_mesa', 
		'flag_pagado', 
		'flag_nopropina', 
		'flag_exento', 
		'flag_tiquetado', 
		'flag_anulado', 
		'metodo_pago', 
		'ID_mesero', 
		'ID_usuario', 
		'fechahora_pagado', 
		'fechahora_anulado', 

    );

    $primary_key = "ID_cuenta";	

    return $app['twig']->render('cuentas/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('cuentas_list');



$app->match('/cuentas/create', function () use ($app) {
    
    $initial_data = array(
		'ID_domicilio' => '', 
		'ID_mesa' => '', 
		'flag_pagado' => '', 
		'flag_nopropina' => '', 
		'flag_exento' => '', 
		'flag_tiquetado' => '', 
		'flag_anulado' => '', 
		'metodo_pago' => '', 
		'ID_mesero' => '', 
		'ID_usuario' => '', 
		'fechahora_pagado' => '', 
		'fechahora_anulado' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ID_domicilio', 'text', array('required' => true));
	$form = $form->add('ID_mesa', 'text', array('required' => true));
	$form = $form->add('flag_pagado', 'text', array('required' => true));
	$form = $form->add('flag_nopropina', 'text', array('required' => true));
	$form = $form->add('flag_exento', 'text', array('required' => true));
	$form = $form->add('flag_tiquetado', 'text', array('required' => true));
	$form = $form->add('flag_anulado', 'text', array('required' => true));
	$form = $form->add('metodo_pago', 'text', array('required' => true));
	$form = $form->add('ID_mesero', 'text', array('required' => true));
	$form = $form->add('ID_usuario', 'text', array('required' => true));
	$form = $form->add('fechahora_pagado', 'text', array('required' => true));
	$form = $form->add('fechahora_anulado', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `cuentas` (`ID_domicilio`, `ID_mesa`, `flag_pagado`, `flag_nopropina`, `flag_exento`, `flag_tiquetado`, `flag_anulado`, `metodo_pago`, `ID_mesero`, `ID_usuario`, `fechahora_pagado`, `fechahora_anulado`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_domicilio'], $data['ID_mesa'], $data['flag_pagado'], $data['flag_nopropina'], $data['flag_exento'], $data['flag_tiquetado'], $data['flag_anulado'], $data['metodo_pago'], $data['ID_mesero'], $data['ID_usuario'], $data['fechahora_pagado'], $data['fechahora_anulado']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuentas created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuentas_list'));

        }
    }

    return $app['twig']->render('cuentas/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('cuentas_create');



$app->match('/cuentas/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cuentas` WHERE `ID_cuenta` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('cuentas_list'));
    }

    
    $initial_data = array(
		'ID_domicilio' => $row_sql['ID_domicilio'], 
		'ID_mesa' => $row_sql['ID_mesa'], 
		'flag_pagado' => $row_sql['flag_pagado'], 
		'flag_nopropina' => $row_sql['flag_nopropina'], 
		'flag_exento' => $row_sql['flag_exento'], 
		'flag_tiquetado' => $row_sql['flag_tiquetado'], 
		'flag_anulado' => $row_sql['flag_anulado'], 
		'metodo_pago' => $row_sql['metodo_pago'], 
		'ID_mesero' => $row_sql['ID_mesero'], 
		'ID_usuario' => $row_sql['ID_usuario'], 
		'fechahora_pagado' => $row_sql['fechahora_pagado'], 
		'fechahora_anulado' => $row_sql['fechahora_anulado'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ID_domicilio', 'text', array('required' => true));
	$form = $form->add('ID_mesa', 'text', array('required' => true));
	$form = $form->add('flag_pagado', 'text', array('required' => true));
	$form = $form->add('flag_nopropina', 'text', array('required' => true));
	$form = $form->add('flag_exento', 'text', array('required' => true));
	$form = $form->add('flag_tiquetado', 'text', array('required' => true));
	$form = $form->add('flag_anulado', 'text', array('required' => true));
	$form = $form->add('metodo_pago', 'text', array('required' => true));
	$form = $form->add('ID_mesero', 'text', array('required' => true));
	$form = $form->add('ID_usuario', 'text', array('required' => true));
	$form = $form->add('fechahora_pagado', 'text', array('required' => true));
	$form = $form->add('fechahora_anulado', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `cuentas` SET `ID_domicilio` = ?, `ID_mesa` = ?, `flag_pagado` = ?, `flag_nopropina` = ?, `flag_exento` = ?, `flag_tiquetado` = ?, `flag_anulado` = ?, `metodo_pago` = ?, `ID_mesero` = ?, `ID_usuario` = ?, `fechahora_pagado` = ?, `fechahora_anulado` = ? WHERE `ID_cuenta` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_domicilio'], $data['ID_mesa'], $data['flag_pagado'], $data['flag_nopropina'], $data['flag_exento'], $data['flag_tiquetado'], $data['flag_anulado'], $data['metodo_pago'], $data['ID_mesero'], $data['ID_usuario'], $data['fechahora_pagado'], $data['fechahora_anulado'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cuentas edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cuentas_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('cuentas/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('cuentas_edit');



$app->match('/cuentas/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cuentas` WHERE `ID_cuenta` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `cuentas` WHERE `ID_cuenta` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'cuentas deleted!',
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

    return $app->redirect($app['url_generator']->generate('cuentas_list'));

})
->bind('cuentas_delete');







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

$app->match('/compras/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_compra', 
		'empresa', 
		'descripcion', 
		'precio', 
		'fechatiempo', 
		'via', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(100)', 
		'varchar(250)', 
		'decimal(10,2)', 
		'datetime', 
		'enum(\'caja\',\'cheque\')', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `compras`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `compras`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/compras/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . compras . " WHERE ".$idfldname." = ?";
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


$app->match('/compras', function () use ($app) {
    
	$table_columns = array(
		'ID_compra', 
		'empresa', 
		'descripcion', 
		'precio', 
		'fechatiempo', 
		'via', 

    );

    $primary_key = "ID_compra";	

    return $app['twig']->render('compras/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('compras_list');


$app->match('/compras/create', function () use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $initial_data = array(
		'empresa' => '', 
		'descripcion' => '', 
		'precio' => '', 
		'fechatiempo' => date("Y-m-d h:i:sa"), 
		'via' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='PagoCompra' AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "via", $findexternal_sql,"catalogo_ID","catItem",false);

	$form = $form->add('empresa', 'text', array('required' => true));
	$form = $form->add('descripcion', 'text', array('required' => true));
	$form = $form->add('precio', 'text', array('required' => true));
	$form = $form->add('fechatiempo', 'text', array('required' => false)); //, array('required' => true)
	//$form = $form->add('via', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `compras` (`empresa`, `descripcion`, `precio`, `fechatiempo`, `via`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['empresa'], $data['descripcion'], $data['precio'], $data['fechatiempo'], $data['via']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'compras created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('compras_list'));

        }
    }

    return $app['twig']->render('compras/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('compras_create');


$app->match('/compras/edit/{id}', function ($id) use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $find_sql = "SELECT * FROM `compras` WHERE `ID_compra` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('compras_list'));
    }

    
    $initial_data = array(
		'empresa' => $row_sql['empresa'], 
		'descripcion' => $row_sql['descripcion'], 
		'precio' => $row_sql['precio'], 
		'fechatiempo' => $row_sql['fechatiempo'], 
		'via' => $row_sql['via'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

         $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='PagoCompra' AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "via", $findexternal_sql,"catalogo_ID","catItem",false);
        
	$form = $form->add('empresa', 'text', array('required' => true));
	$form = $form->add('descripcion', 'text', array('required' => true));
	$form = $form->add('precio', 'text', array('required' => true));
	$form = $form->add('fechatiempo', 'text', array('required' => true));
	//$form = $form->add('via', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `compras` SET `empresa` = ?, `descripcion` = ?, `precio` = ?, `fechatiempo` = ?, `via` = ? WHERE `ID_compra` = ?";
            $app['db']->executeUpdate($update_query, array($data['empresa'], $data['descripcion'], $data['precio'], $data['fechatiempo'], $data['via'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'compras edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('compras_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('compras/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('compras_edit');


$app->match('/compras/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `compras` WHERE `ID_compra` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `compras` WHERE `ID_compra` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'compras deleted!',
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

    return $app->redirect($app['url_generator']->generate('compras_list'));

})
->bind('compras_delete');







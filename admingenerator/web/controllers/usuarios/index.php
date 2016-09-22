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

$app->match('/usuarios/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_usuarios', 
		'usuario', 
		'clave', 
		'nivel', 
		'restriccion_grupo', 
		'disponible', 
		'grupo', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(10)', 
		'char(40)', 
		'enum(\'domicilio\',\'mesero\',\'cocina\',\'impresion\',\'gerente\',\'master\')', 
		'tinyint(1)', 
		'tinyint(1)', 
		'varchar(10)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `usuarios`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `usuarios`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
                                
		} else if($table_columns[$i]=="nivel"  ){
                    $findexternal_sql = 'SELECT catitem FROM `catalogo` WHERE `catalogo_id` = ?';
                    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
                    $rows[$row_key][$table_columns[$i]] = $findexternal_row['catitem'];
                    
                }else {				if( !$row_sql[$table_columns[$i]] ) {
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
$app->match('/usuarios/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . usuarios . " WHERE ".$idfldname." = ?";
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


$app->match('/usuarios', function () use ($app) {
    
	$table_columns = array(
		'ID_usuarios', 
		'usuario', 
		'clave', 
		'nivel', 
		'restriccion_grupo', 
		'disponible', 
		'grupo', 

    );

    $primary_key = "ID_usuarios";	

    return $app['twig']->render('usuarios/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('usuarios_list');


$app->match('/usuarios/create', function () use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    $initial_data = array(
		'usuario' => '', 
		'clave' => '', 
		'nivel' => '', 
		'restriccion_grupo' => '0', 
		'disponible' => true, 
		'grupo' => '0', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='UsrNivel'  AND  active='1' "
                . "ORDER BY catItem Asc";
        buildCatalog($app,$form, "nivel", $findexternal_sql,"catalogo_ID","catItem",false);
        
//        $form = $form->add('nivel', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsUsrNivel,
//	        'expanded' => false,
//	        'constraints' => $optionsUsrNivel
//	    ));
	$form = $form->add('usuario', 'text', array('required' => true));
	$form = $form->add('clave', 'text', array('required' => true));
	//$form = $form->add('nivel', 'text', array('required' => true));
	$form = $form->add('restriccion_grupo', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => true));
	$form = $form->add('grupo', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `usuarios` (`usuario`, `clave`, `nivel`, `restriccion_grupo`, "
                    . "`disponible`, `grupo`) VALUES (?, sha1(?), ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['usuario'], $data['clave'], 
                $data['nivel'], $data['restriccion_grupo'], $data['disponible'], $data['grupo']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_list'));

        }
    }

    return $app['twig']->render('usuarios/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('usuarios_create');


$app->match('/usuarios/edit/{id}', function ($id) use ($app) {
require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    $find_sql = "SELECT * FROM `usuarios` WHERE `ID_usuarios` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('usuarios_list'));
    }

    
    $initial_data = array(
		'usuario' => $row_sql['usuario'], 
		'clave' => $row_sql['clave'], 
		'nivel' => $row_sql['nivel'], 
		'restriccion_grupo' => $row_sql['restriccion_grupo'], 
		'disponible' => $row_sql['disponible']?true:false, 
		'grupo' => $row_sql['grupo'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

   
//        $form = $form->add('nivel', 'choice', array(
//	        'required' => true,
//	        'choices' => $optionsUsrNivel,
//	        'expanded' => false,
//	        'constraints' => $optionsUsrNivel
//	    ));

        $findexternal_sql = "SELECT `catalogo_ID`, `catItem` FROM `catalogo` WHERE catname='UsrNivel'  AND  active='1' ORDER BY catItem Asc";
        buildCatalog($app,$form, "nivel", $findexternal_sql,"catalogo_ID","catItem",false);
        
	$form = $form->add('usuario', 'text', array('required' => true));
	$form = $form->add('clave', 'password', array('required' => true));
//$form = $form->add('nivel', 'text', array('required' => true));
	$form = $form->add('restriccion_grupo', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => false));
	$form = $form->add('grupo', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `usuarios` SET `usuario` = ?, `clave` = sha1(?), `nivel` = ?, "
                    . "`restriccion_grupo` = ?, `disponible` = ?, `grupo` = ? WHERE `ID_usuarios` = ?";
            $app['db']->executeUpdate($update_query, array($data['usuario'], $data['clave'], 
                $data['nivel'], $data['restriccion_grupo'], $data['disponible'], $data['grupo'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('usuarios/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('usuarios_edit');


$app->match('/usuarios/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `usuarios` WHERE `ID_usuarios` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "UPDATE `usuarios` set disponible=0 WHERE `ID_usuarios` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'usuarios deleted!',
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

    return $app->redirect($app['url_generator']->generate('usuarios_list'));

})
->bind('usuarios_delete');

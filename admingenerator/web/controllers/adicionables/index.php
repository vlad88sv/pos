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

$app->match('/adicionables/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_adicional', 
		'precio', 
		'ID_grupo', 
		'disponible', 
		'nombre', 
		'afinidad', 
		'importante', 
		'flag_estadistico', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'decimal(10,2)', 
		'int(11)', 
		'tinyint(1)', 
		'varchar(50)', 
		'tinyint(2)', 
		'tinyint(4)', 
		'tinyint(1)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `adicionables`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `adicionables`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'ID_grupo'){
			    $findexternal_sql = 'SELECT `descripcion` FROM `productos_grupos` WHERE `ID_grupo` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['descripcion'];
			}
			else{
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
$app->match('/adicionables/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . adicionables . " WHERE ".$idfldname." = ?";
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


$app->match('/adicionables', function () use ($app) {
    
	$table_columns = array(
		'ID_adicional', 
		'precio', 
		'ID_grupo', 
		'disponible', 
		'nombre', 
		'afinidad', 
		'importante', 
		'flag_estadistico', 

    );

    $primary_key = "ID_adicional";	

    return $app['twig']->render('adicionables/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('adicionables_list');


$app->match('/adicionables/create', function () use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    $initial_data = array(
		'precio' => '', 
		'ID_grupo' => '-1', 
		'disponible' => true, 
		'nombre' => '', 
		'afinidad' => '1', 
		'importante' => '1', 
		'flag_estadistico' => true, 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);
        $findexternal_sql = 'SELECT `ID_grupo`, `descripcion` FROM productos_grupos ORDER BY ID_grupo Asc';
        buildCatalog($app,$form, "ID_grupo", $findexternal_sql,"ID_grupo","descripcion", true);
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
*/

	$form = $form->add('precio', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => false));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('afinidad', 'text', array('required' => true));
	$form = $form->add('importante', 'text', array('required' => true));
	$form = $form->add('flag_estadistico', 'checkbox', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `adicionables` (`precio`, `ID_grupo`, `disponible`, `nombre`, `afinidad`, `importante`, `flag_estadistico`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['precio'], $data['ID_grupo'], $data['disponible'], $data['nombre'], $data['afinidad'], $data['importante'], $data['flag_estadistico']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'adicionables created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('adicionables_list'));

        }
    }

    return $app['twig']->render('adicionables/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('adicionables_create');


$app->match('/adicionables/edit/{id}', function ($id) use ($app) {
    require_once ($_SERVER['DOCUMENT_ROOT'].'/pos/admingenerator/web/controllers/catalogs.php');
    
    $find_sql = "SELECT * FROM `adicionables` WHERE `ID_adicional` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('adicionables_list'));
    }

    
    $initial_data = array(
		'precio' => $row_sql['precio'], 
		'ID_grupo' => $row_sql['ID_grupo'], 
		'disponible' => $row_sql['disponible']?true:false, 
		'nombre' => $row_sql['nombre'], 
		'afinidad' => $row_sql['afinidad'], 
		'importante' => $row_sql['importante'], 
		'flag_estadistico' => $row_sql['flag_estadistico']?true:false, 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);
        
        $findexternal_sql = 'SELECT `ID_grupo`, `descripcion` FROM productos_grupos ORDER BY ID_grupo Asc';
        buildCatalog($app,$form, "ID_grupo", $findexternal_sql,"ID_grupo","descripcion", true);
        
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


	$form = $form->add('precio', 'text', array('required' => true));
	$form = $form->add('disponible', 'checkbox', array('required' => false));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('afinidad', 'text', array('required' => true));
	$form = $form->add('importante', 'text', array('required' => true));
	$form = $form->add('flag_estadistico', 'checkbox', array('required' => false));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `adicionables` SET `precio` = ?, `ID_grupo` = ?, `disponible` = ?, `nombre` = ?, "
                    . "`afinidad` = ?, `importante` = ?, `flag_estadistico` = ? WHERE `ID_adicional` = ?";
            $app['db']->executeUpdate($update_query, array($data['precio'],  $data['ID_grupo'],  $data['disponible'], 
                $data['nombre'], $data['afinidad'], $data['importante'], $data['flag_estadistico'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'adicionables edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('adicionables_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('adicionables/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('adicionables_edit');


$app->match('/adicionables/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `adicionables` WHERE `ID_adicional` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `adicionables` WHERE `ID_adicional` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'adicionables deleted!',
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

    return $app->redirect($app['url_generator']->generate('adicionables_list'));

})
->bind('adicionables_delete');







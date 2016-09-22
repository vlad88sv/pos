<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Symfony\Component\Validator\Constraints as Assert;

//$optionsPrio = array('baja'=>'Baja','media'=>'Media','alta'=>'Alta');
//$optionsNodo = array('comida'=>'Comida','bebidas'=>'Bebidas','General'=>'General');
//$optionsMenu = array('Entradas','Carnes','Mariscos','Ensaladas','Postres','Sopas','Bebidas calientes','Bebidas frias','General','Infantil');
//$optionsUsrNivel = array('domicilio'=>'domicilio','mesero'=>'mesero','cocina'=>'cocina','impresion'=>'impresion',
//    'gerente'=>'gerente','master'=>'master');

$optionsCat = array('Prioridad'=>'Prioridad','Nodo'=>'Nodo','Menu'=>'Menu','UsrNivel'=>'UsrNivel',
    'PagoCompra'=>'PagoCompra','EstadoCorteZ'=>'EstadoCorteZ','MetodoPago'=>'MetodoPago','DocFiscal'=>'DocFiscal',
    'DetalleFacturacion'=>'DetalleFacturacion','TipoPedido'=>'TipoPedido','tipoOperacionInv'=>'tipoOperacionInv');

function buildCatalog($app,$form, $foreingKey, $findexternal_sql, $catKey, $catDesc, $useKey){
    $options = array();
    
    $findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
        
    foreach($findexternal_rows as $findexternal_row){
        if ($useKey){
            $options[$findexternal_row[$catKey]] = $findexternal_row[$catDesc];
        }else{
            if($findexternal_row[$catDesc]!=null)
                $options[$findexternal_row[$catDesc]] = $findexternal_row[$catDesc];
        }
    }
	if(count($options) > 0){
	    $form = $form->add($foreingKey, 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add($foreingKey, 'text', array('required' => true));
	}
}

        
?>
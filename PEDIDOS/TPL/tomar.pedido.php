<script type="text/javascript" src="JS/tomar_pedido.js"></script>
 
<div id="dialog-password" title="Ingrese clave" style="display:none;">
    <input id="dialog-password-input" type ="password" />
</div>

<?php $_html['titulo'] = 'Tomar pedido'; ?>
<table style="color:white;font-weight: bold;width:100%;margin: 0px;border-bottom: 2px solid black;background-image: url(../img/background.jpg);">
    <tr>
        <td align="center">
                <img src="../img/logo.jpg" />
        </td>
        <td style="text-align:left;">Búscar: 
            <input class="key enfocar" key="88" type="text" id="buscar_producto" value="" /></td>
        <td style="text-align: right;vertical-align: middle;">
            [ <input type="checkbox" id="modo_tactil" value="1" /> <label style="font-size:10pt" for="modo_tactil">táctil</label> ]&nbsp; 
            <button class="key" key="66" id="borrar_orden"><b>B</b>orrar</button>&nbsp;
            <button class="key" key="82" id="ver_resumen"><b>R</b>esumen</button>
            <button class="key" key="69" id="enviar_orden_a_cocina"><b>E</b>nviar</button>
        </td>
    </tr>
</table>


<div id="scroller" style="overflow-x:auto;background-image: url(../img/background.jpg);"></div>
<div id="info_principal" style="overflow-x:auto;color:white;font-weight: bold;background-image: url(../img/background.jpg);"></div>

<table id="menu_productos" style="font-weight: bold;">
    <tr>
<?php

include_once '../SERV/PHP/db.php';

    $c = "SELECT catalogo_id, catitem FROM catalogo WHERE active='1' AND catname='menu' ORDER BY catitem";
    
    $r = db_consultar($c);
    while ($f = db_fetch($r))
    {?>
        <td ><a class='mp key' rel='<?php echo trim($f['catalogo_id'])?>' key='<?php echo trim($f['catalogo_id'])?>'
               href='#'><?php echo strtoupper($f['catitem']); ?></a></td>
    <?php } ?>
        <td><a class="mp key" rel="99" key="99" href="#">RESET</a></td><td></td>
                
<!--        <td><a class="mp key" rel="6" key="0" href="#">A picar se ha dicho</a></td>
        <td><a class="mp key" rel="7" key="1" href="#">Bebidas</a></td>
        <td><a class="mp key" rel="8" key="2" href="#">Ceviches y Cocteles</a></td>
        <td><a class="mp key" rel="9" key="3" href="#">Ensaladas</a></td>
        <td><a class="mp key" rel="10" key="4" href="#">Econo steak</a></td>
        <td><a class="mp key" rel="11" key="5" href="#">Gourmet</a></td>
        <td><a class="mp key" rel="12" key="6" href="#">Kids steak</a></td>
        <td><a class="mp key" rel="13" key="7" href="#">Sopas</a></td>
        <td><a class="mp key" rel="14" key="8" href="#">Steaks House</a></td>
        <td><a class="mp key" rel="37" key="9" href="#">Pastas/Pizzas</a></td>
        <td><a class="mp key" rel="38" key="10" href="#">Postres</a></td>
        <td><a class="mp key" rel="39" key="11" href="#">Xpress Ejec</a></td>
        <td><a class="mp key" rel="40" key="12" href="#">Aves</a></td>
         <td><a class="mp key" rel="9" key="57" href="#">9.TINTO</a></td>
        <td><a class="mp key" rel="10" key="48" href="#">0.BLANCO</a></td>
        <td><a class="mp key" rel="11" key="171" href="#">*.CHAMPAGNE</a></td> -->
    </tr>
</table>
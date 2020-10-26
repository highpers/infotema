<?php

/**
 * Presenta un objeto o array en forma ordenada
 *
 * @param string, array, object $obj
 */
function muestraArrayUObjeto($obj, $arch = '', $linea = '', $die = 0, $conDump = 0)
{
    ////muestraArrayUobjeto($band , __FILE__ , __LINE__ , 1 , 0);

    echo "En archivo $arch - línea $linea ";
    echo '<pre>';
    if (!$conDump)
        print_r($obj);
    else
        var_dump($obj);
    echo '</pre>';

    if ($die)
        die();
}
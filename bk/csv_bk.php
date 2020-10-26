<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require('config.php');
include('../inc/ez_sql.php');
require('../inc/funcs.php');


/**
 * Contiene el nombre de cada archivo con su respectiva consulta sql.
 * Devuelve un array con nombre de archivo y consulta
 * 
 * @return array();
 * 
 */

function get_archivos_y_consultas()
{

    $csv_consulta[0]['archivo'] = 'BonosPagados.csv';
    $csv_consulta[0]['consulta'] = 'SELECT solicitude.date_expedient AS "Caratula"
, solicitude.expedient AS "Expediente"
, companies.cuit AS "CUIT"
, companies.business_name AS "Razon Social"
, solicitude.category AS "Art9"
, FORMAT(bonuses.amount, 2, "de_DE") AS "Monto"
, IF(STRCMP(solicitude.tiene_bono_i_mas_d, "EVALUADO") = 0, "SI", "NO") AS "Bomo I+D"
, DATE_FORMAT(lotes.created_at, "%d/%m/%Y") AS "Lote"
, solicitude.fractions AS "Fraccionamiento"
, DATEDIFF(lotes.created_at,STR_TO_DATE(REPLACE(solicitude.date_expedient, "/"," "), "%d %m %Y")) AS "Tiempos de Tramitacion"
FROM `solicitude` 
INNER JOIN companies ON companies.id = solicitude.company_id
INNER JOIN lotes ON lotes.id = solicitude.lote_id
INNER JOIN bonuses ON bonuses.solicitude_id = solicitude.id
WHERE 1 = 1
AND EXTRACT(YEAR_MONTH FROM STR_TO_DATE(lotes.created_at, "%Y-%m-%d")) = 202006
GROUP BY bonuses.id
ORDER BY lotes.created_at DESC';

    $csv_consulta[1]['archivo'] = 'RankingEmpresas.csv';
    $csv_consulta[1]['consulta'] = "SELECT companies.cuit AS 'CUIT'
, companies.business_name AS 'Razón Social'
, COUNT(bonuses.id) AS 'Cantidad de bonos cobrados'
, FORMAT(SUM(bonuses.amount), 2, 'de_DE') AS 'Monto total pagado'
, SUM(IF(STRCMP(solicitude.tiene_bono_i_mas_d, 'EVALUADO') = 0, 1, 0)) AS 'Cantidad de Bonos I+D'
, FORMAT(SUM(bonuses.amount)/(SELECT SUM(bonuses.amount) FROM `bonuses` INNER JOIN solicitude ON bonuses.solicitude_id = solicitude.id INNER JOIN lotes ON lotes.id = solicitude.lote_id)*100, 2, 'de_DE') AS 'Porcentaje del total de bonos pagados'
FROM companies
INNER JOIN solicitude ON companies.id = solicitude.company_id
INNER JOIN bonuses ON solicitude.id = bonuses.solicitude_id
INNER JOIN lotes ON lotes.id = solicitude.lote_id
GROUP BY solicitude.company_id
ORDER BY SUM(bonuses.amount) DESC";

    $csv_consulta[2]['archivo'] = 'ResumenDeOperatoriaPorLotes.csv';
    $csv_consulta[2]['consulta'] = "SELECT DATE_FORMAT(lotes.created_at, '%M %Y') AS 'Mes de creación de lote'
, DATE_FORMAT(lotes.created_at, '%e-%m') AS 'Fecha de creación'
, COUNT(bonuses.id) AS 'Cantidad de expedientes'
, SUM(solicitude.fractions) AS 'Cantidad de fracciones'
, FORMAT(SUM(bonuses.amount), 2, 'de_DE') AS 'Monto pagado en bonos F e I+D'
, SUM(IF(STRCMP(solicitude.tiene_bono_i_mas_d, 'EVALUADO') = 0, 1, 0)) AS 'Cantidad de Bonos I+D'
, COUNT(tabla_a.bon_id) AS 'Cantidad de expedientes de empresas A'
, FORMAT(IFNULL(SUM(tabla_a.monto), 0), 2, 'de_DE') AS 'Monto total pagado a empresas A'
, COUNT(tabla_b.bon_id) AS 'cantidad de expedientes de empresas B'
, FORMAT(IFNULL(SUM(tabla_b.monto), 0), 2, 'de_DE') AS 'Monto total pagado a empresas B'
FROM `solicitude` 
INNER JOIN lotes ON lotes.id = solicitude.lote_id
INNER JOIN bonuses ON bonuses.solicitude_id = solicitude.id
LEFT JOIN (SELECT bonuses.id AS 'bon_id'
, bonuses.amount AS 'monto'
FROM `solicitude` 
INNER JOIN bonuses ON bonuses.solicitude_id = solicitude.id
WHERE solicitude.category = 'B') AS tabla_b ON tabla_b.bon_id = bonuses.id
LEFT JOIN (SELECT bonuses.id AS 'bon_id'
, bonuses.amount AS 'monto'
FROM `solicitude` 
INNER JOIN bonuses ON bonuses.solicitude_id = solicitude.id
WHERE solicitude.category = 'A') AS tabla_a ON tabla_a.bon_id = bonuses.id
WHERE 1 = 1
#AND EXTRACT(YEAR_MONTH FROM STR_TO_DATE(lotes.created_at, '%Y-%m-%d')) = 202004
GROUP BY lotes.id
ORDER BY lotes.created_at ASC";

    $csv_consulta[3]['archivo'] = 'MontosPorCuit.csv';
    $csv_consulta[3]['consulta'] = "SELECT companies.cuit AS 'CUIT'
, COUNT(bonuses.id) AS 'Cantidad de bonos pagados'
, FORMAT(SUM(bonuses.amount), 2, 'de_DE') AS 'Monto total pagado'
, SUM(IF(STRCMP(solicitude.tiene_bono_i_mas_d, 'EVALUADO') = 0, 1, 0)) AS 'Cantidad de Bonos I+D'
FROM companies
INNER JOIN solicitude ON companies.id = solicitude.company_id
INNER JOIN bonuses ON solicitude.id = bonuses.solicitude_id
INNER JOIN lotes ON lotes.id = solicitude.lote_id
#WHERE YEAR(lotes.created_at) = 2020
GROUP BY solicitude.company_id
ORDER BY companies.cuit ASC";

    return $csv_consulta;
}


function get_datos_csv($sql)
{

    global $db;
    // muestraArrayUobjeto($sql , __FILE__ , __LINE__ , 1 , 0);
    $ds = $db->get_results($sql, ARRAY_A);
    return $ds;
}



function generar_csv($file, $dataset)
{
    $file = DIR_CSV . $file ; 
   $fp = fopen($file, 'w');
   if(!$fp){
     return false;
   } 
    // muestraArrayUobjeto($dataset , __FILE__ , __LINE__ , 1 , 0);
    
    // genero la línea de títulos de columna
    $encabezados = array();
    foreach($dataset[0] as $k=>$v){
        $encabezados[] = $k ;
    }
    fputcsv($fp , $encabezados);
    foreach ($dataset as $campos) {
        fputcsv($fp, $campos , ';');
    }
    fclose($fp);

    return true;
   
}

/////////////////////////////////////////////////////

$datos_csv_consulta = get_archivos_y_consultas();

$mensaje = '';

foreach ($datos_csv_consulta as $data) {

    $dataset = get_datos_csv($data['consulta']);

    if (generar_csv($data['archivo'],$dataset)) {
        $mensaje .= '<p>Se generó el archivo ' . $data['archivo'] . '</p>';
    } else {
        $mensaje .= '<p style="color:red">Error - no se pudo generar el archivo ' . $data['archivo'] . '</p>';
    }
}

echo $mensaje ;

    
// generar_csv();

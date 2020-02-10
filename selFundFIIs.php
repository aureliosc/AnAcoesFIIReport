<?php

// Array
// (
//     [0] => CÃ³digodo fundo
//     [1] => Setor
//     [2] => PreÃ§o Atual
//     [3] => Liquidez DiÃ¡ria
//     [4] => Dividendo
//     [5] => DividendYield
//     [6] => DY (3M)Acumulado
//     [7] => DY (6M)Acumulado
//     [8] => DY (12M)Acumulado
//     [9] => DY (3M)MÃ©dia
//     [10] => DY (6M)MÃ©dia
//     [11] => DY (12M)MÃ©dia
//     [12] => DY Ano
//     [13] => VariaÃ§Ã£o PreÃ§o
//     [14] => Rentab.PerÃ­odo
//     [15] => Rentab.Acumulada
//     [16] => PatrimÃ´nioLÃ­q.
//     [17] => VPA
//     [18] => P/VPA
//     [19] => DYPatrimonial
//     [20] => VariaÃ§Ã£oPatrimonial
//     [21] => Rentab. Patr.no PerÃ­odo
//     [22] => Rentab. Patr.Acumulada
//     [23] => VacÃ¢nciaFÃ­sica
//     [24] => VacÃ¢nciaFinanceira
//     [25] => QuantidadeAtivos
//     [26] => Nome
// )

header("Content-type: text/html; charset=utf-8");

require 'libs/php-export-data.class.php';
require 'libs/XPathWrapper.php';

/* Load the HTML */
$strUrl = "https://www.fundsexplorer.com.br/ranking";

$strTipoSaida = 'csv';

$numMinDY01M = 0.4;
$numMinDY03M = 1;
$numMinDY06M = 2;
$numMinDY12M = 4;
$numMaxDY12M = 10;
$numRentabPatrMin = -10;
$numAtivosMin = 1;
$fatorToleranciaMin = 0.50;
$fatorToleranciaMax = 0.15;

$strNomeArquivo = 'resultados/analise_fiis_' . (1-$fatorToleranciaMin) . '-' . (1+$fatorToleranciaMax) . '_' . $numAtivosMin;

$htmlContent = file_get_contents($strUrl);
		
    $DOM = new DOMDocument();
    libxml_use_internal_errors(true);
    $DOM->loadHTML($htmlContent);
    libxml_clear_errors();
	
	$Header = $DOM->getElementsByTagName('th');
	$Detail = $DOM->getElementsByTagName('td');

    //#Get header name of the table
	foreach($Header as $NodeHeader) {
		$aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
    }
    // $aDataTableHeaderHTML[] = 'Nome';
	// print_r($aDataTableHeaderHTML); exit();

	//#Get row data/detail table without header name as key
	$i = 0;
	$j = 0;
	foreach($Detail as $sNodeDetail) {
		$aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
		$i = $i + 1;
		$j = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
    }
    array_unshift($aDataTableHeaderHTML , 'Nome');

    define("NUM_COL_ANT", 1);
    
	// print_r($aDataTableHeaderHTML); exit();
    
    if ($strTipoSaida == 'csv') {
        $exporter = new ExportDataCSV('file', $strNomeArquivo . '.csv');
    } else {
        $exporter = new ExportDataExcel('file', $strNomeArquivo . '.xls');
    }
    $exporter->initialize(); // starts streaming data to web browser
    $exporter->addRow($aDataTableHeaderHTML); // to Excel

    //#Get row data/detail table with header name as key and outer array index as row number
    $aTempData = array();
	for($i = 0; $i < count($aDataTableDetailHTML); $i++) {
        $codFII = null;
        $isToAdd = true;
        $aTempDataRow = array();
		for($j = NUM_COL_ANT; $j < count($aDataTableHeaderHTML); $j++) {
            $col = $j - NUM_COL_ANT;
            $cel = $aDataTableDetailHTML[$i][$col];
            $celFloat;
            if (startsWith($cel, "R$") || endsWith($cel, '%')) {
                $celFloat = trim(str_replace("R$", "", $cel));
                $celFloat = trim(str_replace("%", "", $celFloat));
                $celFloat = str_replace(".", "", $celFloat);
                $celFloat = str_replace(",", ".", $celFloat);
                $celFloat = floatval($celFloat);
            }
            
            $item = $aDataTableHeaderHTML[$j];
            if (endsWith($item, 'digodo fundo')) { // Código do fundo
                $codFII = $cel;
            }
			if ($item == 'DividendYield') {
				if ($celFloat < $numMinDY01M) { // Somente FIIs que pagam dividendos
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'DY (3M)A')) {
				if ($celFloat < $numMinDY03M) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'DY (6M)A')) {
				if ($celFloat < $numMinDY06M) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'DY (12M)A')) {
                if ($celFloat < $numMinDY12M) {
                    $isToAdd = false;
                }
                else if ($celFloat > $numMaxDY12M) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'DY (3M)M') || startsWith($item, 'DY (6M)M') || startsWith($item, 'DY (12M)M')) {
                if ($celFloat < $numMinDY01M) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'P/VPA')) {
                $celFloat = str_replace(",", ".", $cel);
                $celFloat = floatval($celFloat);
                if (($celFloat < (1-$fatorToleranciaMin)) || ($celFloat > (1 + $fatorToleranciaMax))) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'QuantidadeAtivos')) {
                $celFloat = floatval($cel);                
                if ($celFloat < $numAtivosMin) {
                    $isToAdd = false;
                }
            }
            else if (startsWith($item, 'Rentab. Patr.')) {
                if ($celFloat < 0) {
                    $isToAdd = false;
                }
            }
            if (!$isToAdd) {
                break;
            }
            $aTempDataRow[$item] = $cel;
        }
        if ($isToAdd & (!empty($codFII))) {
            $strUrlTemp = "https://statusinvest.com.br/fundos-imobiliarios/" . $codFII;
            $strXPathNome = "/html/body/main/header/div/div/div/h1/small";
            $strXpathPrazo = "/html/body/main/div[3]/div/div/div[2]/div/div[4]/div/div/div/strong";
            try {
                $xpw = new XPathWrapper($strUrlTemp);
                $prazo = $xpw->getXPathValueOf($strXpathPrazo);
                if (!empty($prazo) & ($prazo != 'Indeterminado')) {
                    $isToAdd = false;
                }
                if ($isToAdd) {
                    $nome = $xpw->getXPathValueOf($strXPathNome);
                    array_unshift($aTempDataRow , $nome);
                }
            } catch (Exception $e) {
            }
        }
        if ($isToAdd) {
            $exporter->addRow($aTempDataRow);
            $aTempData[$i] = $aTempDataRow;
        }
	}
	$aDataTableDetailHTML = $aTempData; unset($aTempData);
	
    // print_r($aDataTableDetailHTML);
    echo 'Relatório gerado em :' . $strNomeArquivo . ' com ' . sizeof($aDataTableDetailHTML) . ' FIIs.';
	// exit();

	function startsWith($haystack, $needle) {
		 $length = strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}
	
	function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
		return (substr($haystack, -$length) === $needle);
    }

    // to Excel ------------------------------------------------------------------
    $exporter->finalize(); // writes the footer, flushes remaining data to browser.

    exit;
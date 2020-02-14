<?php

require 'libs/php-export-data.class.php';
require 'libs/XPathWrapper.php';
require 'perfisConfigAcoes.php';

/* Load the HTML */
$strUrl = "http://www.fundamentus.com.br/resultado.php";

/**
 * Legenda:
 * 
 * Nome: Nome da empresa
 * Setor, Subsetor e Segmento: Detalhe da área em que atua
 * Papel: Código do papel na B3
 * Cotação: Valor unitário da ação
 * P/L: preço da ação dividido pelo lucro por ação
 * P/VP: preço da ação dividido pelo Valor Patrimonial por ação – informa o quanto o mercado está disposto a pagar sobre o Patrimônio Líquido da empresa
 * PSR: (Price Sales Ratio) preço da ação dividido pela Receita Líquida por ação
 * Div.Yield: (Dividend Yield) dividendo pago por ação dividido pelo preço da ação
 * P/Ativo: preço da ação dividido pelos ativos totais por ação
 * P/Cap.Giro: preço da ação dividido pelo capital de giro (ativo circulante menos passivo circulante) por ação
 * P/EBIT: preço da ação dividido pelo EBIT (lucro antes dos impostos e despesas) por ação – é uma boa aproximação do lucro operacional da empresa.
 * P/Ativ Circ.Liq: preço da ação dividido pelos Ativos Circulantes Líquidos por ação – o Ativo Circulante Líquido é obtido subtraindo os ativos circulantes pelas dívidas de curto e longo prazo
 * EV/EBIT: valor da firma dividido pelo EBIT
 * EV/EBITDA: valor da firma dividido pelo EBITDA
 * Mrg Ebit: Margem EBIT - EBIT dividido pela Receita Líquida – indica a porcentagem de cada R$ 1 de venda que sobrou após o pagamento dos custos dos produtos/serviços vendidos, das despesas com vendas gerais e administrativas
 * Mrg. Líq.: Margem Líquida - Lucro Líquido dividido pela Receita Líquida
 * Liq. Corr.: Ativo Circulante dividido pelo Passivo Circulante – indica a capacidade de pagamento da empresa no curto prazo
 * ROIC (Retorno sobre o Capital Investido): calcula-se dividindo o EBIT por (Ativos – Fornecedores – Caixa) – informa ao retorno que a empresa consegue sobre o capital total aplicado
 * ROE (Retorno sobre o Patrimônio Líquido): lucro líquido dividido pelo patrimônio líquido
 * Liq.2meses: Negociado nos últimos 2 meses
 * Patrim. Líq: Patrimônio Líquido
 * Dív.Brut/ Patrim.: Dív. Bruta (DB) sobre o Patrimônio Líquido
 * Cresc. Rec.5a: crescimento da Receita Líquida nos últimos 5 anos
 * Papel de Listagem: somente em caso de leitura de arquivo para análise específica
 * Situação: situação do papel caso não passe nos critérios de seleção
 * 
 * Fonte:
 * https://flaviomn.wordpress.com/2010/11/08/n7/
 * https://clubedovalor.com.br/fundamentus/
 */

$config = new PerfilConfiguracao();
$config->setDefault();
// $config->setDYMin4ComCresc();
// $config->setDYMin4Fileh();
// $config->setTodos(); // Rodar 1x na vida e outra na morte: demora muito.

// Verifica se é para listar todas, independente dos critérios. 
if ($config->bolListarTodasAcoes) {
    $config->numMinFreeFloatON = 0;
}

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
	// print_r($aDataTableHeaderHTML); exit();

	//#Get row data/detail table without header name as key
	$i = 0;
	$j = 0;
	foreach($Detail as $sNodeDetail) {
		$aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
		$i = $i + 1;
		$j = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
	}
    
    array_unshift($aDataTableHeaderHTML , 'Segmento');
    array_unshift($aDataTableHeaderHTML , 'Subsetor');
    array_unshift($aDataTableHeaderHTML , 'Setor');
    array_unshift($aDataTableHeaderHTML , 'Nome');

    define("NUM_COL_ANT", 4);

    // print_r($aDataTableHeaderHTML); exit();

    if ($config->strTipoSaida == 'csv') {
        $exporter = new ExportDataCSV('file', $config->strNomeArquivo . '.csv');
    } else {
        $exporter = new ExportDataExcel('file', $config->strNomeArquivo . '.xls');
    }

    $aDataPapelBlueChip = array();
    if (!empty($config->strConfigListBlueChips)) {
        if ($file = fopen($config->strConfigListBlueChips, "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                $strPBC = trim($line);
                $strPBC = substr($strPBC, 0, 4) . '3';
                if (strlen($strPBC) > 0) {
                    $aDataPapelBlueChip[] = $strPBC;
                }
            }
            fclose($file);
        }
        // print_r($aDataPapelBlueChip); exit();
    }
    $aDataPapelSmallCaps = array();
    if (!empty($config->strConfigListSmallCaps)) {
        if ($file = fopen($config->strConfigListSmallCaps, "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                $strPSC = trim($line);
                $strPSC = substr($strPSC, 0, 4) . '3';
                if (strlen($strPSC) > 0) {
                    $aDataPapelSmallCaps[] = $strPSC;
                }
            }
            fclose($file);
        }
        // print_r($aDataPapelSmallCaps); exit();
    }

    $numColsPos = 0;

    {
        array_push($aDataTableHeaderHTML, 'Free Float (ON)');
        array_push($aDataTableHeaderHTML, 'Free Float (PN)');
        $numColsPos = $numColsPos + 2;
    }

    $isToAnalisarTipo = false;
    if (count($aDataPapelBlueChip) > 0 || count($aDataPapelSmallCaps) > 0) {
        $isToAnalisarTipo = true;
        array_push($aDataTableHeaderHTML, 'Tipo');
        $numColsPos = $numColsPos + 1;
    }
    
    $aDataPapelAnalise = array();
    if (!empty($config->strAnaliseEspecifica)) {
        if ($file = fopen($config->strAnaliseEspecifica, "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                $strPPA = trim($line);
                if (strlen($strPPA) > 0) {
                    $aDataPapelAnalise[] = $strPPA;
                }
            }
            fclose($file);
        }
        if (count($aDataPapelAnalise) > 0) {
            array_push($aDataTableHeaderHTML, 'Papel de Listagem');
            array_push($aDataTableHeaderHTML, 'Situação');
            array_push($aDataTableHeaderHTML, 'Índice');
            $numColsPos = $numColsPos + 3;
        }
        // print_r($aDataTableHeaderHTML); exit();
        // print_r($aDataPapelAnalise); exit();
    }

    $aDataPapelForaAnalise = array();
    if (!empty($config->strPapeisForaDeAnalise)) {
        if ($file = fopen($config->strPapeisForaDeAnalise, "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                $strPFA = trim($line);
                if (strlen($strPFA) > 0) {
                    $strPFA = substr($strPFA, 0, 5);
                    $aDataPapelForaAnalise[] = $strPFA;
                }
            }
            fclose($file);
        }
        // print_r($aDataPapelForaAnalise); exit();
    }

    $exporter->initialize(); // starts streaming data to web browser
    $exporter->addRow($aDataTableHeaderHTML); // to Excel

    $acoesAnalisadas = array();
    //#Get row data/detail table with header name as key and outer array index as row number
	for($i = 0; $i < count($aDataTableDetailHTML); $i++) {
        $isToAdd = true;
        $isToAddForcado = false;
        $strPapel = null;
        $strMessage = '';
        $aTempDataRow = array();
        $aLimite = count($aDataTableHeaderHTML) - $numColsPos;
		for($j = NUM_COL_ANT; $j < $aLimite; $j++) {
            $col = $j - NUM_COL_ANT;
            $cel = $aDataTableDetailHTML[$i][$col];
            $celFloat;
            // echo $cel; echo $aDataTableHeaderHTML[$j]; exit();
            $item = $aDataTableHeaderHTML[$j];
			if ($item == 'Papel') {
                if (count($aDataPapelForaAnalise) > 0) {
                    if (in_array($cel, $aDataPapelForaAnalise)) {
                        // Se está na listagem de papeis fora do radar, não é analisado.
                        $isToAdd = false;
                    }
                }
                if (count($aDataPapelAnalise) > 0) {
                    if (!in_array($cel, $aDataPapelAnalise) && $config->bolSomentePapeisEspecificos) {
                        // Se não está na listagem, e se for apenas papeis específicos, não é analisado.
                        $isToAdd = false;
                    }
                }
                if ($isToAdd) {
			    	if (!endsWith($cel, '3')) { // Somente ações ON
                        $isToAdd = false;
                    } else {
                        if (endsWith($cel, '33')) {
                            $isToAdd = false;
                        } else {
                            $strPapel = $cel;
                        }
                    }
                }
                if ($isToAdd && $config->bolApenasPapeisON) {
                    // verifica se a empresa possúi papeis preferenciais:
                    $tiposPN = ["4", "5", "6"];
                    for ($p = 0; $p < count($tiposPN); $p++) {
                        $celPN = trim(str_replace("3", $tiposPN[$p], $cel));
                        for($k = 0; $k < count($aDataTableDetailHTML); $k++) {
                            $celPNK = $aDataTableDetailHTML[$k][$col];
                            if ($celPN == $celPNK) {
                                $isToAdd = false;
                                break;
                            }
                        }
                        if (!$isToAdd) {
                           break;
                        }
                    }
                }
            } else {
                $celFloat = getFloatOf($cel);
            }

            // Dividend Yield: dividendo pago por ação dividido pelo preço da ação
            if ($item == 'Div.Yield') {
                // Se > 0 = somente empresas que pagam dividendos
				if ($celFloat < $config->numDYMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, 'DY (' . $celFloat . ') < ' . $config->numDYMin);
                }
                else if ($celFloat > $config->numDYMax) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, 'DY (' . $celFloat . ') > ' . $config->numDYMax);
                }
            }
            // Preço da ação dividido pelo lucro por ação
            else if ($item == 'P/L') {
                // Descarte de empresas extremamente baratas (alto risco) ou muito caras.
                // Default: 3 < P/L < 20
				if ($celFloat < $config->numPLMin) { 
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numPLMin);
                }
                else if ($celFloat > $config->numPLMax) { 
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') > ' . $config->numPLMax);
                }
            }
            // Preço da ação dividido pelo Valor Patrimonial por ação.
            // Informa o quanto o mercado está disposto a pagar sobre o Patrimônio Líquido da empresa (N vezes o seu VP).
            else if ($item == 'P/VP') {
                // Somente empresas não muito valorizadas:
				if ($celFloat > $config->numPVPMax) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') > ' . $config->numPVPMax);
                }
            }
            // P/EBIT: preço da ação dividido pelo EBIT (lucro antes dos impostos e despesas) por ação
            // EBIT (Earning Before Interest and Taxes): lucro antes dos impostos e juros, uma aproximação do lucro operacional da empresa
            // É uma boa aproximação do lucro operacional da empresa.
            else if ($item == 'P/EBIT') {
                // P/EBIT acima do valor mínimo
				if ($celFloat < $config->numPEBITMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numPEBITMin);
                }
            }
            // P/EBITDA: preço da ação dividido pelo EBITDA por ação. 
            // O EBITDA é o lucro antes das despesas financeiras, impostos, depreciação e amortização. 
            // É uma boa aproximação do lucro operacional da empresa.
            else if ($item == 'P/EBITDA') {
                // P/EBITDA / P/EBIT acima do valor mínimo
				if ($celFloat < $config->numPEBITMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numPEBITMin);
                }
            }
            // ROE (Retorno sobre o Patrimônio Líquido): lucro líquido dividido pelo patrimônio líquido.
            else if ($item == 'ROE') {
                // Somente empresas que tiveram crescimento:
				if ($celFloat < $config->numROEMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numROEMin);
                }
            }
            // ROIC (Retorno sobre o Capital Investido): calcula-se dividindo o EBIT por (Ativos – Fornecedores – Caixa)
            // informa ao retorno que a empresa consegue sobre o capital total aplicado
            else if ($item == 'ROIC') {
				if ($celFloat < $config->numROICMin) { // Somente empresas que tiveram crescimento
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numROICMin);
                }
            }
            // Dívida bruta total (dívida +debêntures) dividido pelo Patrimônio Líquido
            else if ($item == 'Patrim. Líq') {
                if ($celFloat < $config->numPatrimLiqMin) { // Somente empresas com patrimônio
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numPatrimLiqMin);
                }
            }
            // Negociado nos últimos 2 meses
            else if ($item == 'Liq.2meses') {
                if ($celFloat < $config->numLiq2mesesMin) { // Somente ação com liquidez
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item . ' (' . $celFloat . ') < ' . $config->numLiq2mesesMin);
                }
            }
            // Dívida bruta total (dívida + debêntures) dividido pelo Patrimônio Líquido
            else if ($item == 'Dív.Brut/ Patrim.') {
                // Default: DB/PL entre 0 e 0,5 (ou 50%)
                if ($celFloat < $config->numDBPatrimMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, 'DB/Patr.Líq. (' . $celFloat . ') < ' . $config->numDBPatrimMin);
                }
                else if ($celFloat > $config->numDBPatrimMax) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, 'DB/Patr.Líq. (' . $celFloat . ') > ' . $config->numDBPatrimMax);
                }

            }
            // Crescimento da Receita Líquida nos últimos 5 anos
            else if ($item == 'Cresc. Rec.5a') {
                if ($config->bolPermiteCrescNeg) {
                    // Empresas em vias de recuperação ou com desempenho ruim até certo valor %:
                    if ($celFloat > $config->numCrescRec5aMax) {
                        $isToAdd = false;
                        $strMessage = getMessage($strMessage, $item  . ' (' . $celFloat . ') > ' . $config->numCrescRec5aMax);
                    }
                }
                // Ou somente empresas que não tiveram prejuizo:
                else if ($celFloat < $config->numCrescRec5aMin) {
                    $isToAdd = false;
                    $strMessage = getMessage($strMessage, $item  . ' (' . $celFloat . ') < ' . $config->numCrescRec5aMin);
                }
            }
            // Verifica se é para listar todas, independente dos critérios:
            if ($config->bolListarTodasAcoes) {
                $isToAdd = true;
            }
            // Verifica se é para adicionar ou não o papel na listagem:
            if (!$isToAdd) {
                if (count($aDataPapelAnalise) == 0) {
                    // Se não tem lista de análise específica, fim de papo: não é listado.
                    break;
                } else {
                    if (!in_array($strPapel, $aDataPapelAnalise)) {
                        // Se tem lista de análise específica mas o papel não está nesta lista e não passa nos critérios, não é listado.
                        break;
                    } else {
                        // Tem lista de análise específica mas o papel não passa nos critérios, mesmo assim é listado, mas com observações.
                        $isToAdd = true;
                        $isToAddForcado = true;
                    }
                }
            }
            $aTempDataRow[$item] = $cel;
        }

        if ($isToAdd && !empty($strPapel)) {
            $strUrlTemp       = "https://statusinvest.com.br/acoes/" . $strPapel;
            $strXpathNome     = "/html/body/main/header/div/div/div/h1/small";
            $strXpathSetor    = "/html/body/main/div[3]/div/div[3]/div/div[1]/div/div/div/a/strong";
            $strXpathSubsetor = "/html/body/main/div[3]/div/div[3]/div/div[2]/div/div/div/a/strong";
            $strXpathSegmento = "/html/body/main/div[3]/div/div[3]/div/div[3]/div/div/div/a/strong";
            try {
                $xpw = new XPathWrapper($strUrlTemp);
                $nome = $xpw->getXPathValueOf($strXpathNome);
                $setor = $xpw->getXPathValueOf($strXpathSetor);
                $subsetor = $xpw->getXPathValueOf($strXpathSubsetor);
                $segmento = $xpw->getXPathValueOf($strXpathSegmento);
                array_unshift($aTempDataRow , $segmento);
                array_unshift($aTempDataRow , $subsetor);
                array_unshift($aTempDataRow , $setor);
                array_unshift($aTempDataRow , $nome);

                $aDataFreeFloat = getFreeFloatOnPnOf($strPapel);
                $valFreeFloatON = getFloatOf($aDataFreeFloat[0]);
                if ($valFreeFloatON < $config->numMinFreeFloatON) {
                    if (!in_array($strPapel, $aDataPapelAnalise)) {
                        // Se o papel não está na lista específica e não atende o critério, então não é listado.
                        $isToAdd = false;
                    }
                }
                array_push($aTempDataRow, $aDataFreeFloat[0]); // Free Float ON
                array_push($aTempDataRow, $aDataFreeFloat[1]); // Free Float PN
                if ($isToAnalisarTipo) {
                    if (in_array($strPapel, $aDataPapelBlueChip)) {
                        array_push($aTempDataRow, 'BLUE CHIP');
                    }
                    else if (in_array($strPapel, $aDataPapelSmallCaps)) {
                        array_push($aTempDataRow, 'SMALL CAP');
                    }
                    else {
                        array_push($aTempDataRow, '');
                    }
                }

                if (count($aDataPapelAnalise) > 0) {
                    if (in_array($strPapel, $aDataPapelAnalise)) {
                        if ($isToAddForcado) {
                            array_push($aTempDataRow, '3: SIM com Obs.');
                        } else {
                            array_push($aTempDataRow, '1: SIM e OK');
                        }
                        array_push($aTempDataRow, $strMessage);
                        array_push($aTempDataRow, intval(array_search($strPapel, $aDataPapelAnalise)) +1 );
                    } else {
                        array_push($aTempDataRow, '2: NÃO mas OK');
                        array_push($aTempDataRow, '');
                        array_push($aTempDataRow, count($aDataPapelAnalise) + 1);
                    }
                }
            } catch (Exception $e) {
                array_unshift($aTempDataRow , "");
            } finally {
                if (empty($nome)) {
                    $isToAdd = false;
                }
            }
            // exit();
        }
        // Conferencia para evitar repetição de papel na listagem:
        if (!in_array($strPapel, $acoesAnalisadas)) {
            array_push($acoesAnalisadas, $strPapel);
        } else {
            $isToAdd = false;   
        }
        if ($isToAdd) {
            $exporter->addRow($aTempDataRow); // to Excel
            $aTempData[$i] = $aTempDataRow;
        }
	}
	$aDataTableDetailHTML = $aTempData; unset($aTempData);
	
    // print_r($aDataTableDetailHTML);
    echo 'Relatório gerado em ' . $config->strNomeArquivo . ' com ' . sizeof($aDataTableDetailHTML) . ' papéis listados.';
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

    function containsWord($str, $word) {
        return !!preg_match('#\\b' . preg_quote($word, '#') . '\\b#i', $str);
    }

    function getMessage($message, $word) {
        $length = strlen($message);
		if ($length > 0) {
			$message = $message . '; ' . $word;
		} else {
            $message = $word;
        }
		return $message;
    }

    function getFloatOf($cel) {
        if (empty($cel)) return 0;
        $celFloat = trim(str_replace("%", "", $cel));
        $celFloat = str_replace(".", "", $celFloat);
        $celFloat = str_replace(",", ".", $celFloat);
        $celFloat = floatval($celFloat);
        return $celFloat;
    }

    function getFreeFloatOnPnOf($codPapel) {
        $strUrl2X = "https://www.investsite.com.br/controle_acionario.php?cod_negociacao=" . $codPapel;

        $htmlContent2X = file_get_contents($strUrl2X);
		
        $DOM2X = new DOMDocument();
        libxml_use_internal_errors(true);
        $DOM2X->loadHTML($htmlContent2X);
        libxml_clear_errors();

        $Header = $DOM2X->getElementsByTagName('th');
        $Detail = $DOM2X->getElementsByTagName('td');

        //#Get header name of the table
	    foreach($Header as $NodeHeader) {
	    	$aDataTableHeaderHTML[] = trim($NodeHeader->textContent);
        }

	        //#Get row data/detail table without header name as key
	    $i = 0;
	    $j = 0;
	    foreach($Detail as $sNodeDetail) {
	    	$aDataTableDetailHTML[$j][] = trim($sNodeDetail->textContent);
		    $i = $i + 1;
		    $j = $i % count($aDataTableHeaderHTML) == 0 ? $j + 1 : $j;
        }
        
        //#Get row data/detail table with header name as key and outer array index as row number
        $aDataFreeFloat = array();
	    for($i = 0; $i < count($aDataTableDetailHTML); $i++) {
            $emOutros = false;
		    for($j = 0; $j < count($aDataTableHeaderHTML); $j++) {
                $cel = trim($aDataTableDetailHTML[$i][$j]);
                if ($emOutros) {
                    if (endsWith($cel, '%')) {
                        $aDataFreeFloat[] = $cel;
                    }
                }
                else if (trim($cel) == 'Outros') {
                    $emOutros = true;
                }
            }
            if ($emOutros) {
                // Retorna o Free Float ON e PN
                return $aDataFreeFloat;
            }
        }
        // Se não achou, retorna vazio:
        $aDataFreeFloat = ['','',''];
        return $aDataFreeFloat;
	}

    // to Excel ------------------------------------------------------------------
    $exporter->finalize(); // writes the footer, flushes remaining data to browser.

    exit;

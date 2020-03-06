<?php

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

class PerfilConfiguracao {

    // Configuração de Indicadores:
    public $strTipoSaida = 'csv';
    public $numDYMin = 0;
    public $numDYMax = 12;
    public $numPLMin = 3; // Ideal: 3 <= PL <= 20
    public $numPLMax = 20; // Ideal: 3 <= PL <= 20
    public $numPVPMax = 4;
    public $numPEBITMin = 0; // Ideal P/EBIT >= 0
    public $numROEMin = 10; // Ideal > 20%
    public $numROICMin = 0;
    public $numPatrimLiqMin = 0;
    public $numDBPatrimMin = 0;
    public $numDBPatrimMax = 0.5; // Ideal: 0 <= DBPatrimMax <= 0.5;
    public $bolPermiteCrescNeg = true; // Default = false
    public $numCrescRec5aMin = 95;
    public $numCrescRec5aMax = 100;
    public $bolApenasPapeisON = false; // Novo mercardo = true;
    public $numLiq2mesesMin = 5000;
    public $numMinFreeFloatON = 20; // Valor mínimo ideal: 25
    
    // Configuração de Saída:
    public $bolSomentePapeisEspecificos = false; // Default: false
    public $strAnaliseEspecifica = 'config/listaDeAcoes.txt'; // 'listaDeAcoes.txt';
    public $strPapeisForaDeAnalise = 'config/listaDeAcoesForaAnalise.txt'; // 'listaDeAcoesForaAnalise.txt';
    public $bolListarTodasAcoes = false; // Default: FALSE
    public $strNomeArquivo = 'resultados/analise_acoes_onip';
    public $strConfigListBlueChips = 'config/listaBlueChips.txt';
    public $strConfigListSmallCaps = 'config/listaSmallCaps.txt';

    public function setDefault() {
        // Configuração de Indicadores:
        $this->strTipoSaida = 'csv';
        $this->numDYMin = 0;
        $this->numDYMax = 12;
        $this->numPLMin = 3;
        $this->numPLMax = 20;
        $this->numPVPMax = 1000; // Praticamente anula esse parâmetro
        $this->numPEBITMin = 0; // Ideal P/EBIT >= 0
        $this->numROEMin = 10; // Ideal > 20%
        $this->numROICMin = 0;
        $this->numPatrimLiqMin = 0;
        $this->numDBPatrimMin = 0;
        $this->numDBPatrimMax = 0.5; // Ideal 0.5
        $this->bolPermiteCrescNeg = true; // Default = false
        $this->numCrescRec5aMin = 95;
        $this->numCrescRec5aMax = 100;
        $this->bolApenasPapeisON = false; // Novo mercardo = true;
        $this->numLiq2mesesMin = 5000;
        $this->numMinFreeFloatON = 20; // Valor mínimo ideal: 25
    
        // Configuração de Saída:
        $this->bolSomentePapeisEspecificos = false; // Default: false
        $this->strAnaliseEspecifica = 'config/listaDeAcoes.txt'; // 'listaDeAcoes.txt';
        $this->strPapeisForaDeAnalise = 'config/listaDeAcoesForaAnalise.txt'; // 'listaDeAcoesForaAnalise.txt';
        $this->bolListarTodasAcoes = false; // Default: FALSE
        $this->strNomeArquivo = 'resultados/analise_acoes_onip';
    }

    public function setNovoDefault() {
        $this->setDefault();
        $this->strTipoSaida = 'csv';
        $this->strNomeArquivo = 'resultados/analise_acoes_onip';
    }

    public function setPerfil2() {
        $this->setDefault();        
        $this->numPLMax = 25; // Ideal: 20
        $this->numDBPatrimMax = 1.5; // Ideal 0.5
        $this->numPVPMax = 1000; // Praticamente anula erra indicador
        $this->strNomeArquivo = 'resultados/analise_acoes_on_p2';
    }

    public function setTodos() {
        $this->setDefault();
        // Configuração de Indicadores:
        $this->numMinFreeFloatON = 0; // Valor mínimo ideal: 25
    
        // Configuração de Saída:
        $this->bolSomentePapeisEspecificos = false; // Default: false
        $this->strAnaliseEspecifica = ''; // 'listaDeAcoes.txt';
        $this->bolListarTodasAcoes = true; // Default: FALSE
        $this->strNomeArquivo = 'resultados/analise_acoes_completo';
    }

    public function setDYMin4ComCresc() {
        $this->setDefault();
        // Configuração de Indicadores:
        $this->numDYMin = 4;
        $this->numDYMax = 12;
        $this->numPLMin = -100;
        $this->numPLMax = 100;
        $this->numPVPMax = 100;
        $this->numPEBITMin = 0; // Ideal P/EBIT >= 0
        $this->numROEMin = 0; // Ideal > 20%
        $this->numROICMin = 0;
        $this->numPatrimLiqMin = 0;
        $this->numDBPatrimMin = 0;
        $this->numDBPatrimMax = 100; // Ideal 0.5
        $this->bolPermiteCrescNeg = true; // Default = false
        $this->numCrescRec5aMin = 95;
        $this->numCrescRec5aMax = 1000;
        $this->bolApenasPapeisON = false; // Novo mercardo = true;
        $this->numLiq2mesesMin = 5000;
        $this->numMinFreeFloatON = 0; // Valor mínimo ideal: 25
    
        // Configuração de Saída:
        $this->strNomeArquivo = 'resultados/analise_acoes_dyMin4';
    }

    public function setDYMin4Fileh() {
        $this->setDefault();
        // Configuração de Indicadores:
        $this->numDYMin = 4;
        $this->numDBPatrimMax = 1.5; // Ideal 0.5
        $this->numMinFreeFloatON = 25; // Valor mínimo ideal: 25
    
        // Configuração de Saída:
        $this->strAnaliseEspecifica = 'config/listaDeAcoes.txt'; // 'listaDeAcoes.txt';
        $this->bolListarTodasAcoes = false; // Default: FALSE
        $this->strNomeArquivo = 'resultados/analise_acoes_dyMin4Fileh';
    }

}

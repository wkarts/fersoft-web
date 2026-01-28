<table>
    <thead>
        <tr>
            <th>NOME</th>
            <th>COR</th>
            <th>CATEGORIA</th>
            <th>VALOR DE VENDA</th>
            <th>VALOR DE COMPRA</th>

            <th>NCM</th>
            <th>CÓDIGO DE BARRAS</th>
            <th>CEST</th>
            <th>CST/CSOSN</th>
            <th>CST PIS</th>
            <th>CST COFINS</th>
            <th>CST IPI</th>

            <th>UN. COMPRA</th>
            <th>UN. VENDA</th>
            <th>CONVERSÃO UNITÁRIA</th>
            <th>COMPOSTO</th>
            <th>VALOR LIVRE</th>

            <th>PERCENTUAL ICMS</th>
            <th>PERCENTUAL PIS</th>
            <th>PERCENTUAL COFINS</th>
            <th>PERCENTUAL IPI</th>
            <th>PERCENTUAL ISS</th>

            <th>CFOP SAIDA ESTADUAL</th>
            <th>CFOP SAIDA OUTRO ESTADO</th>
            <th>CÓDIGO ANP</th>
            <th>DESCRIÇÃO ANP</th>
            <th>ALERTA VENCIMENTO</th>
            <th>GERENCIA ESTOQUE</th>
            <th>ESTOQUE MINÍMO</th>

            <th>REFERÊNCIA</th>
            <th>LARGURA</th>
            <th>COMPRIMENTO</th>
            <th>ALTURA</th>
            <th>PESO LIQUIDO</th>
            <th>PESO BRUTO</th>

            <th>LIMITE MAXIMO DESCONTO</th>
            <th>REDUÇÃO BC</th>
            <th>CÓDIGO BENEFICIARIO</th>

            <th>ESTOQUE</th>
            <th>CFOP ENTRADA ESTADUAL</th>
            <th>CFOP ENTRADA OUTRO ESTADO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $p)
        <tr>
            <td>{{$p->nome}} {{$p->str_grade}}</td>
            <td>{{$p->cor}}</td>
            <td>{{$p->categoria->nome}}</td>
            <td>{{number_format($p->valor_venda, 2, ',', '.')}}</td>
            <td>{{number_format($p->valor_compra, 2, ',', '.')}}</td>

            <td>{{$p->NCM}}</td>
            <td>{{$p->codBarras}}</td>
            <td>{{$p->CEST}}</td>

            <td>{{$p->CST_CSOSN}}</td>
            <td>{{$p->CST_PIS}}</td>
            <td>{{$p->CST_COFINS}}</td>
            <td>{{$p->CST_IPI}}</td>

            <td>{{$p->unidade_compra}}</td>
            <td>{{$p->unidade_venda}}</td>
            <td>{{$p->conversao_unitaria}}</td>
            <td>{{$p->composto}}</td>
            <td>{{$p->valor_livre}}</td>

            <td>{{$p->perc_icms}}</td>
            <td>{{$p->perc_pis}}</td>
            <td>{{$p->perc_cofins}}</td>
            <td>{{$p->perc_ipi}}</td>
            <td>{{$p->perc_iss}}</td>

            <td>{{$p->CFOP_saida_estadual}}</td>
            <td>{{$p->CFOP_saida_inter_estadual}}</td>
            <td>{{$p->codigo_anp}}</td>
            <td>{{$p->descricao_anp}}</td>

            <td>{{$p->alerta_vencimento}}</td>
            <td>{{$p->gerenciar_estoque}}</td>
            <td>{{$p->estoque_minimo}}</td>


            <td>{{$p->referencia}}</td>
            <td>{{$p->largura}}</td>
            <td>{{$p->comprimento}}</td>
            <td>{{$p->altura}}</td>
            <td>{{$p->peso_liquido}}</td>
            <td>{{$p->peso_bruto}}</td>

            <td>{{$p->limite_maximo_desconto}}</td>
            <td>{{$p->pRedBC}}</td>
            <td>{{$p->cBenef}}</td>

            <td>{{$p->estoqueAtual()}}</td>
            
            <td>{{$p->CFOP_entrada_estadual}}</td>
            <td>{{$p->CFOP_entrada_inter_estadual}}</td>
            
        </tr>
        @endforeach
    </tbody>
</table>

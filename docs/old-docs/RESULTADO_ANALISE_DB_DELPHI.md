Vamos analisar os bancos de DP e SIAP, ambos contém cadastro de pessoafisica e juridica, mas em formatos diferentes, e temos que migrar para um banco única de pessoa_fisica e pessoa_juridica

--Query

SELECT 'PESSOA' AS TABELA, COUNT(*) AS TOTAL FROM PESSOA;

--Resultado BANCO DP
TABELA        TOTAL 
====== ============ 
PESSOA          513 

--Resultado BANCO SIAP
TABELA        TOTAL 
====== ============ 
PESSOA         2595 


--Query
SELECT COUNT(*) AS TOTAL_PF FROM PESSOA_FISICA;

--Resultado BANCO DP
             TOTAL_PF 
===================== 
                   51 
SELECT COUNT(*) AS TOTAL_PESSOA FROM CREDOR;

--Resultado BANCO SIAP
       TOTAL_PESSOA
============ 
        7466 

--Query SIAP
SELECT
    CASE 
        WHEN TP_CDR = 'PF' THEN 'PESSOA FÍSICA'
        WHEN TP_CDR = 'PJ' THEN 'PESSOA JURÍDICA'
        ELSE 'OUTROS'
    END AS TP_CDR,
    COUNT(*) AS TOTAL
FROM CREDOR
GROUP BY TP_CDR;

--Resultado SIAP
TP_CDR                TOTAL 
============== ============ 
PESSOA FSICA           4405 
PESSOA JURDICA         3061 


--Query
SELECT
    'Em PESSOA mas não em PESSOAS' AS STATUS,
    COUNT(*) AS TOTAL
FROM PESSOA p
WHERE NOT EXISTS (SELECT 1 FROM PESSOA_FISICA ps WHERE ps.INT_PSSOA = p.PESSOA_ID);

--Resultado BANCO DP
STATUS                                            TOTAL 
================================= ===================== 
Em PESSOA mas não em PESSOA_FISICA                  507 

--Resultado BANCO SIAP

SELECT
    CASE 
        WHEN c.CNPJ_CDR IS NOT NULL THEN 'PESSOA EXISTE NO CREDOR'
        ELSE 'PESSOA NÃO EXISTE NO CREDOR'
    END AS STATUS,
    COUNT(*) AS TOTAL
FROM PESSOA p
LEFT JOIN CREDOR c
    ON p.STR_NUM_DOC_PSOA = c.CNPJ_CDR
GROUP BY 
    CASE 
        WHEN c.CNPJ_CDR IS NOT NULL THEN 'PESSOA EXISTE NO CREDOR'
        ELSE 'PESSOA NÃO EXISTE NO CREDOR'
    END;
--Resultado 
STATUS                     TOTAL 
=================== ============ 
PESSOA EXISTE NO CREDOR             4159 
PESSOA NÃO EXISTE NO CREDOR            4 


--Query
SELECT
    'Em PESSOA_FISICA mas não em PESSOA' AS STATUS,
    COUNT(*) AS TOTAL
FROM PESSOA_FISICA ps
WHERE NOT EXISTS (SELECT 1 FROM PESSOA p WHERE p.PESSOA_ID = ps.INT_PSSOA);

--Resultado BANCO DP
 
STATUS                                            TOTAL 
================================= ===================== 
Em PESSOA_FISICA mas não em PESSOA                     45 

--Resultado BANCO SIAP


--Query
SELECT STR_T_CAD, COUNT(*) AS TOTAL
FROM PESSOA
GROUP BY STR_T_CAD
ORDER BY TOTAL DESC;

--Resultado BANCO DP

STR_T_CAD                 TOTAL 
========= ===================== 
C	                   497
A	                    9
D	                    7

--Resultado BANCO SIAP
STR_T_CAD        TOTAL 
========= ============ 
C                 2549 
D                   43 
A                    3 



--Query
SELECT
    STR_NUM_DOC_PSOA,
    COUNT(*) AS QUANTIDADE
FROM PESSOA
WHERE STR_NUM_DOC_PSOA IS NOT NULL AND TRIM(STR_NUM_DOC_PSOA) != ''
GROUP BY STR_NUM_DOC_PSOA
HAVING COUNT(*) > 1
ORDER BY QUANTIDADE DESC;

--Resultado BANCO DP
STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
01615371000140              5 
00904584607                 3 
54626439691                 3 
04499309638                 3 
03804797610                 2 
09803229605                 2 
10218141645                 2 
04314348674                 2 
01517930693                 2 
07511251641                 2 
92719724653                 2 
04327871605                 2 
50423835653                 2 
10625948602                 2 
06339841678                 2 
06536831624                 2 
73149888620                 2 
03644266670                 2 
04051060683                 2 
28151283653                 2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
00456692606                 2 
05867903656                 2 
18084869604                 2 
04284527606                 2 
99488655615                 2 
59889322749                 2 
04691127607                 2 

--Resultado BANCO SIAP
STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
01615371000140             10 
07367867679                 5 
42326028600                 4 
06525544661                 4 
88558770620                 4 
04314348674                 3 
75126737600                 3 
02545383635                 3 
04259752669                 3 
00463777603                 3 
18977683890                 3 
01297165616                 3 
00840544000161              3 
21154554000113              3 
29979036000140              3 
04501690658                 2 
00904584607                 2 
00904146677                 2 
79906982691                 2 
13019153603                 2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
52113795604                 2 
10218141645                 2 
92719724653                 2 
86222848672                 2 
42322464600                 2 
04327871605                 2 
96325070615                 2 
04625187605                 2 
54626439691                 2 
06629104609                 2 
00727054694                 2 
07130506679                 2 
42331323615                 2 
73149888620                 2 
80241743753                 2 
01249050642                 2 
38342316653                 2 
05946591673                 2 
08151699655                 2 
01357394624                 2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
83457534691                 2 
28452100604                 2 
80559948620                 2 
07858839675                 2 
32952597634                 2 
03262326640                 2 
10667235671                 2 
62662996668                 2 
52866777620                 2 
03864520673                 2 
14864539880                 2 
00179347616                 2 
05175266624                 2 
72275510672                 2 
72274905649                 2 
10378141694                 2 
08081851607                 2 
08089163602                 2 
00180380699                 2 
42388872634                 2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
04383243665                 2 
81985681668                 2 
34095933810                 2 
04499309638                 2 
96496541604                 2 
06995893638                 2 
00000000016276              2 
33000118000330              2 
11101969000175              2 
19104451000105              2 
02303541000114              2 
07304241000173              2 
17404302000128              2 
00503272000104              2 
09608765000183              2 
24012452000402              2 
24018656000108              2 
18118596000194              2 
01317127000100              2 
20513859000101              2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
21513577000177              2 
21513577000339              2 
02514782000102              2 
05518417000164              2 
11818668000167              2 
05812376000114              2 
00914711000171              2 
01916872000167              2 
09028602000121              2 
09025465000171              2 
07128081000159              2 
07524381000157              2 
07528036000191              2 
19722112000184              2 
42923573000560              2 
05926081000179              2 
11038785000108              2 
13237931000150              2 
13237191000151              2 
22337375000184              2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
03535664000143              2 
33530486013883              2 
01632455000192              2 
01632458000126              2 
20049620000122              2 
26147686000131              2 
23345029000100              2 
16443048000104              2 
21545371001524              2 
17542056000170              2 
10740865000148              2 
65252116000163              2 
17254509000163              2 
22352961000106              2 
02558157000910              2 
18556357000116              2 
17653908000105              2 
18756382000143              2 
16850359000198              2 
14951451000119              2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
09065881000101              2 
01264915000177              2 
08361599000109              2 
13566689000168              2 
47960950022010              2 
19968883000156              2 
14072798000353              2 
07079763000119              2 
07277331000112              2 
00378257000181              2 
13775784000171              2 
05875647000180              2 
23184914000155              2 
65283269000259              2 
11387190000168              2 
07487504000127              2 
00684182000167              2 
03686856000150              2 
00988887000178              2 
42982868000184              2 

STR_NUM_DOC_PSOA   QUANTIDADE 
================ ============ 
05980740000155              2 
06981180000116              2 
03198737000159              2 
06293123000144              2 
00394460009602              2 
21699889000117              2 
02791527000107              2 
03893311000115              2 
25994179000170              2 
18993824000175              2 



--Query
SELECT
    SX_PSSOA,
    COUNT(*) AS TOTAL
FROM PESSOA_FISICA
GROUP BY SX_PSSOA;

--Resultado BANCO DP
SX_PSSOA        TOTAL 
======== ============ 
F                  13 
M                  38 

--Resultado BANCO SIAP

--Query
SELECT FIRST 10
    INT_PSSOA,
    NM_PSSOA,
    CPF_PSSOA,
    D_NSCMNTO_PSSOA
FROM PESSOA_FISICA
ORDER BY INT_PSSOA DESC;

--Resultado BANCO DP

 INT_PSSOA NM_PSSOA                                                               CPF_PSSOA   D_NSCMNTO_PSSOA 
============ ====================================================================== =========== =============== 
        1514 BRUNO EDUARDO BEDESCHI COSTA                                           04721864698 1982-07-27      
        1513 PABLO HENRIQUE DE SOUZA                                                10318861682 1989-03-03      
        1512 RAFAELA APARECIDA DOS SANTOS                                           08109182690 1988-09-12      
        1511 ALEXSANDRO LUCIO RODRIGUES                                             06804686683 1975-12-24      
        1510 POLIANA LETICIA MUFFATO DE RESENDE                                     10427215641 1992-03-16      
        1509 DOUGLAS ANDRETO SILVA CRUZ                                             10412753669 1994-01-17      
        1508 PAULO CESAR DETONI                                                     77296532653 1970-11-11      
        1507 ELISA REIS MACIEL                                                      10378141694 1989-11-14      
        1506 MARTA ARLINDA VILELA DE CAMPOS                                         81909381691 1970-09-16      
        1505 MATHEUS HENRIQUE DE PAIVA OLIVEIRA                                     01984492675 1997-12-27      

--Resultado BANCO DP


PESSOA_JURIDICA

--Query
SELECT COUNT(*) AS TOTAL_CNPJ FROM CNPJ;

--Resultado BANCO DP
TOTAL_CNPJ                                             
=============================== 
5
--Resultado BANCO SIAP (CREDOR)
TOTAL PJ                                            
=============================== 
3061

--Query
SELECT FIRST 3
    ID3_EMP,
    NM_EMP,
    CNPJ_EMP,
    LG_EMP_ATV
FROM EMPRESA
ORDER BY ID3_EMP DESC;

--Resultado BANCO DP
ID3_EMP NM_EMP                                        CNPJ_EMP       LG_EMP_ATV 
======= ============================================= ============== ========== 
005     CAM MUN SANTA CRUZ DE MINAS AUTONOMOS         29871863000116 S          
004     CAMARA MUNICIPAL SANTA CRUZ DE MINAS          29871863000116 S   

--Resultado BANCO SIAP


-- Verificar se INT_PSSOA de PESSOA_FISICA referencia PESSOA_ID
--Query
SELECT
    'PF com referência em PESSOA' AS STATUS,
    COUNT(*) AS TOTAL
FROM PESSOA_FISICA pf
INNER JOIN PESSOA p ON pf.INT_PSSOA = p.PESSOA_ID;

--Resultado BANCO DP
STATUS                    TOTAL 
=======================   ======= 
PF com referência em PESSOA	6

--Resultado BANCO SIAP

Onde o PESSOA_ID é usado
--Query
SELECT
     rf.RDB$RELATION_NAME AS TABELA,
     rf.RDB$FIELD_NAME AS COLUNA
FROM RDB$RELATION_FIELDS rf
WHERE UPPER(rf.RDB$FIELD_NAME) LIKE '%PESSOA_ID%'
ORDER BY rf.RDB$RELATION_NAME;
--Resultado BANCO DP
TABELA COLUNA                                                
=============================== =============================== 
PESSOA                PESSOA_ID                                        

--Resultado BANCO SIAP
TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
PESSOA                                                                                        PESSOA_ID                                                                                     
PESSOA_OBRA                                                                                   PESSOA_ID             

Onde o INT_PSSSOA é usado
--Query
SELECT
     rf.RDB$RELATION_NAME AS TABELA,
     rf.RDB$FIELD_NAME AS COLUNA
FROM RDB$RELATION_FIELDS rf
WHERE UPPER(rf.RDB$FIELD_NAME) LIKE '%INT_PSSOA%'
ORDER BY rf.RDB$RELATION_NAME;
--Resultado BANCO DP
TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
ESOCIAL_AUX_S1200_DMDEV                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1200_ITENSREM                                                                    INT_PSSOA                                                                                     
ESOCIAL_AUX_S1200_PRINC                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1200_PROCJUD                                                                     INT_PSSOA                                                                                     
ESOCIAL_AUX_S1200_REMUNOUTR                                                                   INT_PSSOA                                                                                     
ESOCIAL_AUX_S1202_DMDEV                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1202_ITENSREM                                                                    INT_PSSOA                                                                                     
ESOCIAL_AUX_S1202_PRINC                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1207_DMDEV                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1207_ITENSREM                                                                    INT_PSSOA                                                                                     
ESOCIAL_AUX_S1207_PRINC                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S1210_INFOPGTO                                                                    INT_PSSOA                                                                                     
ESOCIAL_AUX_S1210_IRRF                                                                        INT_PSSOA                                                                                     
ESOCIAL_AUX_S1210_IRRF_PLANO                                                                  INT_PSSOA                                                                                     
ESOCIAL_AUX_S1210_PRINC                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S2299_DMDEV                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S2299_ITENSREM                                                                    INT_PSSOA                                                                                     
ESOCIAL_AUX_S2299_PRINC                                                                       INT_PSSOA                                                                                     
ESOCIAL_AUX_S2299_PROCJUD                                                                     INT_PSSOA                                                                                     
ESOCIAL_AUX_S2299_REMUNOUTR                                                                   INT_PSSOA                                                                                     

TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
ESOCIAL_T0008                                                                                 INT_PSSOA                                                                                     
ESOCIAL_T0023                                                                                 INT_PSSOA                                                                                     
ESOCIAL_T0046                                                                                 INT_PSSOA                                                                                     
ESOCIAL_T0071                                                                                 INT_PSSOA                                                                                     
FUNCIONARIO                                                                                   INT_PSSOA                                                                                     
PESSOA_FISICA                                                                                 INT_PSSOA                                                                                     
PESSOA_PREVIDENCIA_VINCULO                                                                    INT_PSSOA                                                                                     
RECLAMATORIA_TRABALHISTA                                                                      INT_PSSOA                                                                                     

--Resultado BANCO SIAP


--Query
SELECT
    'PF com referência em PESSOA' AS STATUS,
    COUNT(*) AS TOTAL
FROM PESSOA_FISICA pf
INNER JOIN PESSOA p ON pf.INT_PSSOA = p.PESSOA_ID;

--Resultado BANCO DP
STATUS                                     TOTAL 
========================== ===================== 
PF com referncia em PESSOA                    6 

ID5_CDR é usado
--Resultado BANCO SIAP
TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
ABASTECIMENTO                                                                                 ID5_CDR                                                                                       
ADIANTAMENTO_RJ                                                                               ID5_CDR                                                                                       
AUTORIZACAO_ABASTECIMENTO                                                                     ID5_CDR                                                                                       
BALANCETE_PATMNAL_AJUSTE                                                                      ID5_CDR                                                                                       
BENS                                                                                          ID5_CDR                                                                                       
BILHETE_RJ                                                                                    ID5_CDR                                                                                       
BKP_LICITANTE_PRODUTO_PREGAO                                                                  ID5_CDR                                                                                       
CONSORCIO_REPASSE                                                                             ID5_CDR                                                                                       
CONTRATOS                                                                                     ID5_CDR                                                                                       
CONTRATOS                                                                                     ID5_CDR_SIG_COTR                                                                              
CONTRATOS                                                                                     ID5_CDR_REP_COTR                                                                              
CONTRATOS                                                                                     ID5_CDR_FSCL_COTR                                                                             
CONTRATOS                                                                                     ID5_CDR_ORG_RESP                                                                              
CONVENIO_CONCEDENTE                                                                           ID5_CDR                                                                                       
CRC_EMITIDOS                                                                                  ID5_CDR                                                                                       
CREDOR                                                                                        ID5_CDR                                                                                       
CREDOR_ATIVIDADES                                                                             ID5_CDR                                                                                       
CREDOR_DEPENDENTE                                                                             ID5_CDR_DPDT                                                                                  
CREDOR_DEPENDENTE                                                                             ID5_CDR                                                                                       
CREDOR_SOCIETARIO                                                                             ID5_CDR                                                                                       

TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
CREDOR_SOCIETARIO                                                                             ID5_CDR_SCIO                                                                                  
CREDOR_TIPO_DOCUMENTO                                                                         ID5_CDR                                                                                       
DESPESAS                                                                                      ID5_CDR                                                                                       
DIARIA_DIARIA                                                                                 ID5_CDR                                                                                       
DIARIA_FUNCIONARIO_CEDIDO                                                                     ID5_CDR                                                                                       
DIARIA_FUND_RTTV                                                                              ID5_CDR                                                                                       
DIARIA_RJ                                                                                     ID5_CDR                                                                                       
DIREITO_RECEBER                                                                               ID5_CDR                                                                                       
DIVIDA_CONSOLIDADA                                                                            ID5_CDR                                                                                       
DOCUMENTOS_DIVERSOS_RJ                                                                        ID5_CDR                                                                                       
FICHAS                                                                                        ID5_CDR                                                                                       
FORNECEDOR_COTACAO                                                                            ID5_CDR                                                                                       
FORNECEDOR_COTACOES                                                                           ID5_CDR                                                                                       
FORNECEDOR_PRODUTO                                                                            ID5_CDR                                                                                       
FORNECEDOR_PRODUTO_COTACAO                                                                    ID5_CDR                                                                                       
FORNECEDOR_PRODUTO_COTACOES                                                                   ID5_CDR                                                                                       
FORNECEDOR_REQUISICAO                                                                         ID5_CDR                                                                                       
FUNDO                                                                                         ID5_CDR                                                                                       
GASTOS                                                                                        ID5_CDR                                                                                       
ITEM_PROPOSTA_COTACAO                                                                         ID5_CDR                                                                                       

TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
ITEM_PROPOSTA_COT_LICI                                                                        ID5_CDR                                                                                       
ITEM_PROPOSTA_PREGAO                                                                          ID5_CDR                                                                                       
LANCAMENTOS_RECEITA                                                                           ID5_CDR                                                                                       
LICITACAO                                                                                     ID5_CDR_ORG_RESP_LICI                                                                         
LICITANTE                                                                                     ID5_CDR                                                                                       
LICITANTE_PRODUTO                                                                             ID5_CDR                                                                                       
LICITANTE_PRODUTO_AUX                                                                         ID5_CDR                                                                                       
LICITANTE_PRODUTO_PREGAO                                                                      ID5_CDR                                                                                       
MOVIMENTO                                                                                     ID5_CDR                                                                                       
NOTA_FISCAL_RJ                                                                                ID5_CDR                                                                                       
OBRA_RESPONSAVEL                                                                              ID5_CDR                                                                                       
ORDEM_COMPRA                                                                                  ID5_CDR                                                                                       
ORGAO                                                                                         ID5_CDR_SFWE                                                                                  
ORGAO                                                                                         ID5_CDR_ASRA                                                                                  
PARCERIA_PUBLICO_PRIVADA                                                                      ID5_CDR                                                                                       
PESSOAL_TERCEIROS                                                                             ID5_CDR                                                                                       
PLANO_CONTAS_ITEM                                                                             ID5_CDR                                                                                       
PLANO_CONTAS_MVTO_DETALHE                                                                     ID5_CDR                                                                                       
PRECATORIO                                                                                    ID5_CDR                                                                                       
PREGOEIRO                                                                                     ID5_CDR_PGR                                                                                   

TABELA                                                                                        COLUNA                                                                                        
=============================================================================== =============================================================================== 
PROCESSO_RJ                                                                                   ID5_CDR                                                                                       
REAJUSTE                                                                                      ID5_CDR                                                                                       
RECIBO_RJ                                                                                     ID5_CDR                                                                                       
REMANEJAMENTO_CREDOR                                                                          ID5_CDR                                                                                       
RESPONSAVEL                                                                                   ID5_CDR                                                                                       
RESTOS_PAGAR_CONTROLE                                                                         ID5_CDR                                                                                       
REVISAO                                                                                       ID5_CDR                                                                                       
SICOM_CONTROLE_ENVIO_LCTT                                                                     ID5_CDR                                                                                       
VEICULO                                                                                       ID5_CDR    

--Query
SELECT
    'EMPRESA com referência em PESSOA' AS STATUS,
    COUNT(*) AS TOTAL
FROM EMPRESA e
INNER JOIN PESSOA p ON e.CNPJ_EMP = p.STR_NUM_DOC_PSOA;
--Resultado BANCO DP
STATUS                                     TOTAL 
========================== ===================== 
PJ com referncia em PESSOA                  2

--Resultado BANCO SIAP

STATUS                                TOTAL 
============================== ============ 
CREDOR com referncia em PESSOA         4159 


--Query
SELECT
    'CNPJ com referência em PESSOA' AS STATUS,
    COUNT(*) AS TOTAL
FROM CNPJ e
INNER JOIN PESSOA p ON e.CNPJ = p.STR_NUM_DOC_PSOA;
--Resultado BANCO DP
STATUS                                     TOTAL 
========================== ===================== 
PJ com referncia em PESSOA                  6 

--Resultado BANCO SIAP



-- TABELA PESSOA -- 
--Resultado BANCO DP
SHOW TABLE PESSOA;
PESSOA_ID                       (INT_) INTEGER Not Null 
NM_PSOA                         (NM70_) VARCHAR(70) Nullable 
STR_TP_DOC_PSOA                 CHAR(1) Nullable 
STR_NUM_DOC_PSOA                VARCHAR(14) Nullable 
SX_PSOA                         VARCHAR(1) Nullable 
D_NSCMNTO                       (D_) DATE Nullable 
D_CAD_PSOA                      (D_) DATE Nullable 
ID7_REF_FUNC                    (ID7_) VARCHAR(7) Nullable 
STR_T_CAD                       CHAR(1) Not Null 
ID3_EMP                         (ID3_) VARCHAR(3) Nullable 
D_VCAO                          (D_) DATE Nullable 
STR_JSTFCTVA                    VARCHAR(100) Nullable 
LG_ENVIO_SICOM                  (LG_) VARCHAR(1) Nullable DEFAULT 'N'
                                CHECK (VALUE IN ('S', 'N'))
CONSTRAINT PK_PESSOA:
  Primary key (PESSOA_ID)

--Resultado BANCO SIAP
SHOW TABLE PESSOA;
PESSOA_ID                       (INT_) INTEGER Not Null 
NM_PSOA                         (NM_) VARCHAR(45) Nullable 
STR_TP_DOC_PSOA                 CHAR(1) Not Null 
STR_NUM_DOC_PSOA                VARCHAR(14) Not Null 
D_CAD_PSOA                      (D_) DATE Nullable 
ID5_REF_CDR                     (ID5_) VARCHAR(5) Nullable DEFAULT NULL
STR_T_CAD                       CHAR(1) Not Null 
CHAR_ID_EMP_CDR                 (CHAR_) VARCHAR(1) Nullable 
D_VCAO                          (D_) DATE Nullable 
LG_SBST_REF                     (LG_) VARCHAR(1) Not Null DEFAULT 'N'
                                CHECK (VALUE IN ('S', 'N', 'B'))
ID5_NVO_CDR                     (ID5_) VARCHAR(5) Nullable DEFAULT NULL
CONSTRAINT PK_PESSOA:
  Primary key (PESSOA_ID)


-- ============================================
-- ANÁLISE 5: ENCONTRAR TABELA DE USUÁRIOS
-- ============================================

USUARIO_COMPETENCIA                                            

-- TABELA USUARIOS --
--Resultado BANCO DP
SHOW TABLE USUARIO_COMPETENCIA;
STR_USER                        (STR_) VARCHAR(20) Not Null 
INT_CMPTNCIA                    (INT_) INTEGER Nullable 
CONSTRAINT FK_USUARIO_CMPTNCIA_CMPTNCIA:
  Foreign key (INT_CMPTNCIA)    References COMPETENCIA (INT_CMPTNCIA)
CONSTRAINT PK_USUARIO_COMPETENCIA:
  Primary key (STR_USER)

--Resultado BANCO SIAP

SHOW TABLE USUARIOS;
STR_NM_USR                      (STR_) VARCHAR(20) Not Null 
STR_SH_USR                      VARCHAR(8) Nullable 
STR_STOR_USR                    VARCHAR(40) Nullable 
STR_FCAO_USR                    VARCHAR(40) Nullable 
STR_ID_USR                      VARCHAR(2) Nullable 
NM_USR                          (NM_) VARCHAR(45) Nullable 
CONSTRAINT INTEG_985:
  Primary key (STR_NM_USR)





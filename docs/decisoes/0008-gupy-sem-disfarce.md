# 0008 — A Gupy é lida pelo endereço do próprio portal, sem disfarce de navegador

**Marco:** M2

## Contexto

A Gupy é o sistema de vagas mais usado no Brasil, e não tem API documentada. O portal dela usa um endereço público de busca. Um repositório brasileiro que lia um endereço antigo parou de funcionar em quatro meses.

## Decisão

- O monitor usa o mesmo endereço que o portal usa (`portal.gupy.io/api/job-search/jobs`), com um pedido por termo, porque a busca é pelo título e estreita.
- Ele se identifica como `monitor-de-vagas`. Não usa user-agent de navegador nem contorna bloqueio. Se a Gupy passar a barrar robô, a fonte sai.
- O CI lê a Gupy de verdade a cada push, para perceber cedo uma mudança de formato.

## Alternativas descartadas

- **Disfarçar-se de navegador:** é contornar a proteção de um site, e a regra do projeto proíbe.
- **Deixar a Gupy de fora:** é onde está o mercado de vagas brasileiro.

## Consequências

- A fonte pode quebrar sem aviso. O isolamento das fontes ([ADR 0003](0003-fonte-fora-do-ar-nao-derruba-as-outras.md)) contém o estrago.

## Como é verificado

- `GupyTest::test_nao_se_disfarca_de_navegador`
- `GupyTest::test_um_pedido_por_termo_e_a_mesma_vaga_vem_uma_vez`
- `GupyTest::test_converte_as_vagas_e_le_o_modelo_de_trabalho`
- `ci.yml`: *Simulação contra as fontes de verdade*

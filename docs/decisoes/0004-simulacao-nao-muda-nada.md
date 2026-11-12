# 0004 — A simulação não grava, não avisa e não marca nenhum relógio

**Marco:** M1 a M3

## Contexto

`vagas:verificar --simular` existe para ver o que seria novo, sem efeito. Para isso valer, a execução de verdade seguinte tem de ver exatamente o mesmo.

## Decisão

- Simulando, nada é gravado, nenhuma fonte guarda o ETag, o relógio de intervalo não é marcado e nenhum aviso sai.
- A simulação **respeita** o intervalo mínimo de cada fonte ([ADR 0007](0007-intervalo-minimo-por-fonte.md)): uma leitura simulada também é uma leitura para o Remotive.

## Alternativas descartadas

- **Simular gravando e apagando depois:** deixa rastro se algo falhar no meio.
- **Simulação sem limite de leitura:** gastaria as 4 leituras diárias do Remotive.

## Consequências

- Rodar a simulação logo depois de uma execução de verdade mostra o Remotive como "lido há pouco".

## Como é verificado

- `MonitorTest::test_simular_nao_grava_nem_confirma_a_leitura`
- `MonitorTest::test_simulacao_respeita_o_intervalo_mas_nao_marca_o_relogio`
- `VerificarVagasCommandTest::test_simulacao_nunca_avisa`, com uma vaga pendente no banco: sem ela, o teste passaria mesmo se a simulação avisasse
- `VerificarVagasCommandTest::test_simulacao_nao_deixa_linha_no_log`

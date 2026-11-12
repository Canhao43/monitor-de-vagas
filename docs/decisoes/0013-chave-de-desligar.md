# 0013 — Chave de desligar os avisos sem parar o monitor

**Marco:** M3

## Contexto

Há momentos em que os avisos devem parar (fim da procura de vaga, um problema no bot), sem perder o que aparecer nesse meio-tempo.

## Decisão

- `VAGAS_AVISOS_ATIVOS=false` desliga só os avisos. As vagas continuam sendo lidas e gravadas, e o aviso delas fica pendente.
- Ligado de volta, as pendentes saem agrupadas ([ADR 0011](0011-aviso-pelo-menos-uma-vez.md)).
- O padrão é desligado: só o comando de configuração liga.

## Alternativas descartadas

- **Desligar o monitor inteiro:** as vagas desse período nunca seriam vistas.

## Consequências

- Uma pausa longa acumula pendentes; a idade máxima ([ADR 0006](0006-remota-regiao-e-idade.md)) limita só o que entra, não o que já estava pendente.

## Como é verificado

- `VerificarVagasCommandTest::test_com_avisos_desligados_grava_e_deixa_o_aviso_pendente`
- `VerificarVagasCommandTest::test_com_avisos_ligados_avisa_as_novas_no_telegram`

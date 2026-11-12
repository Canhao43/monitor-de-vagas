# 0003 — Uma fonte fora do ar não derruba as outras

**Marco:** M1

## Contexto

São quatro fontes independentes, duas delas sem API documentada ou com limite de uso. Cedo ou tarde, uma vai falhar.

## Decisão

- Cada fonte roda dentro do próprio `try`. A falha vai para o resultado e para o log, e as outras seguem.
- O comando só termina com erro se **todas** as fontes falharem. Falha de uma é aviso, e a próxima execução tenta de novo.

## Alternativas descartadas

- **Parar no primeiro erro:** a Gupy fora do ar esconderia as vagas do GitHub.
- **Tentar de novo dentro da mesma execução:** a próxima execução, uma hora depois, já é a nova tentativa.

## Consequências

- Uma fonte pode falhar por dias sem o comando "quebrar". O aviso aparece na saída e no log de cada execução.

## Como é verificado

- `MonitorTest::test_uma_fonte_fora_do_ar_nao_impede_as_outras`
- `VerificarVagasCommandTest::test_uma_fonte_fora_do_ar_avisa_mas_nao_falha_o_comando`
- `VerificarVagasCommandTest::test_falha_quando_todas_as_fontes_falham`

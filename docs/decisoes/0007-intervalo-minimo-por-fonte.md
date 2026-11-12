# 0007 — Cada fonte tem um intervalo mínimo entre leituras

**Marco:** M2

## Contexto

O monitor roda de hora em hora. Os termos de uso do Remotive pedem no máximo 4 leituras por dia. A Gupy não publica limite, e a API do GitHub tem a requisição condicional.

## Decisão

- Cada fonte diz o próprio intervalo: GitHub nenhum, Gupy 1 hora, Remotive 6 horas.
- O monitor pula a fonte lida há menos tempo que o intervalo dela.
- Só a leitura que **deu certo** marca o relógio: a que falhou é tentada de novo na execução seguinte.

## Alternativas descartadas

- **Rodar o monitor 4 vezes por dia para todas as fontes:** atrasaria as que não têm limite.
- **Marcar o relógio em toda tentativa:** uma falha do Remotive custaria 6 horas sem ler.

## Consequências

- O intervalo do Remotive não é configurável para menos: são os termos de uso dele.

## Como é verificado

- `MonitorTest::test_fonte_lida_ha_pouco_fica_para_a_proxima_execucao`
- `MonitorTest::test_leitura_que_falhou_nao_marca_o_relogio`
- `RemotiveTest::test_respeita_as_quatro_leituras_por_dia`

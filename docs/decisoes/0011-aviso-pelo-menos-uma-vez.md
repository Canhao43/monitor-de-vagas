# 0011 — Aviso "pelo menos uma vez", agrupado, parando no primeiro erro

**Marco:** M3

## Contexto

Enviar no Telegram e marcar a vaga como avisada são duas coisas, e o programa pode cair entre elas. Na primeira execução, ou na volta de um aviso desligado, pode haver muitas vagas pendentes. E o Telegram limita a frequência de mensagens (`429`).

## Decisão

- **Pelo menos uma vez:** a vaga só é marcada como avisada depois que o Telegram confirma o envio. Uma queda no meio faz o aviso se repetir, e nunca faz a vaga se perder.
- **Agrupado:** até 10 vagas por mensagem, sem passar do limite de 4.096 caracteres.
- **No primeiro erro, inclusive `429`, para:** o que sobrou fica pendente e vai na próxima execução. Não espera o `retry_after`.
- O aviso cobre todas as pendentes, não só as desta execução.

## Alternativas descartadas

- **"No máximo uma vez"** (marcar antes de enviar): perde a vaga numa queda. Repetir um aviso custa menos.
- **Uma mensagem por vaga:** inunda o celular.
- **Esperar o `retry_after` e tentar de novo:** prende a execução agendada.

## Consequências

- Um aviso pode chegar repetido, raramente.

## Como é verificado

- `AvisadorTest::test_so_marca_depois_que_o_telegram_confirma`
- `AvisadorTest::test_mais_de_dez_vagas_viram_mais_de_uma_mensagem`
- `AvisadorTest::test_quando_o_telegram_pede_espera_para_e_deixa_o_resto_para_depois`
- `AvisadorTest::test_a_vaga_avisada_nao_e_avisada_de_novo`

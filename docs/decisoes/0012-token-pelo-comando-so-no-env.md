# 0012 — O token do Telegram entra por um comando, escondido, e vai só para o .env

**Marco:** M3

## Contexto

O token do bot dá controle total sobre ele. O caminho comum (colar num arquivo, mandar por mensagem para alguém configurar) espalha o token por lugares que ficam.

## Decisão

- `vagas:configurar-telegram` pede o token com a digitação escondida, confere o bot no Telegram, pede um `/start` na conversa com ele, descobre o chat e grava `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` e `VAGAS_AVISOS_ATIVOS=true` no `.env`, que o git ignora.
- Só vale `/start` de conversa **privada**: um `/start` num grupo onde o bot está não vira o destino dos avisos.
- Erro do Telegram é mostrado sem a URL do pedido, que contém o token.
- Token recusado ou `/start` não encontrado: nada é gravado.

## Alternativas descartadas

- **Pedir para colar o token no `.env` à mão:** funciona, mas exige abrir o arquivo e achar o id do chat.
- **Pedir o id do chat:** ninguém sabe o próprio.

## Consequências

- Reconfigurar é rodar o comando de novo; a linha antiga é substituída, não repetida.

## Como é verificado

- `ConfigurarTelegramTest::test_grava_token_e_chat_no_env_e_manda_mensagem_de_teste`
- `ConfigurarTelegramTest::test_token_recusado_nao_grava_nada`
- `ConfigurarTelegramTest::test_sem_o_start_nao_grava_nada`
- `AvisadorTest::test_erro_do_telegram_nao_mostra_o_token`

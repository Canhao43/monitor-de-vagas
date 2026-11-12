# 0002 — Requisição condicional no GitHub, com o ETag guardado só depois de gravar

**Marco:** M1

## Contexto

Os repositórios de vagas mudam pouco de uma hora para a outra. A API do GitHub responde `304` (sem corpo) a um pedido com `If-None-Match` quando nada mudou.

## Decisão

- Cada leitura manda o ETag da leitura anterior. `304` quer dizer "nenhuma vaga nova".
- O ETag novo fica pendente durante a leitura e só é guardado em `confirmarLeitura()`, que o monitor chama **depois** de gravar as vagas.

## Alternativas descartadas

- **Baixar tudo a cada execução:** gasta a cota anônima de 60 pedidos por hora à toa.
- **Guardar o ETag ao receber a resposta:** se a gravação das vagas falhasse depois disso, a próxima leitura receberia `304`, e aquelas vagas não voltariam nunca mais.

## Consequências

- Uma falha na gravação faz a próxima leitura baixar tudo de novo. A chave única ([ADR 0001](0001-vaga-nova-decidida-pela-chave-unica.md)) impede a duplicação.

## Como é verificado

- `RepositorioDeVagasDoGithubTest::test_depois_de_confirmar_a_leitura_a_proxima_busca_e_condicional`
- `RepositorioDeVagasDoGithubTest::test_sem_confirmar_a_leitura_a_proxima_busca_traz_tudo_de_novo`

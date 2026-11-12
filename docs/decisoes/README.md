# Registro de decisões

Cada decisão de arquitetura do projeto, num arquivo só, com o mesmo modelo: **contexto**, **decisão**, **alternativas descartadas**, **consequências** e **como é verificado**, que aponta o teste ou a etapa do CI que prova a decisão.

O registro é conferido pelo build: `RegistroDeDecisoesTest` falha se a numeração tiver buraco, se faltar uma seção, se um ADR não estiver aqui ou se um teste ou etapa citados não existirem.

Uma decisão que mudar ganha um ADR novo que substitui o antigo. O antigo não some.

## M1 — vagas, filtro e deduplicação

- [0001 — Quem decide se a vaga é nova é a chave única do banco](0001-vaga-nova-decidida-pela-chave-unica.md)
- [0002 — Requisição condicional no GitHub, com o ETag guardado só depois de gravar](0002-requisicao-condicional-e-etag-depois-de-gravar.md)
- [0003 — Uma fonte fora do ar não derruba as outras](0003-fonte-fora-do-ar-nao-derruba-as-outras.md)
- [0004 — A simulação não grava, não avisa e não marca nenhum relógio](0004-simulacao-nao-muda-nada.md)
- [0005 — O filtro compara palavra inteira, sem acento e sem maiúscula](0005-filtro-por-palavra-inteira.md)

## M2 — Gupy e Remotive

- [0006 — Remota de verdade, de uma região aceita e publicada há no máximo 30 dias](0006-remota-regiao-e-idade.md)
- [0007 — Cada fonte tem um intervalo mínimo entre leituras](0007-intervalo-minimo-por-fonte.md)
- [0008 — A Gupy é lida pelo endereço do próprio portal, sem disfarce de navegador](0008-gupy-sem-disfarce.md)
- [0009 — Remotive dentro dos termos de uso](0009-remotive-dentro-dos-termos-de-uso.md)
- [0010 — Os testes nunca saem para a internet, e o CI lê as fontes de verdade à parte](0010-testes-sem-internet-e-ci-com-fontes-de-verdade.md)

## M3 — aviso no Telegram

- [0011 — Aviso "pelo menos uma vez", agrupado, parando no primeiro erro](0011-aviso-pelo-menos-uma-vez.md)
- [0012 — O token do Telegram entra por um comando, escondido, e vai só para o .env](0012-token-pelo-comando-so-no-env.md)
- [0013 — Chave de desligar os avisos sem parar o monitor](0013-chave-de-desligar.md)

## M4 — agendamento

- [0014 — Agendador de Tarefas do Windows, de hora em hora, sem janela](0014-agendador-do-windows.md)

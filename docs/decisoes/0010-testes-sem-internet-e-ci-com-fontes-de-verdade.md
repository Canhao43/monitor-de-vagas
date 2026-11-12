# 0010 — Os testes nunca saem para a internet, e o CI lê as fontes de verdade à parte

**Marco:** M1 e M2

## Contexto

As fontes são externas, e o formato delas pode mudar. Os testes precisam ser determinísticos, mas alguém precisa perceber quando uma fonte muda.

## Decisão

- Os testes usam respostas gravadas (`Http::fake`) com o formato real de cada fonte e dados fictícios.
- Todo teste roda com `Http::preventStrayRequests()`: pedido sem resposta simulada falha na hora.
- Um job separado do CI roda `vagas:verificar --simular` contra o GitHub e a Gupy de verdade, e falha se alguma fonte falhar. O GitHub é lido com o token do próprio Actions, porque os runners dividem IP e a cota anônima.
- O Remotive fica de fora desse job: cada push gastaria uma das 4 leituras do dia.

## Alternativas descartadas

- **Confiar que todo teste simula todas as fontes:** no M2, os testes do comando foram para a rede de verdade quando a Gupy e o Remotive foram ligados. Só passavam porque o HTTPS do PHP estava quebrado nesta máquina.
- **Só testes com resposta gravada:** não pegariam uma mudança de formato da fonte.

## Consequências

- Uma mudança de formato do Remotive só aparece em produção, no aviso de fonte que falhou.

## Como é verificado

- `GupyTest::test_erro_do_portal_vira_excecao`
- `ci.yml`: *Simulação contra as fontes de verdade*

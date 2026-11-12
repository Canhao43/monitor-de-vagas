# 0014 — Agendador de Tarefas do Windows, de hora em hora, sem janela

**Marco:** M4

## Contexto

O monitor é para uso pessoal, num PC com Windows que fica ligado. Ele não deve depender de servidor, nem do Claude, nem de alguém lembrar de rodar.

## Decisão

- `scripts/agendar-no-windows.ps1` registra a tarefa "Monitor de vagas": de hora em hora, só com o usuário logado (sem guardar senha), e começando assim que o PC ligar se a hora marcada passou.
- Sem duas execuções ao mesmo tempo (`IgnoreNew`) e com limite de 10 minutos por execução.
- O PHP roda dentro de `conhost --headless`: o console existe, mas a janela não pisca na tela a cada hora.
- Cada execução deixa uma linha no log (novas, avisadas, fontes que falharam, fontes puladas). O log é diário e guardado por 14 dias.
- `scripts/desagendar-no-windows.ps1` desfaz.

## Alternativas descartadas

- **`schedule:run` a cada minuto, como no Linux:** 60 execuções por hora para fazer uma.
- **Servidor ou nuvem:** custo e manutenção para um uso pessoal.
- **Rodar mesmo com o usuário deslogado:** exige guardar a senha do Windows na tarefa.

## Consequências

- Com o PC desligado, não há leitura. A primeira execução depois de ligar recupera, porque as fontes ainda têm as vagas.

## Como é verificado

- `VerificarVagasCommandTest::test_cada_execucao_deixa_uma_linha_no_log`
- `ci.yml`: *Sintaxe dos scripts do agendador do Windows*

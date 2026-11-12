<#
    Registra o monitor no Agendador de Tarefas do Windows: de hora em hora,
    sem janela e sem duas execuções ao mesmo tempo.

    Roda só com o usuário logado, e por isso não pede nem guarda senha.
    Para desfazer: scripts\desagendar-no-windows.ps1

    Uso, na pasta do projeto:
        powershell -ExecutionPolicy Bypass -File scripts\agendar-no-windows.ps1
#>

$ErrorActionPreference = 'Stop'

$nome = 'Monitor de vagas'
$projeto = Split-Path -Parent $PSScriptRoot
$php = (Get-Command php -ErrorAction Stop).Source

if (-not (Test-Path (Join-Path $projeto '.env'))) {
    throw "Falta o .env em $projeto. Rode a instalação do README antes."
}

# O "conhost --headless" abre o console do PHP sem mostrar a janela. Sem
# isso, uma janela pisca na tela a cada hora.
$acao = New-ScheduledTaskAction `
    -Execute 'conhost.exe' `
    -Argument "--headless `"$php`" artisan vagas:verificar" `
    -WorkingDirectory $projeto

$gatilho = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Hours 1)

# StartWhenAvailable: se o PC estava desligado na hora marcada, roda assim que
# ligar. IgnoreNew: se uma execução ainda está rodando, a próxima não começa
# (a chave única do banco já impede aviso duplicado; isto evita o trabalho).
$configuracao = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10) `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries

Register-ScheduledTask `
    -TaskName $nome `
    -Action $acao `
    -Trigger $gatilho `
    -Settings $configuracao `
    -Description 'Lê as fontes de vaga e avisa as novas no Telegram (github.com/Canhao43/monitor-de-vagas).' `
    -Force | Out-Null

$tarefa = Get-ScheduledTask -TaskName $nome
$proxima = (Get-ScheduledTaskInfo -TaskName $nome).NextRunTime
Write-Host "Tarefa '$nome' registrada ($($tarefa.State)). Próxima execução: $proxima."
Write-Host "Cada execução deixa uma linha em storage\logs."

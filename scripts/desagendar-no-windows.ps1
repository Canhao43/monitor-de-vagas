<#
    Tira o monitor do Agendador de Tarefas do Windows. O banco e o .env ficam
    como estão: agendar de novo continua de onde parou.
#>

$ErrorActionPreference = 'Stop'
$nome = 'Monitor de vagas'

if (Get-ScheduledTask -TaskName $nome -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName $nome -Confirm:$false
    Write-Host "Tarefa '$nome' removida."
} else {
    Write-Host "A tarefa '$nome' não estava agendada."
}

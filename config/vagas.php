<?php

$lista = fn (string $valor) => array_values(array_filter(array_map('trim', explode(',', $valor))));

return [

    /*
    | O perfil. Uma vaga entra se o título tiver ao menos um dos termos e
    | nenhum dos excluídos. A comparação ignora maiúscula e acento.
    */
    'termos' => $lista(env('VAGAS_TERMOS', 'php,laravel,back-end,backend,protheus')),

    'excluir' => $lista(env('VAGAS_EXCLUIR', 'estagio,estagiario,trainee,junior,jr')),

    // Vaga que não diz se é remota conta como não remota.
    'somente_remotas' => (bool) env('VAGAS_SOMENTE_REMOTAS', true),

    'github' => [
        // Repositórios de vagas em que cada vaga é uma issue aberta.
        'repositorios' => $lista(env('VAGAS_GITHUB_REPOSITORIOS', 'backend-br/vagas,phpdevbr/vagas')),

        // Opcional. Sem token, a API pública dá 60 requisições por hora por
        // IP, e o monitor faz uma por repositório a cada execução.
        'token' => env('VAGAS_GITHUB_TOKEN'),
    ],

];

<?php

$lista = fn (string $valor) => array_values(array_filter(array_map('trim', explode(',', $valor))));

return [

    /*
    | O perfil. Uma vaga entra se o título tiver ao menos um dos termos e
    | nenhum dos excluídos. A comparação ignora maiúscula e acento.
    */
    'termos' => $lista(env('VAGAS_TERMOS', 'php,laravel,protheus')),

    'excluir' => $lista(env('VAGAS_EXCLUIR', 'estagio,estagiario,trainee,junior,jr,banco de talentos')),

    // Vaga publicada há mais tempo que isso provavelmente já foi preenchida.
    // A Gupy devolve vagas de anos atrás na mesma busca das de hoje.
    'dias_maximos' => (int) env('VAGAS_DIAS_MAXIMOS', 30),

    // Vaga que não diz se é remota conta como não remota.
    'somente_remotas' => (bool) env('VAGAS_SOMENTE_REMOTAS', true),

    // Só vale para vaga que restringe de onde aceita candidato (o Remotive
    // diz: "Worldwide", "USA Only", "LATAM"). Vaga sem restrição passa.
    'regioes_aceitas' => $lista(env('VAGAS_REGIOES_ACEITAS', 'worldwide,anywhere,brazil,brasil,latam,latin america,americas,south america')),

    'github' => [
        // Repositórios de vagas em que cada vaga é uma issue aberta.
        'repositorios' => $lista(env('VAGAS_GITHUB_REPOSITORIOS', 'backend-br/vagas,phpdevbr/vagas')),

        // Opcional. Sem token, a API pública dá 60 requisições por hora por
        // IP, e o monitor faz uma por repositório a cada execução.
        'token' => env('VAGAS_GITHUB_TOKEN'),
    ],

    'gupy' => [
        'ativa' => (bool) env('VAGAS_GUPY_ATIVA', true),
        // A busca da Gupy é pelo título e estreita: termos curtos, um por pedido.
        'buscas' => $lista(env('VAGAS_GUPY_BUSCAS', 'php,laravel,protheus')),
    ],

    'remotive' => [
        'ativa' => (bool) env('VAGAS_REMOTIVE_ATIVA', true),
        'categoria' => env('VAGAS_REMOTIVE_CATEGORIA', 'software-dev'),
    ],

    'avisos' => [
        // A chave de desligar. Desligado, as vagas novas continuam sendo
        // gravadas, e o aviso delas fica pendente até ligar de novo.
        'ativos' => (bool) env('VAGAS_AVISOS_ATIVOS', false),

        // Gravados pelo comando vagas:configurar-telegram, só no .env.
        'telegram' => [
            'token' => env('TELEGRAM_BOT_TOKEN'),
            'chat_id' => env('TELEGRAM_CHAT_ID'),
        ],
    ],

];

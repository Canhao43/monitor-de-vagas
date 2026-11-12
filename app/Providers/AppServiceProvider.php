<?php

namespace App\Providers;

use App\Vagas\Filtro;
use App\Vagas\Fontes\RepositorioDeVagasDoGithub;
use App\Vagas\Monitor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Filtro::class, fn () => new Filtro(
            termos: config('vagas.termos'),
            excluir: config('vagas.excluir'),
            somenteRemotas: config('vagas.somente_remotas'),
        ));

        // As fontes saem da configuração: trocar ou acrescentar um repositório
        // de vagas não mexe em código.
        $this->app->bind(Monitor::class, fn ($app) => new Monitor(
            fontes: array_map(
                fn (string $repositorio) => new RepositorioDeVagasDoGithub($repositorio, config('vagas.github.token')),
                config('vagas.github.repositorios'),
            ),
            filtro: $app->make(Filtro::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Vagas\Filtro;
use App\Vagas\Fontes\Fonte;
use App\Vagas\Fontes\Gupy;
use App\Vagas\Fontes\Remotive;
use App\Vagas\Fontes\RepositorioDeVagasDoGithub;
use App\Vagas\Monitor;
use Carbon\CarbonImmutable;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Filtro::class, fn () => new Filtro(
            termos: config('vagas.termos'),
            excluir: config('vagas.excluir'),
            somenteRemotas: config('vagas.somente_remotas'),
            regioesAceitas: config('vagas.regioes_aceitas'),
            publicadaDesde: CarbonImmutable::now()->subDays(config('vagas.dias_maximos')),
        ));

        // As fontes saem da configuração: ligar, desligar ou acrescentar um
        // repositório de vagas não mexe em código.
        $this->app->bind(Monitor::class, fn ($app) => new Monitor(
            fontes: $this->fontes(),
            filtro: $app->make(Filtro::class),
        ));
    }

    /**
     * @return list<Fonte>
     */
    private function fontes(): array
    {
        $fontes = array_map(
            fn (string $repositorio) => new RepositorioDeVagasDoGithub($repositorio, config('vagas.github.token')),
            config('vagas.github.repositorios'),
        );

        if (config('vagas.gupy.ativa')) {
            $fontes[] = new Gupy(config('vagas.gupy.buscas'));
        }

        if (config('vagas.remotive.ativa')) {
            $fontes[] = new Remotive(config('vagas.remotive.categoria'));
        }

        return $fontes;
    }

    public function boot(): void
    {
        //
    }
}

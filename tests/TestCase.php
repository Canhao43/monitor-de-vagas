<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nenhum teste fala com a internet: requisição sem resposta simulada
        // falha na hora. Sem isso, uma fonte esquecida no Http::fake sai para
        // a rede de verdade, e o teste passa ou falha conforme a fonte.
        Http::preventStrayRequests();
    }
}

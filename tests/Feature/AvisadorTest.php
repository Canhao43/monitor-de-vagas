<?php

namespace Tests\Feature;

use App\Avisos\Avisador;
use App\Avisos\Telegram;
use App\Avisos\TelegramPediuEspera;
use App\Models\Vaga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AvisadorTest extends TestCase
{
    use RefreshDatabase;

    private function vaga(int $n, array $extra = []): Vaga
    {
        return Vaga::create($extra + [
            'fonte' => 'gupy',
            'id_externo' => (string) $n,
            'titulo' => "Desenvolvedor PHP {$n}",
            'empresa' => 'Empresa Fictícia',
            'remota' => true,
            'url' => "https://ficticia.gupy.io/job/{$n}",
            'vista_em' => now(),
        ]);
    }

    private function avisador(): Avisador
    {
        return new Avisador(new Telegram('token-de-teste'), '12345');
    }

    private function telegramOk(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
    }

    public function test_avisa_as_pendentes_numa_mensagem_e_marca_como_avisadas(): void
    {
        $this->telegramOk();
        $this->vaga(1);
        $this->vaga(2);

        $resultado = $this->avisador()->avisarPendentes();

        $this->assertSame(2, $resultado->avisadas);
        $this->assertNull($resultado->erro);
        $this->assertSame(0, Vaga::whereNull('avisada_em')->count());
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $pedido) => $pedido['chat_id'] === '12345'
            && str_contains($pedido['text'], '2 vagas novas')
            && str_contains($pedido['text'], 'https://ficticia.gupy.io/job/2'));
    }

    public function test_a_vaga_avisada_nao_e_avisada_de_novo(): void
    {
        $this->telegramOk();
        $this->vaga(1);

        $this->avisador()->avisarPendentes();
        $segunda = $this->avisador()->avisarPendentes();

        $this->assertSame(0, $segunda->avisadas);
        Http::assertSentCount(1);
    }

    public function test_so_marca_depois_que_o_telegram_confirma(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 500, 'description' => 'Internal Server Error'], 500)]);
        $this->vaga(1);

        $resultado = $this->avisador()->avisarPendentes();

        // Fica pendente: vai na próxima execução, em vez de se perder.
        $this->assertSame(0, $resultado->avisadas);
        $this->assertNotNull($resultado->erro);
        $this->assertSame(1, Vaga::whereNull('avisada_em')->count());
    }

    public function test_mais_de_dez_vagas_viram_mais_de_uma_mensagem(): void
    {
        $this->telegramOk();
        foreach (range(1, 12) as $n) {
            $this->vaga($n);
        }

        $this->assertSame(12, $this->avisador()->avisarPendentes()->avisadas);
        Http::assertSentCount(2);
    }

    public function test_quando_o_telegram_pede_espera_para_e_deixa_o_resto_para_depois(): void
    {
        Http::fake(['api.telegram.org/*' => Http::sequence()
            ->push(['ok' => true, 'result' => ['message_id' => 1]])
            ->push(['ok' => false, 'error_code' => 429, 'parameters' => ['retry_after' => 30]], 429)
            ->push(['ok' => true, 'result' => ['message_id' => 2]])]);
        foreach (range(1, 25) as $n) {
            $this->vaga($n);
        }

        $resultado = $this->avisador()->avisarPendentes();

        // 3 lotes: o 1º vai, o 2º leva 429, e o 3º nem é tentado.
        $this->assertSame(10, $resultado->avisadas);
        $this->assertSame(15, Vaga::whereNull('avisada_em')->count());
        Http::assertSentCount(2);
        $this->assertStringContainsString('30 s', $resultado->erro);
    }

    public function test_escapa_o_html_e_diz_de_que_fonte_a_vaga_veio(): void
    {
        $this->telegramOk();
        $this->vaga(1, ['titulo' => 'Dev <PHP> & Laravel', 'fonte' => 'remotive', 'url' => 'https://remotive.com/remote-jobs/x-1']);

        $this->avisador()->avisarPendentes();

        Http::assertSent(fn (Request $pedido) => str_contains($pedido['text'], 'Dev &lt;PHP&gt; &amp; Laravel')
            && str_contains($pedido['text'], 'Remotive')
            && $pedido['parse_mode'] === 'HTML');
    }

    public function test_erro_do_telegram_nao_mostra_o_token(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401)]);

        try {
            (new Telegram('123:segredo'))->quemSouEu();
            $this->fail('Deveria ter lançado exceção.');
        } catch (RuntimeException $erro) {
            $this->assertNotInstanceOf(TelegramPediuEspera::class, $erro);
            $this->assertStringNotContainsString('segredo', $erro->getMessage());
        }
    }
}

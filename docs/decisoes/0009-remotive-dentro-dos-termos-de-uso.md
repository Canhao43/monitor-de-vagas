# 0009 — Remotive dentro dos termos de uso

**Marco:** M2 e M3

## Contexto

O Remotive tem API pública, com regras escritas na própria resposta: no máximo 4 leituras por dia, link de volta para o Remotive e menção a ele como fonte. Os rótulos que ele põe nas vagas são amplos: uma vaga de assistente de escritório vem com `php`.

## Decisão

- Um pedido só por leitura, pela categoria inteira de desenvolvimento. O filtro do perfil faz o resto.
- O filtro olha só o título das vagas do Remotive.
- O aviso leva o link do próprio Remotive e diz "Remotive" como fonte.
- O intervalo é de 6 horas ([ADR 0007](0007-intervalo-minimo-por-fonte.md)).

## Alternativas descartadas

- **Um pedido por termo, como na Gupy:** multiplicaria as leituras que os termos limitam.
- **Usar os rótulos no filtro:** traria vagas que nada têm a ver com o perfil.

## Consequências

- O Remotive mostra as vagas com 24 horas de atraso, pelas regras dele.
- O CI não lê o Remotive de verdade ([ADR 0010](0010-testes-sem-internet-e-ci-com-fontes-de-verdade.md)).

## Como é verificado

- `RemotiveTest::test_um_pedido_so_pela_categoria`
- `RemotiveTest::test_converte_as_vagas_com_regiao_e_sem_os_rotulos`
- `AvisadorTest::test_escapa_o_html_e_diz_de_que_fonte_a_vaga_veio`

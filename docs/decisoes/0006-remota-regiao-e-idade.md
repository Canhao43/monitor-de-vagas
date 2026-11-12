# 0006 — Remota de verdade, de uma região aceita e publicada há no máximo 30 dias

**Marco:** M1 e M2

## Contexto

Na primeira leitura real das três fontes vieram 35 vagas, e boa parte não servia: vagas da Gupy de 2021 e de páginas de empresa desativadas, remotas "só para quem mora nos EUA", e "bancos de talentos".

## Decisão

- **Só remota:** vaga que não diz se é remota conta como não remota.
- **Região aceita:** vaga que restringe de onde contrata só passa se a região estiver na lista (`worldwide`, `brazil`, `latam`, `americas`…). Vaga que não restringe passa.
- **Idade máxima de 30 dias** (`VAGAS_DIAS_MAXIMOS`), para vaga que informa a data.

Com isso, as 35 viraram 11. Com o perfil só de PHP, Laravel e Protheus, ficaram 3.

## Alternativas descartadas

- **Tratar remota desconhecida como remota:** melhor perder uma vaga ambígua do que avisar uma presencial.
- **Avisar tudo o que a fonte devolve:** o aviso vira ruído e para de ser lido.

## Consequências

- Vaga boa sem data e sem região passa; vaga boa com data velha, mas ainda aberta, não passa.

## Como é verificado

- `FiltroTest::test_so_remota_recusa_presencial_e_desconhecida`
- `FiltroTest::test_vaga_que_restringe_regiao_so_passa_se_a_regiao_for_aceita`
- `FiltroTest::test_vaga_velha_demais_nao_passa`

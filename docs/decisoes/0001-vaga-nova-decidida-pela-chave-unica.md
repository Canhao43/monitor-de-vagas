# 0001 — Quem decide se a vaga é nova é a chave única do banco

**Marco:** M1

## Contexto

O monitor roda de hora em hora e lê, a cada vez, todas as vagas abertas das fontes. Só a vaga que nunca foi vista pode virar aviso. Duas execuções podem se sobrepor (uma lenta e a seguinte já começando).

## Decisão

- A tabela `vagas` tem chave única em `fonte` + `id_externo` (o id da vaga na fonte).
- A vaga é gravada direto. Se a gravação bate na chave, a vaga já existia e não é nova. A violação da chave é o caso normal, não um erro.

## Alternativas descartadas

- **Consultar se existe e, se não, gravar:** entre a consulta e a gravação, outra execução pode gravar a mesma vaga, e as duas avisariam.
- **Chave só no id da fonte:** o mesmo número pode existir em fontes diferentes (a issue 10 de dois repositórios).

## Consequências

- Uma vaga republicada pela fonte com outro id é avisada de novo.

## Como é verificado

- `MonitorTest::test_a_mesma_vaga_na_execucao_seguinte_nao_e_nova`
- `MonitorTest::test_vaga_gravada_por_outra_execucao_ao_mesmo_tempo_nao_conta_como_nova`
- `MonitorTest::test_o_mesmo_id_em_fontes_diferentes_sao_vagas_diferentes`

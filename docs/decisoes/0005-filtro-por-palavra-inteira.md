# 0005 — O filtro compara palavra inteira, sem acento e sem maiúscula

**Marco:** M1

## Contexto

O perfil é uma lista de termos que interessam (`php`, `laravel`, `protheus`) e uma de termos que tiram a vaga (`junior`, `estagio`, `banco de talentos`). As fontes escrevem de todo jeito: "PHP/Laravel", "Júnior", "JR".

## Decisão

- Tudo é comparado depois de tirar acento e maiúscula.
- O termo tem de aparecer como palavra inteira: `php` não casa com `PHPUnit`, e `jr` não tira uma vaga da "Jrsoft".
- No GitHub, o filtro olha título **e** rótulos: o `backend-br` marca nível e modelo de trabalho como rótulos.
- Os termos ficam no `.env` (`VAGAS_TERMOS`, `VAGAS_EXCLUIR`), sem mexer em código.

## Alternativas descartadas

- **"Contém" simples:** `jr` tiraria vaga de empresa com "jr" no nome, e `php` aceitaria vaga de "PHPUnit".
- **Só o título, em todas as fontes:** no `backend-br`, "Júnior" vem como rótulo.

## Consequências

- Termo com variação de escrita precisa estar na lista (`estagio` e `estagiario`).

## Como é verificado

- `FiltroTest::test_compara_palavra_inteira`
- `FiltroTest::test_ignora_maiuscula_e_acento`
- `FiltroTest::test_exclui_pelo_rotulo_da_fonte`
- `FiltroTest::test_aceita_vaga_com_algum_termo`

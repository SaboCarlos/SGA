<?php

declare(strict_types=1);

/**
 * Verificacao das regras de dominio sem dependencias externas.
 *
 * Objectivo: permitir executar as regras RN-01 a RN-12 numa maquina com apenas
 * PHP instalado, antes de o ambiente Docker e o Composer estarem prontos.
 *
 * Nao substitui a suite de testes (backend/tests, Pest) -- reproduz os mesmos
 * casos com um verificador minimo, para que o calculo possa ser confirmado no
 * primeiro dia da Fase 4.
 *
 * Execucao:  php verificar-dominio.php
 */

// ---------------------------------------------------------------- autoload PSR-4
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'App\\';
    $base    = __DIR__ . '/backend/app/';

    if (! str_starts_with($classe, $prefixo)) {
        return;
    }

    $caminho = $base . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';

    if (is_file($caminho)) {
        require $caminho;
    }
});

use App\Dominio\Enums\SituacaoFinal;
use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Enums\TipoAvaliacao;
use App\Dominio\Excepcoes\EstruturaAvaliacaoInvalida;
use App\Dominio\Regras\Regulamento\FabricaRegulamento;
use App\Dominio\Regras\ValidadorInscricao;
use App\Dominio\Valores\ContextoInscricao;
use App\Dominio\Valores\NotaAvaliacao;

// ------------------------------------------------------------ verificador minimo
final class Verificador
{
    private int $passou = 0;
    private array $falhou = [];
    private string $grupo = '';

    public function grupo(string $nome): void
    {
        $this->grupo = $nome;
        echo "\n\033[1m{$nome}\033[0m\n";
    }

    public function igual(mixed $esperado, mixed $obtido, string $descricao): void
    {
        $ok = $esperado === $obtido;
        $this->registar($ok, $descricao, $this->formatar($esperado), $this->formatar($obtido));
    }

    public function verdadeiro(bool $condicao, string $descricao): void
    {
        $this->registar($condicao, $descricao, 'true', $condicao ? 'true' : 'false');
    }

    public function lanca(string $excepcao, callable $acao, string $descricao): void
    {
        try {
            $acao();
            $this->registar(false, $descricao, $excepcao, 'nenhuma excepcao');
        } catch (Throwable $e) {
            $this->registar(
                $e instanceof $excepcao,
                $descricao,
                $excepcao,
                get_class($e)
            );
        }
    }

    private function registar(bool $ok, string $descricao, string $esperado, string $obtido): void
    {
        if ($ok) {
            $this->passou++;
            echo "  \033[32mOK\033[0m   {$descricao}\n";

            return;
        }

        $this->falhou[] = "{$this->grupo} :: {$descricao} (esperado {$esperado}, obtido {$obtido})";
        echo "  \033[31mFALHA\033[0m {$descricao} -- esperado {$esperado}, obtido {$obtido}\n";
    }

    private function formatar(mixed $v): string
    {
        return match (true) {
            $v === null      => 'null',
            is_bool($v)      => $v ? 'true' : 'false',
            $v instanceof SituacaoFrequencia,
            $v instanceof SituacaoFinal => $v->value,
            is_array($v)     => '[' . implode(', ', array_map('strval', $v)) . ']',
            default          => (string) $v,
        };
    }

    public function resumo(): int
    {
        $total = $this->passou + count($this->falhou);
        echo "\n" . str_repeat('-', 64) . "\n";
        echo "Total: {$total} | Passou: {$this->passou} | Falhou: " . count($this->falhou) . "\n";

        foreach ($this->falhou as $f) {
            echo "  - {$f}\n";
        }

        return $this->falhou === [] ? 0 : 1;
    }
}

/** Atalho para construir uma nota de frequencia. */
function nota(float $peso, ?float $valor, bool $faltou = false): NotaAvaliacao
{
    return new NotaAvaliacao(TipoAvaliacao::TESTE, $peso, $valor, $faltou);
}

$v = new Verificador();
$reg = (new FabricaRegulamento())->porCodigo('PADRAO_ESCN');

// ------------------------------------------------- RN-07: media ponderada e arredondamento
$v->grupo('RN-07 - Nota de frequencia (media ponderada, 1 casa decimal)');

$v->igual(15.0, $reg->calcularFrequencia([nota(50, 14.0), nota(50, 16.0)]),
    'dois testes de peso 50% com 14 e 16 dao 15,0');

// 12*0,30 + 13*0,30 + 15*0,40 = 3,6 + 3,9 + 6,0 = 13,5
$v->igual(13.5, $reg->calcularFrequencia([nota(30, 12.0), nota(30, 13.0), nota(40, 15.0)]),
    'pesos 30/30/40 com 12, 13 e 15 dao 13,5');

$v->igual(20.0, $reg->calcularFrequencia([nota(100, 20.0)]),
    'avaliacao unica de peso 100% com 20 da 20,0');

$v->igual(0.0, $reg->calcularFrequencia([nota(60, null, true), nota(40, null, true)]),
    'duas faltas dao 0,0 (falta conta como zero)');

$v->igual(9.0, $reg->calcularFrequencia([nota(50, 18.0), nota(50, null, true)]),
    '18 e uma falta dao 9,0');

$v->igual(13.4, $reg->calcularFrequencia([nota(50, 13.35), nota(50, 13.35)]),
    'arredondamento de 13,35 para 13,4 (meio para cima)');

// o exame nao entra na frequencia
$v->igual(15.0, $reg->calcularFrequencia([
    nota(100, 15.0),
    new NotaAvaliacao(TipoAvaliacao::EXAME, 100, 8.0),
]), 'a nota de exame e ignorada no calculo da frequencia');

// ------------------------------------------------------------- RN-06: soma dos pesos
$v->grupo('RN-06 - Soma dos pesos das avaliacoes de frequencia');

$v->lanca(EstruturaAvaliacaoInvalida::class,
    static fn () => $reg->calcularFrequencia([nota(40, 15.0), nota(40, 15.0)]),
    'soma de 80% e recusada');

$v->lanca(EstruturaAvaliacaoInvalida::class,
    static fn () => $reg->calcularFrequencia([nota(60, 15.0), nota(60, 15.0)]),
    'soma de 120% e recusada');

$v->igual(15.0, $reg->calcularFrequencia([nota(33.33, 15.0), nota(33.33, 15.0), nota(33.34, 15.0)]),
    'soma de 100,00% com pesos fraccionarios e aceita');

// -------------------------------------- RN-08 / RN-09: fronteiras da situacao de frequencia
$v->grupo('RN-08 e RN-09 - Situacao de frequencia (casos de fronteira)');

$fronteiras = [
    [9.9,  SituacaoFrequencia::REPROVADO_FREQUENCIA, '9,9 reprova por frequencia'],
    [9.95, SituacaoFrequencia::REPROVADO_FREQUENCIA, '9,95 (valor nao arredondado) reprova'],
    [10.0, SituacaoFrequencia::ADMITIDO_EXAME,       '10,0 admite a exame (limite inferior)'],
    [10.1, SituacaoFrequencia::ADMITIDO_EXAME,       '10,1 admite a exame'],
    [13.9, SituacaoFrequencia::ADMITIDO_EXAME,       '13,9 admite a exame (limite superior)'],
    [13.94, SituacaoFrequencia::ADMITIDO_EXAME,      '13,94 admite a exame'],
    [14.0, SituacaoFrequencia::DISPENSADO,           '14,0 dispensa (limite inferior)'],
    [20.0, SituacaoFrequencia::DISPENSADO,           '20,0 dispensa'],
];

foreach ($fronteiras as [$nf, $esperado, $descricao]) {
    $v->igual($esperado, $reg->determinarSituacaoFrequencia($nf), $descricao);
}

// nota: 13,95 arredondado pela frequencia da 14,0 e concede dispensa
$v->igual(14.0, $reg->calcularFrequencia([nota(50, 13.95), nota(50, 13.95)]),
    '13,95 arredonda para 14,0');
$v->igual(SituacaoFrequencia::DISPENSADO,
    $reg->determinarSituacaoFrequencia($reg->calcularFrequencia([nota(50, 13.95), nota(50, 13.95)])),
    'frequencia de 13,95 arredondada concede dispensa');

// ------------------------------------------------- RN-10: classificacao final e situacao
$v->grupo('RN-10 - Classificacao final e situacao final');

$v->igual(16.0, $reg->calcularClassificacaoFinal(16.0),
    'dispensado: a final e a propria frequencia');

$v->igual(null, $reg->calcularClassificacaoFinal(12.0),
    'admitido a exame sem exame realizado: final indefinida (em curso)');

$v->igual(13.0, $reg->calcularClassificacaoFinal(12.0, 14.0),
    'admitido a exame com 14 no exame: final 13,0');

$v->igual(8.0, $reg->calcularClassificacaoFinal(8.0),
    'reprovado por frequencia: final igual a frequencia');

$v->igual(SituacaoFinal::APROVADO, $reg->determinarSituacaoFinal(10.0),
    '10,0 aprova (limite)');
$v->igual(SituacaoFinal::REPROVADO, $reg->determinarSituacaoFinal(9.9),
    '9,9 reprova');
$v->igual(SituacaoFinal::EM_CURSO, $reg->determinarSituacaoFinal(null),
    'sem classificacao final: em curso');

// --------------------------------------------------------- RN-12: media do curso
$v->grupo('RN-12 - Media do curso ponderada por creditos');

$v->igual(15.0, $reg->calcularMediaCurso([
    ['classificacao' => 15.0, 'creditos' => 6],
    ['classificacao' => 15.0, 'creditos' => 6],
]), 'duas disciplinas iguais dao a mesma media');

$v->igual(16.0, $reg->calcularMediaCurso([
    ['classificacao' => 18.0, 'creditos' => 6],
    ['classificacao' => 12.0, 'creditos' => 3],
]), 'ponderacao por creditos: 18 (6 cr) e 12 (3 cr) dao 16,0');

$v->igual(null, $reg->calcularMediaCurso([]),
    'sem disciplinas aprovadas a media e indefinida');

// -------------------------------------------------- Strategy: regulamento alternativo
$v->grupo('ADR-004 - Strategy: regulamento alternativo sem exame');

$semExame = (new FabricaRegulamento())->porCodigo('SEM_EXAME');

$v->igual(SituacaoFrequencia::DISPENSADO, $semExame->determinarSituacaoFrequencia(10.0),
    'no regulamento sem exame, 10,0 aprova directamente');
$v->igual(12.0, $semExame->calcularClassificacaoFinal(12.0),
    'a final e sempre a frequencia');
$v->verdadeiro($semExame->codigo() !== $reg->codigo(),
    'os dois regulamentos coexistem sem alteracao do servico');

// ------------------------------------------------------ RN-01 a RN-05: inscricao
$v->grupo('RN-01 a RN-05 - Validacao da inscricao');

$validador = new ValidadorInscricao();

$base = static fn (array $alteracoes = []): ContextoInscricao => new ContextoInscricao(
    disciplinaPretendidaId:     301,
    disciplinasDoPlano:         $alteracoes['plano']        ?? [101, 201, 301],
    disciplinasAprovadas:       $alteracoes['aprovadas']    ?? [101, 201],
    preRequisitosDaDisciplina:  $alteracoes['prereq']       ?? [201],
    disciplinasJaInscritas:     $alteracoes['inscritas']    ?? [],
    inscritosNaTurma:           $alteracoes['inscritos']    ?? 10,
    capacidadeDaTurma:          $alteracoes['capacidade']   ?? 30,
    periodoInscricoesAberto:    $alteracoes['aberto']       ?? true,
    designacaoDisciplina:       'Engenharia de Software',
    designacoes:                [101 => 'Programação I', 201 => 'Programação II'],
    dispensarPreRequisitos:     $alteracoes['dispensa']     ?? false,
);

$v->verdadeiro($validador->validar($base())->valido(),
    'inscricao valida e aceita');

$v->igual(['RN-05'], $validador->validar($base(['aberto' => false]))->codigos(),
    'periodo fechado viola RN-05');

$v->igual(['RN-01'], $validador->validar($base(['plano' => [101, 201]]))->codigos(),
    'disciplina fora do plano viola RN-01');

$v->igual(['RN-02'], $validador->validar($base(['aprovadas' => [101]]))->codigos(),
    'pre-requisito nao aprovado viola RN-02');

$v->igual(['RN-03'], $validador->validar($base(['inscritas' => [301]]))->codigos(),
    'inscricao duplicada viola RN-03');

$v->igual(['RN-04'], $validador->validar($base(['inscritos' => 30]))->codigos(),
    'turma sem vagas viola RN-04');

$v->igual(['RN-04'], $validador->validar($base(['inscritos' => 31]))->codigos(),
    'turma acima da capacidade viola RN-04');

$v->verdadeiro($validador->validar($base(['aprovadas' => [101], 'dispensa' => true]))->valido(),
    'dispensa administrativa de pre-requisitos (UC18) permite a inscricao');

// as violacoes sao acumuladas, nao interrompidas na primeira
$multiplas = $validador->validar($base([
    'aberto'    => false,
    'plano'     => [101, 201],
    'aprovadas' => [101],
    'inscritos' => 30,
]));
$v->igual(['RN-05', 'RN-01', 'RN-02', 'RN-04'], $multiplas->codigos(),
    'quatro regras violadas sao reportadas em conjunto (RNF-21)');

// ------------------------------------------------------ validacao do objecto de valor
$v->grupo('RF-38 - Escala de classificacao 0 a 20');

$v->lanca(InvalidArgumentException::class,
    static fn () => nota(100, 21.0),
    'classificacao de 21 e recusada na construcao');

$v->lanca(InvalidArgumentException::class,
    static fn () => nota(100, -1.0),
    'classificacao negativa e recusada');

$v->lanca(InvalidArgumentException::class,
    static fn () => nota(0, 15.0),
    'peso zero e recusado');

$v->lanca(InvalidArgumentException::class,
    static fn () => nota(100, null, false),
    'nota sem valor e sem marca de falta e recusada');

$v->igual(20.0, nota(100, 20.0)->valorEfectivo(),
    'peso 100 e valor 20 sao aceites (limite superior)');

$v->igual(0.0, nota(100, null, true)->valorEfectivo(),
    'falta com valor nulo e aceite e vale 0,0');

// ------------------------------------------------- ADR-004: fabrica de regulamentos
$v->grupo('FabricaRegulamento (ADR-004) - resolucao por codigo');

$v->verdadeiro((new FabricaRegulamento())->porCodigo('PADRAO_ESCN') instanceof \App\Dominio\Regras\Regulamento\RegulamentoAvaliacao,
    'codigo registado resolve para uma implementacao concreta');

$v->lanca(InvalidArgumentException::class,
    static fn () => (new FabricaRegulamento())->porCodigo('INEXISTENTE'),
    'codigo desconhecido lanca excepcao, nao devolve null');

$v->igual(['PADRAO_ESCN', 'SEM_EXAME'], (new FabricaRegulamento())->disponiveis(),
    'disponiveis() lista os regulamentos registados');

$v->verdadeiro(
    (new FabricaRegulamento())->porCodigo('PADRAO_ESCN')->codigo() === 'PADRAO_ESCN',
    'o codigo devolvido pela instancia corresponde ao pedido'
);

// ---------------------------------------------- ResultadoValidacao (estado vazio)
$v->grupo('ResultadoValidacao - estado vazio (nenhuma violacao acrescentada)');

$vazio = new \App\Dominio\Valores\ResultadoValidacao();

$v->verdadeiro($vazio->valido(),
    'sem violacoes acrescentadas, valido() e verdadeiro');
$v->igual([], $vazio->codigos(),
    'sem violacoes acrescentadas, codigos() e uma lista vazia');
$v->igual(null, $vazio->primeira(),
    'sem violacoes acrescentadas, primeira() e null');
$v->igual([], $vazio->paraResposta(),
    'sem violacoes acrescentadas, paraResposta() e uma lista vazia');

exit($v->resumo());

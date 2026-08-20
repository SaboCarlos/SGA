<?php

declare(strict_types=1);

use App\Dominio\Enums\SituacaoFinal;
use App\Dominio\Enums\SituacaoFrequencia;
use App\Dominio\Enums\TipoAvaliacao;
use App\Dominio\Excepcoes\EstruturaAvaliacaoInvalida;
use App\Dominio\Regras\Regulamento\FabricaRegulamento;
use App\Dominio\Valores\NotaAvaliacao;

/**
 * Testes unitarios das regras RN-06 a RN-12.
 *
 * Nao tocam a base de dados: o regulamento opera sobre objectos de valor puros.
 * Cobertura exigida neste modulo: >= 90% (RNF-24).
 */
beforeEach(function () {
    $this->reg = (new FabricaRegulamento)->porCodigo('PADRAO_ESCN');
});

function nota(float $peso, ?float $valor, bool $faltou = false): NotaAvaliacao
{
    return new NotaAvaliacao(TipoAvaliacao::TESTE, $peso, $valor, $faltou);
}

// ------------------------------------------------------------------------- RN-07
describe('RN-07 nota de frequencia', function () {
    it('calcula a media ponderada com uma casa decimal', function () {
        expect($this->reg->calcularFrequencia([nota(50, 14.0), nota(50, 16.0)]))->toBe(15.0);
        expect($this->reg->calcularFrequencia([nota(30, 12.0), nota(30, 13.0), nota(40, 15.0)]))->toBe(13.5);
    });

    it('conta a falta como zero', function () {
        expect($this->reg->calcularFrequencia([nota(50, 18.0), nota(50, null, true)]))->toBe(9.0);
    });

    it('arredonda meio para cima', function () {
        expect($this->reg->calcularFrequencia([nota(50, 13.35), nota(50, 13.35)]))->toBe(13.4);
    });

    it('ignora a avaliacao de tipo exame', function () {
        $notas = [nota(100, 15.0), new NotaAvaliacao(TipoAvaliacao::EXAME, 100, 8.0)];

        expect($this->reg->calcularFrequencia($notas))->toBe(15.0);
    });
});

// ------------------------------------------------------------------------- RN-06
describe('RN-06 soma dos pesos', function () {
    it('recusa estrutura cuja soma dos pesos nao seja 100%', function (float $p1, float $p2) {
        $this->reg->calcularFrequencia([nota($p1, 15.0), nota($p2, 15.0)]);
    })->with([[40, 40], [60, 60], [10, 20]])->throws(EstruturaAvaliacaoInvalida::class);

    it('aceita pesos fraccionarios que somem 100%', function () {
        $notas = [nota(33.33, 15.0), nota(33.33, 15.0), nota(33.34, 15.0)];

        expect($this->reg->calcularFrequencia($notas))->toBe(15.0);
    });
});

// ------------------------------------------------------------------ RN-08 / RN-09
describe('RN-08 e RN-09 situacao de frequencia', function () {
    it('determina a situacao nos casos de fronteira', function (float $nf, SituacaoFrequencia $esperada) {
        expect($this->reg->determinarSituacaoFrequencia($nf))->toBe($esperada);
    })->with([
        [9.9,   SituacaoFrequencia::REPROVADO_FREQUENCIA],
        [9.95,  SituacaoFrequencia::REPROVADO_FREQUENCIA],
        [10.0,  SituacaoFrequencia::ADMITIDO_EXAME],
        [13.9,  SituacaoFrequencia::ADMITIDO_EXAME],
        [13.94, SituacaoFrequencia::ADMITIDO_EXAME],
        [14.0,  SituacaoFrequencia::DISPENSADO],
        [20.0,  SituacaoFrequencia::DISPENSADO],
    ]);

    it('concede dispensa quando 13,95 arredonda para 14,0', function () {
        $frequencia = $this->reg->calcularFrequencia([nota(50, 13.95), nota(50, 13.95)]);

        expect($frequencia)->toBe(14.0)
            ->and($this->reg->determinarSituacaoFrequencia($frequencia))
            ->toBe(SituacaoFrequencia::DISPENSADO);
    });
});

// ------------------------------------------------------------------------- RN-10
describe('RN-10 classificacao final', function () {
    it('usa a frequencia quando o estudante dispensa', function () {
        expect($this->reg->calcularClassificacaoFinal(16.0))->toBe(16.0);
    });

    it('devolve null enquanto o exame nao for realizado', function () {
        expect($this->reg->calcularClassificacaoFinal(12.0))->toBeNull();
    });

    it('combina frequencia e exame', function () {
        expect($this->reg->calcularClassificacaoFinal(12.0, 14.0))->toBe(13.0);
    });

    it('aprova a partir de 10,0 valores', function (?float $final, SituacaoFinal $esperada) {
        expect($this->reg->determinarSituacaoFinal($final))->toBe($esperada);
    })->with([
        [10.0, SituacaoFinal::APROVADO],
        [9.9,  SituacaoFinal::REPROVADO],
        [null, SituacaoFinal::EM_CURSO],
    ]);
});

// ------------------------------------------------------------------------- RN-12
describe('RN-12 media do curso', function () {
    it('pondera pelos creditos', function () {
        $aprovadas = [
            ['classificacao' => 18.0, 'creditos' => 6],
            ['classificacao' => 12.0, 'creditos' => 3],
        ];

        expect($this->reg->calcularMediaCurso($aprovadas))->toBe(16.0);
    });

    it('devolve null sem disciplinas aprovadas', function () {
        expect($this->reg->calcularMediaCurso([]))->toBeNull();
    });
});

// -------------------------------------------------------------- Strategy (ADR-004)
it('permite um regulamento alternativo sem alterar o servico', function () {
    $semExame = (new FabricaRegulamento)->porCodigo('SEM_EXAME');

    expect($semExame->determinarSituacaoFrequencia(10.0))->toBe(SituacaoFrequencia::DISPENSADO)
        ->and($semExame->calcularClassificacaoFinal(12.0))->toBe(12.0);
});

it('recusa um codigo de regulamento desconhecido', function () {
    (new FabricaRegulamento)->porCodigo('INEXISTENTE');
})->throws(InvalidArgumentException::class);

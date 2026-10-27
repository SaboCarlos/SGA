<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $pauta['numero'] }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 0; }
        .subtitulo { color: #64748b; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .resumo { margin-bottom: 12px; }
        .resumo span { margin-right: 24px; }
        .estado { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #e2e8f0; }
        .num { text-align: right; }
        .rascunho { color: #94a3b8; font-style: italic; }
        .nota-rodape { margin-top: 10px; color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Escola Superior de Ciências Náuticas — Sistema de Gestão Académica</h1>
    <p class="subtitulo">
        Pauta {{ $pauta['numero'] }} — versão {{ $pauta['versao'] }} —
        {{ $pauta['turma']['disciplina'] }}, Turma {{ $pauta['turma']['designacao'] }}
        — <span class="estado">{{ $pauta['estado'] }}</span>
    </p>

    <p class="resumo">
        <span><strong>Total de inscritos:</strong> {{ $pauta['total_inscritos'] }}</span>
        <span><strong>Aprovados:</strong> {{ $pauta['total_aprovados'] }}</span>
        <span><strong>Taxa de aprovação:</strong> {{ $pauta['taxa_aprovacao'] }}%</span>
    </p>

    <table>
        <thead>
            <tr>
                <th>Nº Estudante</th>
                <th>Nome</th>
                @foreach ($pauta['avaliacoes'] as $avaliacao)
                    <th class="num">{{ $avaliacao['designacao'] }} ({{ rtrim(rtrim(number_format($avaliacao['peso'], 2, ',', ''), '0'), ',') }}%)</th>
                @endforeach
                <th class="num">Frequência</th>
                <th class="num">Final</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pauta['linhas'] as $linha)
                <tr>
                    <td>{{ $linha['numero_estudante'] }}</td>
                    <td>{{ $linha['nome_completo'] }}</td>
                    @foreach ($pauta['avaliacoes'] as $avaliacao)
                        @php($nota = $linha['notas'][$avaliacao['id']] ?? null)
                        <td class="num {{ $nota && ! $nota['publicada'] ? 'rascunho' : '' }}">
                            @if (! $nota || ($nota['valor'] === null && ! $nota['faltou']))
                                —
                            @elseif ($nota['faltou'])
                                F
                            @else
                                {{ $nota['valor'] }}
                            @endif
                        </td>
                    @endforeach
                    <td class="num">{{ $linha['nota_frequencia'] ?? '—' }}</td>
                    <td class="num">{{ $linha['classificacao_final'] ?? '—' }}</td>
                    <td>{{ $linha['situacao_final'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="nota-rodape">F = faltou. Notas a cinzento e em itálico ainda não foram publicadas.</p>
</body>
</html>

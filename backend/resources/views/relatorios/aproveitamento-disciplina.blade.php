<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Aproveitamento por disciplina</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Escola Superior de Ciências Náuticas — Aproveitamento por disciplina</h1>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Disciplina</th>
                <th>Ano lectivo</th>
                <th>Sem.</th>
                <th>Inscritos</th>
                <th>Aprovados</th>
                <th>Reprovados</th>
                <th>Taxa</th>
                <th>Média</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($linhas as $linha)
                <tr>
                    <td>{{ $linha['codigo'] }}</td>
                    <td>{{ $linha['disciplina'] }}</td>
                    <td>{{ $linha['ano_lectivo'] }}</td>
                    <td>{{ $linha['semestre'] }}</td>
                    <td>{{ $linha['inscritos'] }}</td>
                    <td>{{ $linha['aprovados'] }}</td>
                    <td>{{ $linha['reprovados'] }}</td>
                    <td>{{ $linha['taxa_aprovacao'] }}%</td>
                    <td>{{ $linha['media_disciplina'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

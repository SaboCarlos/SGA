<?php

// Em desenvolvimento o frontend e servido pelo mesmo Nginx da API, por isso
// o CORS nunca entrava em jogo e '*' bastava. Em producao o frontend vive no
// Vercel, noutra origem: o browser passa a exigir uma autorizacao explicita,
// e '*' deixa de ser aceitavel (RNF-09, OWASP A05).
//
// As origens vem de CORS_ORIGINS (lista separada por virgulas) em vez de
// ficarem fixas aqui, para o mesmo codigo servir local, pre-visualizacao e
// producao.

$origens = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ORIGINS', 'http://localhost:5173,http://localhost:8000')),
)));

$padroes = [];

// Cada branch no Vercel recebe um dominio proprio e imprevisivel
// (sga-git-abc123-equipa.vercel.app). Sem este padrao, so o dominio de
// producao conseguiria falar com a API.
if (filter_var(env('CORS_PERMITIR_PREVIEWS_VERCEL', false), FILTER_VALIDATE_BOOL)) {
    $padroes[] = '#^https://[a-z0-9-]+\.vercel\.app$#';
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origens,

    'allowed_origins_patterns' => $padroes,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    // A autenticacao e por token Bearer em cabecalho, nao por cookie de
    // sessao: nao ha credenciais para o browser enviar entre origens.
    'supports_credentials' => false,

];

<?php

// Ficheiro parcial: as restantes chaves do Sanctum continuam a vir dos
// valores por omissao do pacote (mergeConfigFrom).
//
// SANCTUM_EXPIRACAO era lido com env() dentro do AuthController. Isso
// funciona em desenvolvimento, mas em producao o arranque executa
// "php artisan config:cache" -- e a partir dai o .env deixa de ser
// carregado, pelo que env() devolveria sempre o valor por omissao e a
// expiracao configurada seria silenciosamente ignorada (RNF-11).
// Lido aqui, o valor fica dentro da config em cache.

return [

    // Tempo de vida do token de acesso, em minutos.
    'expiracao_minutos' => (int) env('SANCTUM_EXPIRACAO', 60),

];

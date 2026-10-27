<?php

use Illuminate\Support\Facades\Route;

// Route::view em vez de uma closure: closures nao sao serializaveis, e
// "php artisan route:cache" -- que o arranque em producao executa --
// falharia com "Unable to prepare route [/] for serialization".
Route::view('/', 'welcome');

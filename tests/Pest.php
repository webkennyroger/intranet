<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('Browser');

// Falha rápido num seletor errado (ex. ambíguo/inexistente) em vez de
// esperar o padrão (bem mais longo) — útil enquanto escrevendo/depurando
// testes de navegador; o custo (achar um elemento genuinamente lento pra
// aparecer) é raro nesta aplicação.
pest()->browser()->timeout(4000);

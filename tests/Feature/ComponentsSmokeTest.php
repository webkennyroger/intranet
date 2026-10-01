<?php

use Illuminate\Support\Facades\Blade;

test('the button component renders its slot', function () {
    $html = Blade::render('<x-ui.button>Salvar</x-ui.button>');

    expect($html)->toContain('Salvar');
});

test('the input component renders a label', function () {
    $html = Blade::render('<x-ui.input label="Nome" name="name" />');

    expect($html)->toContain('Nome');
});

test('the modal component renders its named slot content', function () {
    $initialBufferLevel = ob_get_level();
    $html = Blade::render('<x-ui.modal name="demo"><x-slot:header>Título</x-slot:header>Corpo</x-ui.modal>');

    // Clean up any unclosed output buffers
    while (ob_get_level() > $initialBufferLevel) {
        ob_end_clean();
    }

    expect($html)->toContain('Título')->toContain('Corpo');
});

test('the data-table component renders its header and body slots', function () {
    $html = Blade::render('<x-ui.data-table><x-slot:header><th>Nome</th></x-slot:header><tr><td>Ana</td></tr></x-ui.data-table>');

    expect($html)->toContain('Nome')->toContain('Ana');
});

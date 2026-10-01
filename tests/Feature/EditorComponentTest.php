<?php

use Livewire\Livewire;

test('the tiptap editor component renders its mount point and hidden input', function () {
    $html = Livewire::test(\App\Livewire\TestEditorComponent::class)->html();

    expect($html)->toContain('x-ref="editor"')
        ->and($html)->toContain('name="body"');
});

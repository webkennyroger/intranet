<?php

use Livewire\Livewire;

test('the tiptap editor component renders its mount point and hidden input', function () {
    $html = Livewire::test(new class extends \Livewire\Component {
        public $content = '<p>Olá</p>';

        public function render()
        {
            return <<<'HTML'
            <div><x-editor.tiptap name="body" :value="$content" /></div>
            HTML;
        }
    })->html();

    expect($html)->toContain('x-ref="editor"')
        ->and($html)->toContain('name="body"');
});

<?php

namespace App\Livewire;

use Livewire\Component;

class TestEditorComponent extends Component
{
    public $content = '<p>Olá</p>';

    public function render()
    {
        return view('livewire.test-editor-component', [
            'content' => $this->content,
        ]);
    }
}

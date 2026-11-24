<?php

namespace App\Livewire\Admin\Appearance;

use App\Models\SiteSetting;
use Livewire\Component;

/**
 * Tela admin "Aparência": deixa o Super Administrador trocar a cor de
 * destaque do sistema (botões, links, item ativo do menu) usando uma paleta
 * pré-definida ou um hex customizado, e salva isso em SiteSetting. A
 * pré-visualização ao vivo roda só no navegador (Alpine); nada é gravado
 * até clicar em salvar.
 */
class AppearanceForm extends Component
{
    public string $primaryColor = '#00C950';

    /**
     * Paleta oficial do Tailwind CSS v4 (https://uicolors.app/tailwind-colors),
     * na mesma ordem do site, sempre no tom 500 — Preto puro fica na ponta
     * por conveniência (não existe como tom 500). Branco não entra: botões,
     * links e realces brancos ficariam invisíveis em cima de cards brancos.
     *
     * @var array<string, string>
     */
    public array $palette = [
        'Red' => '#FB2C36',
        'Orange' => '#FF6900',
        'Amber' => '#FE9A00',
        'Yellow' => '#F0B100',
        'Lime' => '#7CCF00',
        'Green' => '#00C950',
        'Emerald' => '#00BC7D',
        'Teal' => '#00BBA7',
        'Cyan' => '#00B8DB',
        'Sky' => '#00A6F4',
        'Blue' => '#2B7FFF',
        'Indigo' => '#615FFF',
        'Violet' => '#8E51FF',
        'Purple' => '#AD46FF',
        'Fuchsia' => '#E12AFB',
        'Pink' => '#F6339A',
        'Rose' => '#FF2056',
        'Slate' => '#62748E',
        'Gray' => '#6A7282',
        'Zinc' => '#71717B',
        'Neutral' => '#737373',
        'Stone' => '#79716B',
        'Taupe' => '#7C6D67',
        'Mauve' => '#79697B',
        'Mist' => '#67787C',
        'Olive' => '#7C7C67',
        'Preto' => '#000000',
    ];

    public function mount(): void
    {
        $this->primaryColor = SiteSetting::current()->primary_color;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('appearance.update'), 403);

        $this->validate([
            'primaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'primaryColor.required' => 'Escolha uma cor de destaque.',
            'primaryColor.regex' => 'Use uma cor no formato #RRGGBB, por exemplo #00C950.',
        ]);

        SiteSetting::current()->update(['primary_color' => strtoupper($this->primaryColor)]);

        session()->flash('toast', ['title' => 'Aparência', 'message' => 'Cor de destaque salva com sucesso.', 'variant' => 'success']);

        // A real browser reload (not Livewire's redirect-to-self, which some
        // browsers can short-circuit without a real round-trip) so the <head>
        // style block, which only renders on initial load, always picks up
        // the new color immediately instead of staying stale.
        $this->dispatch('appearance-saved-reload');
    }

    public function render()
    {
        return view('livewire.admin.appearance.appearance-form')
            ->layout('layouts.admin', ['title' => 'Aparência']);
    }
}

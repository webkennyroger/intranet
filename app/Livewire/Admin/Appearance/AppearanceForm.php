<?php

namespace App\Livewire\Admin\Appearance;

use App\Models\SiteSetting;
use Livewire\Component;

/**
 * Tela admin "Aparência": deixa o Super Administrador trocar as cores do
 * tema (primária, cabeçalho, barra lateral) usando uma paleta pré-definida
 * ou um hex customizado, e salva isso em SiteSetting.
 */
class AppearanceForm extends Component
{
    public string $primaryColor = '#00C950';

    public string $navHeaderColor = '#FFFFFF';

    public string $headerColor = '#FFFFFF';

    public string $sidebarColor = '#FFFFFF';

    public string $textColor = '#0F172A';

    /**
     * Paleta oficial do Tailwind CSS v4 (https://uicolors.app/tailwind-colors),
     * na mesma ordem do site, sempre no tom 500 — Branco e Preto puros
     * ficam nas pontas por conveniência (não existem como tom 500).
     *
     * @var array<string, string>
     */
    public array $palette = [
        'Branco' => '#FFFFFF',
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

    /**
     * @var array<string, string>
     */
    private array $fieldToColumn = [
        'primaryColor' => 'primary_color',
        'navHeaderColor' => 'nav_header_color',
        'headerColor' => 'header_color',
        'sidebarColor' => 'sidebar_color',
        'textColor' => 'text_color',
    ];

    public function mount(): void
    {
        $setting = SiteSetting::current();

        $this->primaryColor = $setting->primary_color;
        $this->navHeaderColor = $setting->nav_header_color;
        $this->headerColor = $setting->header_color;
        $this->sidebarColor = $setting->sidebar_color;
        $this->textColor = $setting->text_color;
    }

    public function pick(string $field, string $color): void
    {
        abort_unless(array_key_exists($field, $this->fieldToColumn), 404);

        $this->{$field} = $color;
        $this->applyColors();
    }

    public function save(): void
    {
        $this->validate([
            'primaryColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'navHeaderColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'headerColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'sidebarColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'textColor' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $this->applyColors();
    }

    private function applyColors(): void
    {
        abort_unless(auth()->user()->can('appearance.update'), 403);

        SiteSetting::current()->update([
            'primary_color' => $this->primaryColor,
            'nav_header_color' => $this->navHeaderColor,
            'header_color' => $this->headerColor,
            'sidebar_color' => $this->sidebarColor,
            'text_color' => $this->textColor,
        ]);

        session()->flash('toast', ['title' => 'Aparência', 'message' => 'Cores salvas com sucesso.', 'variant' => 'success']);

        // A real browser reload (not Livewire's redirect-to-self, which some
        // browsers can short-circuit without a real round-trip) so the <head>
        // style block, which only renders on initial load, always picks up
        // the new colors immediately instead of staying stale.
        $this->dispatch('appearance-saved-reload');
    }

    public function render()
    {
        return view('livewire.admin.appearance.appearance-form')
            ->layout('layouts.admin', ['title' => 'Aparência']);
    }
}

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
    public string $primaryColor = '#0C4A34';

    public string $navHeaderColor = '#FFFFFF';

    public string $headerColor = '#FFFFFF';

    public string $sidebarColor = '#FFFFFF';

    /**
     * @var array<string, string>
     */
    public array $palette = [
        'Verde (padrão)' => '#0C4A34',
        'Jade' => '#22B573',
        'Rosa' => '#EC4899',
        'Azul' => '#2563EB',
        'Amarelo' => '#F59E0B',
        'Laranja' => '#F97316',
        'Vermelho' => '#DC2626',
        'Roxo' => '#7C3AED',
    ];

    /**
     * @var array<string, string>
     */
    private array $fieldToColumn = [
        'primaryColor' => 'primary_color',
        'navHeaderColor' => 'nav_header_color',
        'headerColor' => 'header_color',
        'sidebarColor' => 'sidebar_color',
    ];

    public function mount(): void
    {
        $setting = SiteSetting::current();

        $this->primaryColor = $setting->primary_color;
        $this->navHeaderColor = $setting->nav_header_color;
        $this->headerColor = $setting->header_color;
        $this->sidebarColor = $setting->sidebar_color;
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

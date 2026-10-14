<?php

namespace App\Livewire\Admin\Council;

use App\Models\CouncilMember;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário admin de criação/edição dos membros exibidos na página do
 * Conselho Superior (nome, cargo, foto e se está ativo).
 */
class CouncilMemberForm extends Component
{
    use WithFileUploads;

    public ?CouncilMember $member = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $role = '';

    #[Validate('nullable|image|max:4096')]
    public $photo = null;

    public bool $isActive = true;

    public function mount(?CouncilMember $member = null): void
    {
        if ($member) {
            $this->member = $member;
            $this->name = $member->name;
            $this->role = $member->role;
            $this->isActive = $member->is_active;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->member ? 'council.update' : 'council.create'), 403);

        $this->validate();

        $data = [
            'name' => $this->name,
            'role' => $this->role,
            'is_active' => $this->isActive,
        ];

        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('council', 'public');
        }

        if ($this->member) {
            $changes = ActivityLogger::diff($this->member, $data);
            $this->member->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->member, $changes);
            }
            $this->dispatch('toast', title: 'Conselho Superior', message: 'Membro atualizado com sucesso.', variant: 'success');
        } else {
            $data['order'] = (CouncilMember::max('order') ?? 0) + 1;
            $member = CouncilMember::create($data);
            ActivityLogger::created($member);
            $this->dispatch('toast', title: 'Conselho Superior', message: 'Membro criado com sucesso.', variant: 'success');
        }

        $this->dispatch('council-member-saved');
        $this->reset(['name', 'role', 'photo']);
        $this->isActive = true;
    }

    public function render()
    {
        return view('livewire.admin.council.council-member-form');
    }
}

{{--
    Formulário de processo SEI do Conselho Superior (admin), aberto em
    modal. O link externo é opcional — sem ele, o número do processo
    aparece na listagem sem abrir nada ao clicar.
--}}
<form wire:submit="save" class="space-y-5">
    <!-- Identificação do processo -->
    <div class="space-y-4">
        <x-ui.input wire:model="seiNumber" label="Número do processo SEI" placeholder="Ex: 2025.0.000019186-8" />
        <x-ui.input wire:model="subject" label="Assunto" />
    </div>

    <!-- Pessoas envolvidas -->
    <div class="pt-4" style="border-top: 1px solid var(--border-color);">
        <p class="text-[11px] font-bold uppercase tracking-wide mb-3" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">
            <i class="fa-solid fa-user-group mr-1.5"></i>Pessoas envolvidas
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.input wire:model="assignedTo" label="Relator/Atribuído" />
            <x-ui.input wire:model="interestedParty" label="Interessado" />
        </div>
    </div>

    <!-- Link externo -->
    <div class="pt-4" style="border-top: 1px solid var(--border-color);">
        <p class="text-[11px] font-bold uppercase tracking-wide mb-3" style="color: var(--admin-primary, var(--brand-primary, #0C4A34));">
            <i class="fa-solid fa-link mr-1.5"></i>Link externo
        </p>
        <x-ui.input wire:model="externalUrl" type="url" label="Link do SEI (opcional)" placeholder="https://..." hint="Se preenchido, o número do processo abre esse link numa nova aba." />
    </div>

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>

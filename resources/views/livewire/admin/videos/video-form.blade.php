{{--
    Formulário de vídeo (admin), aberto em modal.
    Cadastra/edita título, descrição e URL do vídeo (YouTube, Vimeo etc.).
    Vídeo novo entra sempre publicado — despublicar é uma ação separada na
    tabela, não um campo deste formulário.
--}}
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />

    <x-ui.textarea wire:model="description" label="Descrição" rows="3" />

    <x-ui.input wire:model="url" type="url" label="URL do vídeo" hint="Link do YouTube, Vimeo ou outro serviço." />

    <x-ui.button type="submit" class="w-full justify-center">
        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Salvar
    </x-ui.button>
</form>

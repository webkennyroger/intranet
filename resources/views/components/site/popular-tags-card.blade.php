{{--
    Card "Explore por tags": lista as tags mais usadas entre as matérias
    publicadas. Usado nas páginas onde faz sentido como atalho de
    navegação dentro de uma barra lateral já existente (Notícias e a
    leitura de uma matéria) — não nas páginas de listagem em largura
    total (Mural, SOS) nem na própria página de busca por tag.
--}}
@props(['tags'])

@if ($tags->isNotEmpty())
    <div class="ui-card p-4 space-y-4">
        <h3 class="font-black text-sm text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2 pb-3">
            <i class="fa-solid fa-tags text-(--brand-primary-dark)"></i> Explore por tags
        </h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                <a href="{{ route('mural.tag', $tag) }}" class="rounded-lg border px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide transition hover:border-(--brand-primary) hover:text-(--brand-primary)" style="border-color: var(--border-color); color: var(--text-secondary);">{{ $tag }}</a>
            @endforeach
        </div>
    </div>
@endif

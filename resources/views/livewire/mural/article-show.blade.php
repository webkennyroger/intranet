{{--
    Tela de leitura de uma matéria do Mural.
    Mostra a capa, o conteúdo completo da notícia/artigo, a data de
    publicação e permite curtir a matéria.
--}}
<div class="mx-auto space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="newspaper" title="Mural" subtitle="Leitura da matéria selecionada." :back="route('mural.index')" />

    <!-- Conteúdo da matéria -->
    <div class="ui-card overflow-hidden">
        @if ($article->featuredImageUrl())
            <img src="{{ $article->featuredImageUrl() }}" class="h-52 w-full object-cover" alt="{{ $article->title }}" />
        @else
            <div class="h-52 flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
                <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white"></i>
            </div>
        @endif
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between text-xs text-slate-400 dark:text-(--text-muted)">
                <button wire:click="toggleLike" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                    <span>{{ $article->likedByUsers()->count() }}</span>
                </button>
                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
            </div>
            <!-- Categorias e Autor -->
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-(--text-secondary)">
                @foreach ($article->categories as $category)
                    <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded" style="background-color: {{ $category->lightColor() }}; color: {{ $category->color }};">{{ $category->name }}</span>
                    <span>·</span>
                @endforeach
                <span>Por {{ $article->author->name }}</span>
            </div>

            <h2 class="text-2xl font-black text-slate-800 dark:text-(--text-primary)">{{ $article->title }}</h2>
            <div class="prose prose-sm max-w-none">{!! $article->body !!}</div>

            <!-- Campos Personalizados -->
            @if (! empty($article->custom_fields))
                <div class="flex flex-col items-start gap-4 pt-2">
                    @foreach ($article->custom_fields as $field)
                        @php
                            $isTextLink = $field['type'] === 'texto' && (str_starts_with($field['value'], 'http://') || str_starts_with($field['value'], 'https://'));
                        @endphp

                        @if ($isTextLink)
                            <a href="{{ $field['value'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold uppercase bg-(--brand-primary)/10 text-(--brand-primary) hover:bg-(--brand-primary)/20 transition">
                                <i class="fa-solid fa-{{ \App\Models\Article::fileIconForPath($field['value']) }}"></i>
                                {{ $field['label'] }}
                            </a>
                        @elseif ($field['type'] === 'texto')
                            <span class="text-sm text-slate-600 dark:text-(--text-secondary)"><strong class="text-slate-800 dark:text-(--text-primary)">{{ $field['label'] }}:</strong> {{ $field['value'] }}</span>
                        @elseif ($field['type'] === 'link')
                            <a href="{{ $field['value'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold uppercase text-white hover:brightness-110 transition" style="background-color: var(--brand-primary, #0C4A34);">
                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> {{ $field['label'] }}
                            </a>
                        @elseif ($field['type'] === 'arquivo')
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($field['value']) }}" target="_blank" class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold uppercase text-white hover:brightness-110 transition" style="background-color: var(--brand-primary, #0C4A34);">
                                <i class="fa-solid fa-download text-xs"></i> {{ $field['label'] }}
                            </a>
                        @elseif ($field['type'] === 'imagem')
                            <div>
                                <p class="text-xs font-semibold text-slate-500 dark:text-(--text-secondary) mb-1">{{ $field['label'] }}</p>
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($field['value']) }}" class="max-h-64 rounded-xl" alt="{{ $field['label'] }}" />
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

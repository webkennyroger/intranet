{{--
    Tela de leitura de uma matéria do Mural: capa em tamanho completo,
    conteúdo (com imagens clicáveis, abertas no lightbox), posts
    relacionados e tags populares (barra lateral) e os comentários
    (um nível de resposta).
--}}
<div class="space-y-4">
    <!-- Cabeçalho da página -->
    <x-site.page-header icon="newspaper" title="Mural" subtitle="Leitura da matéria selecionada." :back="route('mural.index')" />

    @php
        $hasSidebar = $relatedArticles->isNotEmpty() || $popularTags->isNotEmpty();
    @endphp
    <div class="grid grid-cols-1 {{ $hasSidebar ? 'lg:grid-cols-3' : '' }} gap-4">
        <!-- Coluna principal: conteúdo e comentários -->
        <div class="{{ $hasSidebar ? 'lg:col-span-2' : '' }} space-y-4">
            <!-- Conteúdo da matéria -->
            <div class="ui-card p-6 sm:p-8 space-y-5">
                @php
                    $primaryCategory = $article->categories->first();
                @endphp
                @if ($primaryCategory)
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wide px-3 py-1 rounded"
                        style="background-color: {{ $primaryCategory->color }}; color: {{ $primaryCategory->readableTextColor() }};">
                        {{ $primaryCategory->name }}
                    </span>
                @endif

                <h1 class="text-2xl sm:text-3xl font-black leading-tight text-slate-800 dark:text-(--text-primary)">{{ $article->title }}</h1>

                <!-- Meta: autor, data, visualizações, comentários, curtir -->
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-(--text-secondary)">
                    <div class="flex items-center gap-2">
                        <x-ui.avatar :name="$article->author->name" size="xs" />
                        <span>Por <strong class="text-slate-700 dark:text-(--text-primary)">{{ $article->author->name }}</strong></span>
                    </div>
                    <span class="inline-flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i> {{ $article->published_at?->locale('pt_BR')->isoFormat('D [de] MMM [de] YYYY') }}</span>

                    <div class="ml-auto flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold">
                            <i class="fa-regular fa-comment"></i> {{ $comments->count() + $comments->sum(fn ($comment) => $comment->replies->count()) }}
                        </span>
                        <x-ui.view-count :count="$article->views_count" class="rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold" />
                        <button wire:click="toggleLike" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-(--border-color) bg-slate-50 dark:bg-(--bg-hover) px-3 py-1.5 text-xs font-bold transition hover:bg-slate-100 dark:hover:bg-(--bg-elevated) {{ $article->isLikedBy(auth()->user()) ? 'text-(--brand-primary)' : 'text-slate-500 dark:text-(--text-secondary)' }}">
                            <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-heart"></i>
                            <span>{{ $article->likedByUsers()->count() }}</span>
                        </button>
                    </div>
                </div>

                <!-- Imagem de capa, em tamanho completo (sem corte); clica pra abrir no modal -->
                @if ($article->featuredImageUrl())
                    <img
                        src="{{ $article->featuredImageUrl() }}"
                        class="w-full h-auto rounded-xl cursor-pointer transition hover:opacity-90"
                        alt="{{ $article->title }}"
                        @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: [{ url: $event.target.currentSrc || $event.target.src, name: $event.target.alt, type: 'image' }], index: 0 } }))"
                    />
                @else
                    <div class="h-52 w-full rounded-xl flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
                        <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white"></i>
                    </div>
                @endif

                <div
                    class="prose prose-sm max-w-none dark:prose-invert [&_img]:cursor-pointer [&_img]:transition [&_img]:hover:opacity-90 after:block after:clear-both after:content-['']"
                    @click="if ($event.target.tagName === 'IMG') { window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: [{ url: $event.target.currentSrc || $event.target.src, name: $event.target.alt, type: 'image' }], index: 0 } })) }"
                >{!! $article->body !!}</div>

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
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($field['value']) }}"
                                        class="max-h-64 rounded-xl cursor-pointer transition hover:opacity-90"
                                        alt="{{ $field['label'] }}"
                                        @click="window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { items: [{ url: $event.target.currentSrc || $event.target.src, name: $event.target.alt, type: 'image' }], index: 0 } }))"
                                    />
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Comentários -->
            <div class="ui-card p-5 sm:p-6 space-y-5">
                <h3 class="text-sm font-bold text-slate-800 dark:text-(--text-primary)">
                    {{ $comments->count() + $comments->sum(fn ($comment) => $comment->replies->count()) }} comentário(s)
                </h3>

                @foreach ($comments as $comment)
                    <div wire:key="comment-{{ $comment->id }}" class="space-y-3">
                        <div class="ui-card rounded-lg! p-4 flex items-start gap-3.5">
                            <x-ui.avatar :name="$comment->author->name" size="md" class="shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-800 dark:text-(--text-primary)">{{ $comment->author->name }}</p>
                                    <span class="text-[11px] text-slate-400 dark:text-(--text-muted) shrink-0">{{ $comment->created_at->locale('pt_BR')->isoFormat('DD/MM/YYYY') }}</span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-(--text-secondary) mt-1.5">{{ $comment->body }}</p>
                                <div class="flex items-center gap-2 mt-3">
                                    <x-ui.button type="button" wire:click="startReply({{ $comment->id }})" size="sm" class="rounded-lg!">
                                        <i class="fa-solid fa-reply mr-1"></i>Responder
                                    </x-ui.button>
                                    @if ($comment->author_id === auth()->id() || auth()->user()->hasRole('Super Administrador'))
                                        <button type="button" @click="confirmAction('Excluir este comentário?', () => $wire.deleteComment({{ $comment->id }}))" class="text-xs font-semibold text-rose-600 hover:underline px-2">
                                            Excluir
                                        </button>
                                    @endif
                                </div>

                                @if ($replyingToId === $comment->id)
                                    <form wire:submit="addReply" class="mt-3 space-y-2">
                                        <textarea wire:model="newReply" rows="2" placeholder="Escreva sua resposta..." class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                                        @error('newReply') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                        <div class="flex items-center gap-2">
                                            <x-ui.button type="submit" size="sm" class="rounded-lg!">Responder</x-ui.button>
                                            <x-ui.button type="button" variant="ghost" size="sm" class="rounded-lg!" wire:click="cancelReply">Cancelar</x-ui.button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- Respostas (um nível), recuadas à direita -->
                        @foreach ($comment->replies as $reply)
                            <div class="ui-card rounded-lg! p-4 flex items-start gap-3 ml-10 sm:ml-14" style="background-color: var(--bg-elevated);" wire:key="reply-{{ $reply->id }}">
                                <x-ui.avatar :name="$reply->author->name" size="sm" class="shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-bold text-slate-800 dark:text-(--text-primary)">{{ $reply->author->name }}</p>
                                        <span class="text-[11px] text-slate-400 dark:text-(--text-muted) shrink-0">{{ $reply->created_at->locale('pt_BR')->isoFormat('DD/MM/YYYY') }}</span>
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-(--text-secondary) mt-1.5">{{ $reply->body }}</p>
                                    @if ($reply->author_id === auth()->id() || auth()->user()->hasRole('Super Administrador'))
                                        <button type="button" @click="confirmAction('Excluir esta resposta?', () => $wire.deleteComment({{ $reply->id }}))" class="text-xs font-semibold text-rose-600 hover:underline mt-2">
                                            Excluir
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <!-- Deixar um comentário -->
            <div class="ui-card p-5 sm:p-6">
                <h4 class="text-sm font-bold text-slate-800 dark:text-(--text-primary) mb-3">Deixe um comentário</h4>
                <form wire:submit="addComment" class="flex items-start gap-3.5">
                    <x-ui.avatar :name="auth()->user()->name" size="md" class="shrink-0" />
                    <div class="flex-1 space-y-2">
                        <textarea wire:model="newComment" rows="3" placeholder="Escreva seu comentário..." class="w-full rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-(--focus-ring)/40" style="background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                        @error('newComment') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        <x-ui.button type="submit">Comentar</x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coluna lateral: posts relacionados e tags -->
        <aside class="space-y-4 self-start">
            @if ($relatedArticles->isNotEmpty())
                <div class="ui-card p-4 space-y-4">
                    <h3 class="font-black text-sm text-slate-800 dark:text-(--text-primary) tracking-tight uppercase section-title-line flex items-center gap-2 pb-2 mb-8">
                        <i class="fa-solid fa-newspaper text-(--brand-primary-dark)"></i> Leia também
                    </h3>

                    <ul class="space-y-4">
                        @foreach ($relatedArticles as $related)
                            @php($relatedCategory = $related->categories->first())
                            <li class="pb-4 border-b border-slate-100 dark:border-(--border-color) last:pb-0 last:border-0">
                                <a href="{{ route('mural.show', $related) }}" class="flex items-start gap-3 group">
                                    <div class="relative h-16 w-20 shrink-0 overflow-hidden rounded-lg">
                                        @if ($related->featuredImageUrl())
                                            <img src="{{ $related->featuredImageUrl() }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $related->title }}" />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center" style="background-color: {{ $related->cover_color }};">
                                                <i class="fa-solid fa-{{ $related->cover_icon }} text-lg text-white/80"></i>
                                            </div>
                                        @endif
                                        @if ($relatedCategory)
                                            <span class="absolute top-1 left-1 text-[8px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded" style="background-color: {{ $relatedCategory->color }}; color: {{ $relatedCategory->readableTextColor() }};">{{ $relatedCategory->name }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1.5 pt-0.5">
                                        <p class="text-sm font-bold text-slate-700 dark:text-(--text-primary) line-clamp-2 leading-snug group-hover:text-(--brand-primary) transition">{{ $related->title }}</p>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-500 dark:text-(--text-muted)">
                                            <span class="inline-flex items-center gap-1">
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full shrink-0" style="background-color: var(--bg-elevated);"><i class="fa-solid fa-user text-[8px]"></i></span>
                                                {{ $related->author?->name ?? '—' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1">
                                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full shrink-0" style="background-color: var(--brand-primary-light, #0C4A341a); color: var(--brand-primary, #0C4A34);"><i class="fa-solid fa-calendar text-[8px]"></i></span>
                                                {{ $related->published_at?->locale('pt_BR')->isoFormat('DD/MM/YYYY') }}
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-site.popular-tags-card :tags="$popularTags" />
        </aside>
    </div>

    <x-ui.lightbox />
</div>

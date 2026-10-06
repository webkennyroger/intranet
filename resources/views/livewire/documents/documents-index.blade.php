{{--
    Tela de Documentos.
    Lista os documentos (PDFs e afins) disponíveis para download, cada
    um com título e descrição. Mostra aviso quando não há documentos.
--}}
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Selo da seção -->
    <div class="flex items-center">
        <div class="text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-file-lines text-xs"></i>
            <span>DOCUMENTOS</span>
        </div>
    </div>

    @if ($documents->isEmpty())
        <!-- Estado vazio -->
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum documento disponível por enquanto.
        </div>
    @else
        <!-- Grade de documentos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($documents as $document)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}" target="_blank" class="flex items-start gap-3 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 transition">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-pdf text-lg text-emerald-800"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800 leading-snug block">{{ $document->title }}</span>
                        @if ($document->description)
                            <span class="text-xs text-slate-500 line-clamp-2 block mt-0.5">{{ $document->description }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

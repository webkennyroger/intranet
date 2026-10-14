{{--
    Lightbox nativo (sem biblioteca de terceiros) pra imagens E vídeos: tela
    cheia, setas de navegação, loop, teclado (←/→/Esc) e swipe no touch.
    Inclua uma vez na página e abra disparando, de qualquer lugar (ex. um
    @click num card de foto/vídeo):

    window.dispatchEvent(new CustomEvent('open-lightbox', {
        detail: { items: [{ url: '...', name: '...', type: 'image' }, ...], index: 0 },
    }))

    "items" é a lista completa (pra setas/loop navegarem entre todas) e
    "index" é a posição inicial (a foto/vídeo em que o usuário clicou).
    "type" por item: "image" (padrão, se omitido) ou "video" — nesse caso
    "url" precisa já ser o link de embed (ex. Video::embedUrl()), não a URL
    original do YouTube/Vimeo.
--}}
<div
    x-data="{
        open: false,
        items: [],
        index: 0,
        touchX: null,
        close() { this.open = false; this.items = []; },
        next() { this.index = (this.index + 1) % this.items.length; },
        prev() { this.index = (this.index - 1 + this.items.length) % this.items.length; },
        get current() { return this.items[this.index] ?? null; },
    }"
    x-on:open-lightbox.window="open = true; items = $event.detail.items; index = $event.detail.index ?? 0;"
    x-effect="document.documentElement.style.overflow = open ? 'hidden' : ''"
    x-show="open"
    x-cloak
    x-transition.opacity
    @keydown.escape.window="close()"
    @keydown.arrow-right.window="next()"
    @keydown.arrow-left.window="prev()"
    @touchstart="touchX = $event.touches[0].clientX"
    @touchend="
        if (touchX !== null) {
            const delta = touchX - $event.changedTouches[0].clientX;
            if (delta > 50) next();
            if (delta < -50) prev();
            touchX = null;
        }
    "
    class="fixed inset-0 z-[60] flex items-center justify-center"
    style="background-color: rgba(0, 0, 0, 0.9);"
    @click="close()"
>
    <button @click.stop="close()" class="absolute right-4 top-4 w-11 h-11 rounded-full flex items-center justify-center text-white hover:bg-white/10 transition" title="Fechar">
        <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    <button x-show="items.length > 1" @click.stop="prev()" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full flex items-center justify-center text-white hover:bg-white/10 transition" title="Anterior">
        <i class="fa-solid fa-chevron-left text-xl"></i>
    </button>

    <template x-if="current?.type === 'video'">
        <div class="w-[90vw] max-w-4xl aspect-video" @click.stop>
            <iframe :src="current?.url" class="w-full h-full rounded-lg shadow-2xl" frameborder="0" allowfullscreen></iframe>
        </div>
    </template>
    <template x-if="current?.type !== 'video'">
        <img :src="current?.url" :alt="current?.name" @click.stop class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg shadow-2xl" />
    </template>

    <button x-show="items.length > 1" @click.stop="next()" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full flex items-center justify-center text-white hover:bg-white/10 transition" title="Próxima">
        <i class="fa-solid fa-chevron-right text-xl"></i>
    </button>

    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm text-center px-4">
        <div x-text="current?.name"></div>
        <div class="text-xs text-white/60 mt-0.5" x-show="items.length > 1" x-text="(index + 1) + ' / ' + items.length"></div>
    </div>
</div>

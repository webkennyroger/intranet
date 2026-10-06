window.dragDrop = (wire, method) => ({
    draggedId: null,
    armedId: null,
    saving: false,
    arm(event) {
        const item = event.target.closest('[data-sort-id]');
        if (!item) return;
        this.armedId = item.dataset.sortId;
        item.draggable = true;
    },
    disarm(event) {
        const item = event.target.closest('[data-sort-id]');
        if (item && this.draggedId === null) item.draggable = false;
        if (this.draggedId === null) this.armedId = null;
    },
    start(event) {
        const item = event.target.closest('[data-sort-id]');
        if (!item || this.armedId !== item.dataset.sortId) {
            event.preventDefault();
            return;
        }
        this.draggedId = item.dataset.sortId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', this.draggedId);
        requestAnimationFrame(() => item.classList.add('opacity-50', 'ring-2', 'ring-emerald-500'));
    },
    end(event) {
        const item = event.target.closest('[data-sort-id]');
        item?.classList.remove('opacity-50', 'ring-2', 'ring-emerald-500');
        if (item) item.draggable = false;
        this.draggedId = null;
        this.armedId = null;
    },
    async drop(event) {
        event.preventDefault();
        event.stopPropagation();
        const target = event.target.closest('[data-sort-id]');
        const source = [...this.$root.querySelectorAll('[data-sort-id]')].find(item => item.dataset.sortId === this.draggedId);
        if (!source || !target || source === target) return;
        const bounds = target.getBoundingClientRect();
        const horizontal = this.$root.scrollWidth > this.$root.clientWidth || Math.abs(source.offsetTop - target.offsetTop) < Math.max(source.offsetHeight, target.offsetHeight);
        const after = horizontal
            ? event.clientX > bounds.left + bounds.width / 2
            : event.clientY > bounds.top + bounds.height / 2;
        target.parentNode.insertBefore(source, after ? target.nextSibling : target);
        const ids = [...this.$root.querySelectorAll('[data-sort-id]')].map(item => Number(item.dataset.sortId));
        this.saving = true;
        try {
            await wire.call(method, ids);
        } finally {
            this.saving = false;
            source.classList.remove('opacity-50', 'ring-2', 'ring-emerald-500');
            source.draggable = false;
            this.draggedId = null;
            this.armedId = null;
        }
    },
});

@props([
    'columns' => [],
])

<div
    x-data="{
        columns: @js($columns),
        dragging: null,
        dragStart(columnId, item) {
            this.dragging = { columnId: columnId, item: item };
        },
        dragEnd() {
            this.dragging = null;
        },
        dropOn(targetColumnId) {
            if (! this.dragging) {
                return;
            }

            const fromColumnId = this.dragging.columnId;
            const item = this.dragging.item;

            if (fromColumnId === targetColumnId) {
                this.dragging = null;
                return;
            }

            const fromColumn = this.columns.find((c) => c.id === fromColumnId);
            const toColumn = this.columns.find((c) => c.id === targetColumnId);

            if (! fromColumn || ! toColumn) {
                this.dragging = null;
                return;
            }

            fromColumn.items = fromColumn.items.filter((i) => i.id !== item.id);
            toColumn.items.push(item);
            this.dragging = null;

            $dispatch('kanban-card-moved', {
                cardId: item.id,
                fromColumn: fromColumnId,
                toColumn: targetColumnId,
            });
        },
    }"
    {{ $attributes->merge(['class' => 'flex items-start gap-4 overflow-x-auto pb-2']) }}
>
    <template x-for="column in columns" :key="column.id">
        <div
            class="flex w-72 flex-shrink-0 flex-col rounded-2xl"
            style="background-color: var(--bg-surface); border: 1px solid var(--border-color);"
            @dragover.prevent
            @drop="dropOn(column.id)"
        >
            <div class="flex items-center justify-between px-4 py-3" style="border-bottom: 1px solid var(--border-color);">
                <span class="text-sm font-semibold" style="color: var(--text-primary);" x-text="column.title"></span>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    style="background-color: var(--bg-elevated); color: var(--text-secondary);"
                    x-text="column.items.length"
                ></span>
            </div>

            <div class="flex min-h-[4rem] flex-1 flex-col gap-2 p-3">
                <template x-for="item in column.items" :key="item.id">
                    <div
                        draggable="true"
                        @dragstart="dragStart(column.id, item)"
                        @dragend="dragEnd()"
                        class="cursor-grab rounded-xl p-3 shadow-sm transition-opacity duration-150 active:cursor-grabbing"
                        :class="dragging && dragging.item.id === item.id ? 'opacity-40' : 'opacity-100'"
                        style="background-color: var(--bg-card); border: 1px solid var(--border-color);"
                    >
                        <p class="text-sm font-medium" style="color: var(--text-primary);" x-text="item.title"></p>
                        <p x-show="item.description" x-cloak class="mt-1 text-xs" style="color: var(--text-muted);" x-text="item.description"></p>
                    </div>
                </template>

                <template x-if="column.items.length === 0">
                    <div
                        class="flex flex-1 items-center justify-center rounded-xl border border-dashed py-6 text-xs"
                        style="border-color: var(--border-subtle); color: var(--text-muted);"
                    >
                        Solte um cartão aqui
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>

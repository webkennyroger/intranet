<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\CouncilMember;
use App\Models\Document;
use App\Models\Event;
use App\Models\ForumTopic;
use App\Models\PhotoAlbum;
use App\Models\ServiceLink;
use App\Models\Training;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela admin "Histórico de Atividades": lista os registros de ActivityLog
 * (quem criou/editou/ativou/excluiu o quê) com busca, filtros e um painel
 * de detalhe por log selecionado.
 */
class ActivityLogIndex extends Component
{
    use WithPagination;

    /**
     * Modelos rastreados pelo log, mapeados para o rótulo exibido no
     * filtro "Tipo" e na listagem.
     */
    public const SUBJECT_TYPES = [
        Article::class => 'Mural',
        Document::class => 'Documento',
        CouncilMember::class => 'Conselho Superior',
        ServiceLink::class => 'Serviço',
        PhotoAlbum::class => 'Álbum de Fotos',
        Video::class => 'Vídeo',
        Event::class => 'Evento',
        Training::class => 'Capacitação',
        User::class => 'Usuário',
        ForumTopic::class => 'Fórum',
    ];

    /**
     * Ações possíveis de um log, usadas tanto nas abas de filtro quanto
     * para contar quantos registros existem em cada categoria.
     */
    public const ACTIONS = [
        '' => 'Todos',
        'created' => 'Criações',
        'updated' => 'Atualizações',
        'activated' => 'Ativações',
        'deactivated' => 'Desativações',
        'deleted' => 'Exclusões',
    ];

    public string $search = '';

    public string $actionFilter = '';

    public string $typeFilter = '';

    public string $periodFilter = '';

    public string $userFilter = '';

    public ?int $selectedLogId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function setActionFilter(string $action): void
    {
        $this->actionFilter = $action;
        $this->resetPage();
    }

    public function openDetail(int $logId): void
    {
        $this->selectedLogId = $logId;
    }

    public function closeDetail(): void
    {
        $this->selectedLogId = null;
    }

    private function filteredQuery(): Builder
    {
        return ActivityLog::query()
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('description', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('subject_type', $this->typeFilter))
            ->when($this->userFilter !== '', fn ($query) => $query->where('user_id', $this->userFilter))
            ->when($this->periodFilter === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($this->periodFilter === 'week', fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($this->periodFilter === 'month', fn ($query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));
    }

    public function render()
    {
        $logs = (clone $this->filteredQuery())
            ->when($this->actionFilter !== '', fn ($query) => $query->where('action', $this->actionFilter))
            ->with(['user', 'subject'])
            ->latest()
            ->paginate(10);

        $tabCounts = collect(self::ACTIONS)->mapWithKeys(fn ($label, $action) => [
            $action => (clone $this->filteredQuery())->when($action !== '', fn ($query) => $query->where('action', $action))->count(),
        ]);

        $userOptions = User::whereIn('id', ActivityLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.activity-log-index', [
            'logs' => $logs,
            'tabCounts' => $tabCounts,
            'stats' => [
                'total' => (clone $this->filteredQuery())->count(),
                'today' => (clone $this->filteredQuery())->whereDate('created_at', today())->count(),
                'thisWeek' => (clone $this->filteredQuery())->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'typeOptions' => self::SUBJECT_TYPES,
            'userOptions' => $userOptions,
            'selectedLog' => $this->selectedLogId ? ActivityLog::with('user')->find($this->selectedLogId) : null,
        ])->layout('layouts.admin', ['title' => 'Histórico de Atividades']);
    }
}

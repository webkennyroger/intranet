<?php

namespace App\Models;

use Database\Factories\BirthdayBannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner/arte comemorativa de um mês do ano (1-12), exibido na página de
 * Aniversariantes e no card de Aniversariantes da Início durante aquele
 * mês — no máximo um banner por mês.
 */
class BirthdayBanner extends Model
{
    /** @use HasFactory<BirthdayBannerFactory> */
    use HasFactory;

    protected $fillable = ['month', 'image_path', 'is_published'];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }

    /**
     * Nome do mês em português (Janeiro...Dezembro), pro admin exibir sem
     * depender da localidade do servidor.
     */
    public function monthName(): string
    {
        return self::monthNames()[$this->month] ?? '';
    }

    /**
     * @return array<int, string>
     */
    public static function monthNames(): array
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
    }
}

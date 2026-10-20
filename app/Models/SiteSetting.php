<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurações visuais da intranet (cores de aparência). Guarda um único
 * registro (id 1, via current()) e deriva, a partir dele, gradientes,
 * tons escuros e cores de texto legíveis usadas em todo o layout.
 */
class SiteSetting extends Model
{
    protected $fillable = [
        'primary_color',
        'nav_header_color',
        'header_color',
        'sidebar_color',
        'text_color',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'primary_color' => '#0C4A34',
            'nav_header_color' => '#FFFFFF',
            'header_color' => '#FFFFFF',
            'sidebar_color' => '#FFFFFF',
            'text_color' => '#0F172A',
        ]);
    }

    public function primaryLightColor(): string
    {
        return $this->primary_color.'1a';
    }

    public function primaryHoverColor(): string
    {
        return static::darken($this->primary_color, 0.8);
    }

    /**
     * Four darkening stops (0%, 35%, 65%, 100%) for a diagonal hero-banner
     * gradient, all derived from the current primary color. Returned as
     * plain hex strings (not CSS color-mix()) so the gradient still shows
     * up even on engines that don't resolve color-mix() inside a
     * linear-gradient() color stop — one unsupported stop invalidates the
     * whole background value, so this avoids that failure mode entirely.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    public function heroGradientStops(): array
    {
        return [
            $this->primary_color,
            static::darken($this->primary_color, 0.92),
            static::darken($this->primary_color, 0.85),
            $this->primaryHoverColor(),
        ];
    }

    /**
     * Superfície neutra no modo escuro (sidebar, header, fundo de card) —
     * cinza-chumbo neutro ("zinc"), igual à referência (uicolors.app):
     * fundo/sidebar/header ficam pretos independente da cor escolhida, e só
     * os ACENTOS (item ativo, botão, ícone — ver darkActiveBgColor() e
     * darkActiveHighlightBgFor()) usam a cor da marca. $hex não entra na
     * conta de propósito — mantido só pra não precisar mudar quem chama.
     */
    public static function darkBgFor(string $hex): string
    {
        return '#18181b';
    }

    /**
     * Background for the sidebar's active menu-item highlight: same hue as
     * the given color, bright enough to stand out as an accent against the
     * neutral dark sidebar from darkBgFor() (matches the hand-picked ESDEP
     * green #16352a — H~159°, S~41%, L~26% — reproduced for any hue).
     */
    public static function darkActiveHighlightBgFor(string $hex): string
    {
        return static::hueMatchedDark($hex, 0.41, 0.26, '#16352a');
    }

    /**
     * Background for the sidebar's active menu-item highlight in light
     * mode: same hue as the given color, as a solid pale tint instead of
     * primaryLightColor()'s alpha-over-white — that 10% alpha reads as
     * near-invisible next to the solid accent bar beside it.
     */
    public static function lightActiveHighlightBgFor(string $hex): string
    {
        return static::hueMatchedDark($hex, 0.55, 0.93, '#eef2f6');
    }

    /**
     * Text/foreground for an active/accent highlight in dark mode: same
     * hue, bright and saturated so it reads on the dark background above
     * (matches ESDEP's #4fd1a5 — H~160°, S~59%, L~57%).
     */
    public static function darkTextFor(string $hex): string
    {
        return static::hueMatchedDark($hex, 0.59, 0.57, '#f4f4f5');
    }

    /**
     * O "canvas" da página no modo escuro — preto neutro, mais escuro que
     * darkBgFor() pra cards/header/sidebar lerem como "em cima" da página
     * (zinc-950, igual à referência). Ignora $hex pelo mesmo motivo de
     * darkBgFor().
     */
    public static function darkBodyBgFor(string $hex): string
    {
        return '#0e0e11';
    }

    /**
     * Fundo "elevado" no modo escuro (ex.: linha de tabela em destaque,
     * bloco de ícone, borda do card) — neutro, um degrau mais claro que
     * darkBgFor() (zinc-800), mantendo a escada: body < surface < elevated
     * < hover. Ignora $hex pelo mesmo motivo de darkBgFor().
     */
    public static function darkElevatedBgFor(string $hex): string
    {
        return '#27272a';
    }

    /**
     * Fundo de hover/campo de formulário no modo escuro — neutro, o degrau
     * mais claro da escada acima (zinc-700). Ignora $hex pelo mesmo motivo
     * de darkBgFor().
     */
    public static function darkHoverBgFor(string $hex): string
    {
        return '#3f3f46';
    }

    public function darkActiveBgColor(): string
    {
        return static::hueMatchedDark($this->primary_color, 0.41, 0.15, '#0c221a');
    }

    public function darkActiveTextColor(): string
    {
        return static::darkTextFor($this->primary_color);
    }

    /**
     * Shared implementation behind the hue-matched dark accents
     * (darkActiveBgColor/darkActiveHighlightBgFor/darkTextFor) — same hue
     * as $hex, fixed saturation/lightness, falling back to a hand-picked
     * hex when $hex itself is achromatic (grey/white, so there's no hue to
     * match).
     */
    private static function hueMatchedDark(string $hex, float $saturation, float $lightness, string $achromaticFallback): string
    {
        [$hue, $inputSaturation] = static::toHsl($hex);

        if ($inputSaturation === 0.0) {
            return $achromaticFallback;
        }

        return static::fromHsl($hue, $saturation, $lightness);
    }

    /**
     * A readable text color (dark or light) for content placed on top of
     * the given background color, based on its perceived brightness.
     * "$darkText" is the color used on light backgrounds — defaults to a
     * neutral near-black, but callers rendering admin chrome (nav header,
     * top bar, sidebar) pass the user-chosen "Cor do texto" instead, so
     * that chrome text matches the rest of the panel's text exactly
     * whenever it resolves to the "dark" branch, instead of two different
     * near-black shades side by side.
     */
    public static function readableTextFor(string $hex, string $darkText = '#312a2a'): string
    {
        return static::isLightBackground($hex) ? $darkText : '#f4f4f5';
    }

    /**
     * Whether the given background color is light enough to need dark
     * text on top of it — same brightness threshold as readableTextFor(),
     * split out so callers can branch on "is this background light" (e.g.
     * which active-highlight pair to use) without comparing against
     * readableTextFor()'s return value, which is now a user-chosen color
     * and not a fixed, comparable constant.
     */
    public static function isLightBackground(string $hex): bool
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');
        $brightness = ($red * 299 + $green * 587 + $blue * 114) / 1000;

        return $brightness > 150;
    }

    private static function darken(string $hex, float $factor): string
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');

        $apply = fn (int $channel): int => (int) max(0, $channel * $factor);

        return sprintf('#%02x%02x%02x', $apply($red), $apply($green), $apply($blue));
    }

    /**
     * @return array{0: float, 1: float, 2: float} [hue 0-360, saturation 0-1, lightness 0-1]
     */
    private static function toHsl(string $hex): array
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');
        // Dividing by 255.0 (not 255) forces a float result — 255/255 in
        // PHP is the int 1, which made $delta an int 0 below, and `=== 0.0`
        // is false for an int (strict comparison checks type too), so pure
        // grays (white, black, gray) fell through into a division by zero.
        $r = $red / 255.0;
        $g = $green / 255.0;
        $b = $blue / 255.0;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;
        $delta = $max - $min;

        if ($delta === 0.0) {
            return [0.0, 0.0, $lightness];
        }

        $saturation = $delta / (1 - abs(2 * $lightness - 1));

        $hue = match ($max) {
            $r => fmod(($g - $b) / $delta, 6),
            $g => (($b - $r) / $delta) + 2,
            default => (($r - $g) / $delta) + 4,
        } * 60;

        if ($hue < 0) {
            $hue += 360;
        }

        return [$hue, $saturation, $lightness];
    }

    private static function fromHsl(float $hue, float $saturation, float $lightness): string
    {
        $c = (1 - abs(2 * $lightness - 1)) * $saturation;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $lightness - $c / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60 => [$c, $x, 0],
            $hue < 120 => [$x, $c, 0],
            $hue < 180 => [0, $c, $x],
            $hue < 240 => [0, $x, $c],
            $hue < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255)
        );
    }
}

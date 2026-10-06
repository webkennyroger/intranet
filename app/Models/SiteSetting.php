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
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'primary_color' => '#0C4A34',
            'nav_header_color' => '#FFFFFF',
            'header_color' => '#FFFFFF',
            'sidebar_color' => '#FFFFFF',
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
     * Background for an active/accent highlight in dark mode: same hue as
     * the given color, but dark and muted (matches the hand-picked ESDEP
     * green #16352a — H~159°, S~41%, L~15% — reproduced for any hue).
     */
    public static function darkBgFor(string $hex): string
    {
        [$hue, $saturation] = static::toHsl($hex);

        if ($saturation === 0.0) {
            return '#0c221a';
        }

        return static::fromHsl($hue, 0.41, 0.15);
    }

    /**
     * Background for the sidebar's active menu-item highlight: same hue as
     * the given color, but noticeably lighter than darkBgFor()'s surface
     * lightness (0.15 → 0.26). The sidebar's own background is darkBgFor()
     * of whatever color was chosen for it — if that happens to share a hue
     * with the primary color (e.g. both left at the default green), a
     * highlight built from darkBgFor() too would be nearly the same shade
     * and disappear into the sidebar. This stays visibly lighter regardless.
     */
    public static function darkActiveHighlightBgFor(string $hex): string
    {
        [$hue, $saturation] = static::toHsl($hex);

        if ($saturation === 0.0) {
            return '#16352a';
        }

        return static::fromHsl($hue, 0.41, 0.26);
    }

    /**
     * Text/foreground for an active/accent highlight in dark mode: same
     * hue, bright and saturated so it reads on the dark background above
     * (matches ESDEP's #4fd1a5 — H~160°, S~59%, L~57%).
     */
    public static function darkTextFor(string $hex): string
    {
        [$hue, $saturation] = static::toHsl($hex);

        if ($saturation === 0.0) {
            return '#e6f2ec';
        }

        return static::fromHsl($hue, 0.59, 0.57);
    }

    /**
     * The page/body canvas in dark mode: same hue as the given color, but
     * much darker than darkBgFor() so cards/header/sidebar still read as
     * "sitting on top of" the page (matches ESDEP's #081712 — L~6%).
     */
    public static function darkBodyBgFor(string $hex): string
    {
        [$hue, $saturation] = static::toHsl($hex);

        if ($saturation === 0.0) {
            return '#081712';
        }

        return static::fromHsl($hue, 0.35, 0.06);
    }

    public function darkActiveBgColor(): string
    {
        return static::darkBgFor($this->primary_color);
    }

    public function darkActiveTextColor(): string
    {
        return static::darkTextFor($this->primary_color);
    }

    /**
     * A readable text color (dark or light) for content placed on top of
     * the given background color, based on its perceived brightness.
     */
    public static function readableTextFor(string $hex): string
    {
        [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');
        $brightness = ($red * 299 + $green * 587 + $blue * 114) / 1000;

        return $brightness > 150 ? '#312a2a' : '#e6f2ec';
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

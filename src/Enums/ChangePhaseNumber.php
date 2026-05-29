<?php

namespace Platform\Change\Enums;

enum ChangePhaseNumber: int
{
    case URGENCY = 1;
    case COALITION = 2;
    case VISION = 3;
    case VOLUNTEERS = 4;
    case BARRIERS = 5;
    case SHORT_WINS = 6;
    case SUSTAIN = 7;
    case ANCHOR = 8;

    public function label(): string
    {
        return match ($this) {
            self::URGENCY => 'Dringlichkeit erzeugen',
            self::COALITION => 'Führungskoalition aufbauen',
            self::VISION => 'Vision & Strategie entwickeln',
            self::VOLUNTEERS => 'Freiwillige gewinnen',
            self::BARRIERS => 'Hindernisse beseitigen',
            self::SHORT_WINS => 'Kurzfristige Erfolge erzielen',
            self::SUSTAIN => 'Veränderung weiter vorantreiben',
            self::ANCHOR => 'Veränderung verankern',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::URGENCY => 'Dringlichkeit',
            self::COALITION => 'Koalition',
            self::VISION => 'Vision',
            self::VOLUNTEERS => 'Freiwillige',
            self::BARRIERS => 'Hindernisse',
            self::SHORT_WINS => 'Quick Wins',
            self::SUSTAIN => 'Vorantreiben',
            self::ANCHOR => 'Verankern',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::URGENCY => 'Warum ist die Veränderung jetzt notwendig? Dringlichkeit kommunizieren und Bewusstsein schaffen.',
            self::COALITION => 'Ein starkes Team mit Einfluss und Kompetenz zusammenstellen, das die Veränderung führt.',
            self::VISION => 'Eine klare, motivierende Vision formulieren und die Strategie zur Umsetzung definieren.',
            self::VOLUNTEERS => 'Breite Unterstützung gewinnen, Betroffene einbinden und die Vision kommunizieren.',
            self::BARRIERS => 'Strukturelle und organisatorische Hindernisse identifizieren und beseitigen.',
            self::SHORT_WINS => 'Schnelle, sichtbare Erfolge planen und feiern, um Momentum aufzubauen.',
            self::SUSTAIN => 'Erfolge nutzen, um weitere Veränderungen voranzutreiben. Nicht nachlassen.',
            self::ANCHOR => 'Neue Verhaltensweisen und Prozesse in der Unternehmenskultur verankern.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::URGENCY => 'heroicon-o-fire',
            self::COALITION => 'heroicon-o-user-group',
            self::VISION => 'heroicon-o-light-bulb',
            self::VOLUNTEERS => 'heroicon-o-megaphone',
            self::BARRIERS => 'heroicon-o-shield-check',
            self::SHORT_WINS => 'heroicon-o-trophy',
            self::SUSTAIN => 'heroicon-o-arrow-trending-up',
            self::ANCHOR => 'heroicon-o-building-library',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::URGENCY => '#E63946',
            self::COALITION => '#E76F51',
            self::VISION => '#F4D35E',
            self::VOLUNTEERS => '#A7C957',
            self::BARRIERS => '#457B9D',
            self::SHORT_WINS => '#2A9D8F',
            self::SUSTAIN => '#1D3557',
            self::ANCHOR => '#264653',
        };
    }

    public function colorRgb(): string
    {
        return match ($this) {
            self::URGENCY => '230, 57, 70',
            self::COALITION => '231, 111, 81',
            self::VISION => '244, 211, 94',
            self::VOLUNTEERS => '167, 201, 87',
            self::BARRIERS => '69, 123, 157',
            self::SHORT_WINS => '42, 157, 143',
            self::SUSTAIN => '29, 53, 87',
            self::ANCHOR => '38, 70, 83',
        };
    }

    public function shape(): string
    {
        return match ($this) {
            self::URGENCY => 'triangle',
            self::COALITION => 'diamond',
            self::VISION => 'triangle',
            self::VOLUNTEERS => 'hexagon',
            self::BARRIERS => 'circle',
            self::SHORT_WINS => 'pentagon',
            self::SUSTAIN => 'octagon',
            self::ANCHOR => 'square',
        };
    }

    public function needsDarkText(): bool
    {
        return match ($this) {
            self::VISION, self::VOLUNTEERS => true,
            default => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

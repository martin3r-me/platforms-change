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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

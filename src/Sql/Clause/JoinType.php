<?php

declare(strict_types=1);

namespace PhpDb\Sql\Clause;

use function strtolower;

enum JoinType: string
{
    case Inner      = 'INNER';
    case Outer      = 'OUTER';
    case FullOuter  = 'FULL OUTER';
    case Left       = 'LEFT';
    case Right      = 'RIGHT';
    case LeftOuter  = 'LEFT OUTER';
    case RightOuter = 'RIGHT OUTER';

    public static function fromString(string $type): self
    {
        return match (strtolower($type)) {
            'inner' => self::Inner,
            'outer' => self::Outer,
            'full outer' => self::FullOuter,
            'left' => self::Left,
            'right' => self::Right,
            'left outer' => self::LeftOuter,
            'right outer' => self::RightOuter,
            default => self::Inner,
        };
    }
}

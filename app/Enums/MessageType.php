<?php

namespace App\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case ATTACHMENT = 'attachment';
    case SYSTEM = 'system';

    /** Human friendly label for UI */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::ATTACHMENT => 'Attachment',
            self::SYSTEM => 'System',
        };
    }

    /** Array of raw string values, e.g. ['text','attachment','system'] */
    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }

    /** Safe factory from string (returns null if not valid) */
    public static function fromValue(string $value): ?self
    {
        return collect(self::cases())->firstWhere('value', $value) ?: null;
    }
}
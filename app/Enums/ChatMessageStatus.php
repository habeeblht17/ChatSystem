<?php

namespace App\Enums;

enum ChatMessageStatus: string
{
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::READ => 'Read',
        };
    }

    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }

    public static function fromValue(string $value): ?self
    {
        return collect(self::cases())->firstWhere('value', $value) ?: null;
    }
}
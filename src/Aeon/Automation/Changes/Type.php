<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

final class Type
{
    private const TYPE_ADDED = 1;

    private const TYPE_CHANGED = 2;

    private const TYPE_FIXED = 3;

    private const TYPE_REMOVED = 4;

    private const TYPE_DEPRECATED = 5;

    private const TYPE_SECURITY = 6;

    private int $type;

    private function __construct(int $type)
    {
        $this->type = $type;
    }

    public static function added() : self
    {
        return new self(self::TYPE_ADDED);
    }

    public static function changed() : self
    {
        return new self(self::TYPE_CHANGED);
    }

    public static function fixed() : self
    {
        return new self(self::TYPE_FIXED);
    }

    public static function removed() : self
    {
        return new self(self::TYPE_REMOVED);
    }

    public static function deprecated() : self
    {
        return new self(self::TYPE_DEPRECATED);
    }

    public static function security() : self
    {
        return new self(self::TYPE_SECURITY);
    }
}

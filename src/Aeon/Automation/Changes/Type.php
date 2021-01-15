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

    public static function fromString(string $type) : self
    {
        switch (\strtolower($type)) {
            case 'added':
                return self::added();
            case 'changed':
                return self::changed();
            case 'fixed':
                return self::fixed();
            case 'removed':
                return self::removed();
            case 'deprecated':
                return self::deprecated();
            case 'security':
                return self::security();

            default:
                throw new \InvalidArgumentException('Unknown change type: ' . $type);
        }
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

    /**
     * @return Type[];
     */
    public static function all() : array
    {
        return [
            self::added(),
            self::changed(),
            self::fixed(),
            self::removed(),
            self::deprecated(),
            self::security(),
        ];
    }

    public function isAdded() : bool
    {
        return $this->type === self::TYPE_ADDED;
    }

    public function isChanged() : bool
    {
        return $this->type === self::TYPE_CHANGED;
    }

    public function isFixed() : bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    public function isRemoved() : bool
    {
        return $this->type === self::TYPE_REMOVED;
    }

    public function isDeprecated() : bool
    {
        return $this->type === self::TYPE_DEPRECATED;
    }

    public function isSecurity() : bool
    {
        return $this->type === self::TYPE_SECURITY;
    }

    public function name() : string
    {
        switch ($this->type) {
            case self::TYPE_ADDED:
                return 'added';
            case self::TYPE_CHANGED:
                return 'changed';
            case self::TYPE_FIXED:
                return 'fixed';
            case self::TYPE_REMOVED:
                return 'removed';
            case self::TYPE_DEPRECATED:
                return 'deprecated';
            case self::TYPE_SECURITY:
                return 'security';
        }

        throw new \RuntimeException('Missing type name definition');
    }

    public function isEqual(self $type) : bool
    {
        return $this->type === $type->type;
    }
}

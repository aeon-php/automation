<?php

declare(strict_types=1);

namespace Aeon\Automation;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Type;

final class Changes
{
    private ChangesSource $source;

    /**
     * @var Change[]
     */
    private array $added;

    /**
     * @var Change[]
     */
    private array $changed;

    /**
     * @var Change[]
     */
    private array $fixed;

    /**
     * @var Change[]
     */
    private array $removed;

    /**
     * @var Change[]
     */
    private array $deprecated;

    /**
     * @var Change[]
     */
    private array $security;

    /**
     * @param string[] $added
     * @param string[] $changed
     * @param string[] $fixed
     * @param string[] $removed
     * @param string[] $deprecated
     * @param string[] $security
     */
    public function __construct(ChangesSource $source, array $added, array $changed, array $fixed, array $removed, array $deprecated, array $security)
    {
        $this->source = $source;
        $this->added = \array_map(fn (string $message) : Change => new Change(Type::added(), $message), $added);
        $this->changed = \array_map(fn (string $message) : Change => new Change(Type::changed(), $message), $changed);
        $this->fixed = \array_map(fn (string $message) : Change => new Change(Type::fixed(), $message), $fixed);
        $this->removed = \array_map(fn (string $message) : Change => new Change(Type::removed(), $message), $removed);
        $this->deprecated = \array_map(fn (string $message) : Change => new Change(Type::deprecated(), $message), $deprecated);
        $this->security = \array_map(fn (string $message) : Change => new Change(Type::security(), $message), $security);
    }

    public function empty() : bool
    {
        return (\count($this->added) + \count($this->changed) + \count($this->fixed) + \count($this->removed) + \count($this->deprecated) + \count($this->security)) === 0;
    }

    public function source() : ChangesSource
    {
        return $this->source;
    }

    /**
     * @return Change[]
     */
    public function added() : array
    {
        return $this->added;
    }

    /**
     * @return Change[]
     */
    public function changed() : array
    {
        return $this->changed;
    }

    /**
     * @return Change[]
     */
    public function fixed() : array
    {
        return $this->fixed;
    }

    /**
     * @return Change[]
     */
    public function removed() : array
    {
        return $this->removed;
    }

    /**
     * @return Change[]
     */
    public function deprecated() : array
    {
        return $this->deprecated;
    }

    /**
     * @return Change[]
     */
    public function security() : array
    {
        return $this->security;
    }

    /**
     * @return Change[]
     */
    public function all() : array
    {
        return \array_merge(
            $this->added(),
            $this->changed(),
            $this->fixed(),
            $this->removed(),
            $this->deprecated(),
            $this->security()
        );
    }
}

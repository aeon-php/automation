<?php

declare(strict_types=1);

namespace Aeon\Automation;

final class Changes
{
    private ChangesSource $source;

    /**
     * @var string[]
     */
    private array $added;

    /**
     * @var string[]
     */
    private array $changed;

    /**
     * @var string[]
     */
    private array $fixed;

    /**
     * @var string[]
     */
    private array $removed;

    /**
     * @var string[]
     */
    private array $deprecated;

    /**
     * @var string[]
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
        $this->added = $added;
        $this->changed = $changed;
        $this->fixed = $fixed;
        $this->removed = $removed;
        $this->deprecated = $deprecated;
        $this->security = $security;
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
     * @return string[]
     */
    public function added() : array
    {
        return $this->added;
    }

    /**
     * @return string[]
     */
    public function changed() : array
    {
        return $this->changed;
    }

    /**
     * @return string[]
     */
    public function fixed() : array
    {
        return $this->fixed;
    }

    /**
     * @return string[]
     */
    public function removed() : array
    {
        return $this->removed;
    }

    /**
     * @return string[]
     */
    public function deprecated() : array
    {
        return $this->deprecated;
    }

    /**
     * @return string[]
     */
    public function security() : array
    {
        return $this->security;
    }

    public function merge(self $changeLog) : self
    {
        return new self(
            \array_merge($this->added, $changeLog->added()),
            \array_merge($this->changed, $changeLog->changed()),
            \array_merge($this->fixed, $changeLog->fixed()),
            \array_merge($this->removed, $changeLog->removed()),
            \array_merge($this->deprecated, $changeLog->deprecated()),
            \array_merge($this->security, $changeLog->security())
        );
    }
}

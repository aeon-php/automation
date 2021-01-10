<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother\Changes;

use Aeon\Automation\Changes\ChangesSource;
use Aeon\Calendar\Gregorian\DateTime;

final class ChangesSourceMother
{
    public static function withTitle(string $title) : ChangesSource
    {
        return new class($title) implements ChangesSource {
            private string $title;

            public function __construct(string $content)
            {
                $this->title = $content;
            }

            public function id() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function url() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function title() : string
            {
                return $this->title;
            }

            public function date() : DateTime
            {
                throw new \RuntimeException('not implemented');
            }

            public function description() : string
            {
                return $this->title;
            }

            public function user() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function isFrom(string ...$users) : bool
            {
                throw new \RuntimeException('not implemented');
            }

            public function userUrl() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function equals(ChangesSource $source) : bool
            {
                throw new \RuntimeException('not implemented');
            }
        };
    }

    public static function withContent(string $content) : ChangesSource
    {
        return new class($content) implements ChangesSource {
            private string $content;

            public function __construct(string $content)
            {
                $this->content = $content;
            }

            public function id() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function url() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function title() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function date() : DateTime
            {
                throw new \RuntimeException('not implemented');
            }

            public function description() : string
            {
                return $this->content;
            }

            public function user() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function isFrom(string ...$users) : bool
            {
                throw new \RuntimeException('not implemented');
            }

            public function userUrl() : string
            {
                throw new \RuntimeException('not implemented');
            }

            public function equals(ChangesSource $source) : bool
            {
                throw new \RuntimeException('not implemented');
            }
        };
    }
}

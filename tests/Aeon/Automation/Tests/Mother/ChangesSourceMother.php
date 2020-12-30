<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Mother;

use Aeon\Automation\ChangesSource;

final class ChangesSourceMother
{
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

            public function description() : string
            {
                return $this->content;
            }

            public function user() : string
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

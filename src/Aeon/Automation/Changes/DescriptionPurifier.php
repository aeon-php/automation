<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes;

use League\CommonMark\CommonMarkConverter;

final class DescriptionPurifier
{
    private CommonMarkConverter $markdown;

    public function __construct()
    {
        $this->markdown = new CommonMarkConverter();
    }

    public function purify(string $description) : string
    {
        return \trim(
            \htmlspecialchars_decode(
                \strip_tags(
                    $this->markdown->convertToHtml(
                        \str_replace(
                            '*',
                            '\*',
                            \str_replace('`', '\`', $description)
                        )
                    )
                )
            )
        );
    }
}

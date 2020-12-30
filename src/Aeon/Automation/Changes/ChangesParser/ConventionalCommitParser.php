<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\ChangesParser;

use Aeon\Automation\Changes;
use Aeon\Automation\Changes\ChangesParser;
use Aeon\Automation\ChangesSource;
use Ramsey\ConventionalCommits\Configuration\DefaultConfiguration;
use Ramsey\ConventionalCommits\Exception\InvalidCommitMessage;
use Ramsey\ConventionalCommits\Parser;

final class ConventionalCommitParser implements ChangesParser
{
    private const DEFAULT_TYPES = [
        'add', 'added', 'feat', 'fix', 'fixed', 'cs', 'remove', 'removed', 'rm',
        'dep', 'deprecated', 'deprecate', 'sec', 'security',
        'change', 'changed', 'updated',
    ];

    public function support(ChangesSource $changesSource) : bool
    {
        try {
            $parser = new Parser(new DefaultConfiguration([
                'types' => self::DEFAULT_TYPES,
            ]));

            $message = $parser->parse($changesSource->description());

            return $message->getType() !== null && $message->getDescription() !== null;
        } catch (\Exception $invalidCommitMessage) {
            return false;
        }
    }

    public function parse(ChangesSource $changesSource) : Changes
    {
        $parser = new Parser(new DefaultConfiguration([
            'types' => self::DEFAULT_TYPES,
        ]));

        $message = $parser->parse($changesSource->description());

        switch (\strtolower($message->getType()->toString())) {
            case 'add':
            case 'added':
            case 'feat':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::added(), $message->getDescription()->toString()),
                );
            case 'updated':
            case 'changed':
            case 'change':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::changed(), $message->getDescription()->toString())
                );
            case 'cs':
            case 'fixed':
            case 'fix':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::fixed(), $message->getDescription()->toString())
                );
            case 'removed':
            case 'rm':
            case 'remove':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::removed(), $message->getDescription()->toString())
                );
            case 'dep':
            case 'deprecated':
            case 'deprecate':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::deprecated(), $message->getDescription()->toString())
                );
            case 'sec':
            case 'security':
                return new Changes(
                    $changesSource,
                    new Changes\Change(Changes\Type::security(), $message->getDescription()->toString())
                );

            default:
                throw new InvalidCommitMessage('Invalid format: ' . $changesSource->description());
        }
    }
}

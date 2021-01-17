<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\Detector;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesDetector;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\Type;
use Ramsey\ConventionalCommits\Configuration\DefaultConfiguration;
use Ramsey\ConventionalCommits\Exception\InvalidCommitMessage;
use Ramsey\ConventionalCommits\Parser;

final class ConventionalCommitDetector implements ChangesDetector
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

    public function detect(ChangesSource $changesSource) : Changes
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
                    new Change($changesSource, Type::added(), $message->getDescription()->toString()),
                );
            case 'updated':
            case 'changed':
            case 'change':
                return new Changes(
                    new Change($changesSource, Type::changed(), $message->getDescription()->toString())
                );
            case 'cs':
            case 'fixed':
            case 'fix':
                return new Changes(
                    new Change($changesSource, Type::fixed(), $message->getDescription()->toString())
                );
            case 'removed':
            case 'rm':
            case 'remove':
                return new Changes(
                    new Change($changesSource, Type::removed(), $message->getDescription()->toString())
                );
            case 'dep':
            case 'deprecated':
            case 'deprecate':
                return new Changes(
                    new Change($changesSource, Type::deprecated(), $message->getDescription()->toString())
                );
            case 'sec':
            case 'security':
                return new Changes(
                    new Change($changesSource, Type::security(), $message->getDescription()->toString())
                );

            default:
                throw new InvalidCommitMessage('Invalid format: ' . $changesSource->description());
        }
    }
}

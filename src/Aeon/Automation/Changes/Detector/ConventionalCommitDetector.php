<?php

declare(strict_types=1);

namespace Aeon\Automation\Changes\Detector;

use Aeon\Automation\Changes\Change;
use Aeon\Automation\Changes\Changes;
use Aeon\Automation\Changes\ChangesDetector;
use Aeon\Automation\Changes\ChangesSource;
use Aeon\Automation\Changes\DescriptionPurifier;
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

    private DescriptionPurifier $purifier;

    public function __construct(DescriptionPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

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

        $description = $this->purifier->purify($message->getDescription()->toString());

        switch (\strtolower($message->getType()->toString())) {
            case 'add':
            case 'added':
            case 'feat':
                return new Changes(
                    new Change($changesSource, Type::added(), $description),
                );
            case 'updated':
            case 'changed':
            case 'change':
                return new Changes(
                    new Change($changesSource, Type::changed(), $description)
                );
            case 'cs':
            case 'fixed':
            case 'fix':
                return new Changes(
                    new Change($changesSource, Type::fixed(), $description)
                );
            case 'removed':
            case 'rm':
            case 'remove':
                return new Changes(
                    new Change($changesSource, Type::removed(), $description)
                );
            case 'dep':
            case 'deprecated':
            case 'deprecate':
                return new Changes(
                    new Change($changesSource, Type::deprecated(), $description)
                );
            case 'sec':
            case 'security':
                return new Changes(
                    new Change($changesSource, Type::security(), $description)
                );

            default:
                throw new InvalidCommitMessage('Invalid format: ' . $this->purifier->purify($changesSource->description()));
        }
    }
}

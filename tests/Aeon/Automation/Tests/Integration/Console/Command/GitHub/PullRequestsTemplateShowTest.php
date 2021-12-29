<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command\GitHub;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\GitHub\PullRequestsTemplateShow;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class PullRequestsTemplateShowTest extends CommandTestCase
{
    public function test_pull_request_template_show() : void
    {
        $command = new PullRequestsTemplateShow(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setHttpCache(new ArrayAdapter());
        $command->setGitHubCache(new ArrayAdapter());

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestsTemplateShow::getDefaultName()));

        $commandTester->execute(
            [],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request Template - Show', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}

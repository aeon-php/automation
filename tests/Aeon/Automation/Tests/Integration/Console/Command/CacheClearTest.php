<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\CacheClear;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheClearTest extends CommandTestCase
{
    public function test_cache_clear() : void
    {
        $command = new CacheClear(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setHttpCache($httpCache = $this->createMock(AbstractAdapter::class));
        $command->setGitHubCache($githubCache = $this->createMock(AbstractAdapter::class));

        $httpCache->expects($this->once())
            ->method('setLogger');
        $httpCache->expects($this->once())
            ->method('clear');
        $githubCache->expects($this->once())
            ->method('setLogger');
        $githubCache->expects($this->once())
            ->method('clear');

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(CacheClear::getDefaultName()));

        $commandTester->execute(
            [],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('[NOTE] Clearing HTTP cache', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Clearing GitHub cache', $commandTester->getDisplay());
        $this->assertStringContainsString('[OK] Cache clear  ', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}

<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\ChangelogGenerate;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Aeon\Automation\Tests\Mother\SHAMother;
use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\GregorianCalendarStub;
use Aeon\Calendar\Gregorian\TimeZone;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class ChangelogGenerateTest extends CommandTestCase
{
    public function test_changelog_generate_without_parameters_with_tags() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::branch('1.x', $branchSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0', $tag100SHA = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $tag100SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag100SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.0.0', $tag100SHA),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Unreleased 2', $branchSHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag100SHA . '...' . $branchSHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 2,
                    'commits' => [
                        GitHubResponseMother::commit('Unreleased 1', $unreleased1 = SHAMother::random()),
                        GitHubResponseMother::commit('Unreleased 2', $branchSHA),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(2, 'Pull Request 2 Title')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased1 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(1, 'Pull Request 1 Title')]
            )),
        ));

        $command = new ChangelogGenerate(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerate::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Format: markdown', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit Start: ' . $branchSHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit End: ' . $tag100SHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes After: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes Before: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Total commits: 2', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] All commits analyzed, generating changelog:', $commandTester->getDisplay());
        $this->assertStringContainsString('## [Unreleased] - 2021-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#2](http://api.github.com) - **Pull Request 2 Title**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#1](http://api.github.com) - **Pull Request 1 Title**', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_changelog_generate_without_parameters_and_without_tags() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::branch('1.x', $branchSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Unreleased 3', $branchSHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Unreleased 3', $branchSHA),
                    GitHubResponseMother::commit('Unreleased 2', $unreleased2 = SHAMother::random()),
                    GitHubResponseMother::commit('Unreleased 1', $unreleased1 = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(3, 'Pull Request 3 Title')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased2 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(2, 'Pull Request 2 Title')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased1 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(1, 'Pull Request 1 Title')]
            )),
        ));

        $calendar = new GregorianCalendarStub(TimeZone::UTC());
        $calendar->setNow(DateTime::fromString('2021-01-01'));

        $command = new ChangelogGenerate(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setCalendar($calendar);

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerate::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Format: markdown', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit Start: ' . $branchSHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit End: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes After: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes Before: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Total commits: 3', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] All commits analyzed, generating changelog:', $commandTester->getDisplay());
        $this->assertStringContainsString('## [Unreleased] - 2021-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#3](http://api.github.com) - **Pull Request 3 Title**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#2](http://api.github.com) - **Pull Request 2 Title**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#1](http://api.github.com) - **Pull Request 1 Title**', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_changelog_generate_without_without_tags_only_pull_requests() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::branch('1.x', $branchSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Unreleased 3', $branchSHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Unreleased 3', $branchSHA),
                    GitHubResponseMother::commit('Unreleased 2', $unreleased2 = SHAMother::random()),
                    GitHubResponseMother::commit('Unreleased 1', $unreleased1 = SHAMother::random()),
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(3, 'Pull Request 3 Title')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased2 . '/pulls', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased1 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(1, 'Pull Request 1 Title')]
            )),
        ));

        $calendar = new GregorianCalendarStub(TimeZone::UTC());
        $calendar->setNow(DateTime::fromString('2021-01-01'));

        $command = new ChangelogGenerate(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setCalendar($calendar);

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerate::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation', '--only-pull-requests' => true],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Format: markdown', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit Start: ' . $branchSHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit End: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes After: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes Before: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Total commits: 3', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] All commits analyzed, generating changelog:', $commandTester->getDisplay());
        $this->assertStringContainsString('## [Unreleased] - 2021-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#3](http://api.github.com) - **Pull Request 3 Title**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#1](http://api.github.com) - **Pull Request 1 Title**', $commandTester->getDisplay());
        $this->assertStringNotContainsString(' - [#2](http://api.github.com) - **Pull Request 2 Title**', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_changelog_generate_without_tags_only_commits() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation', ResponseMother::jsonSuccess(
                GitHubResponseMother::repository('1.x')
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/branches/1.x', ResponseMother::jsonSuccess(
                GitHubResponseMother::branch('1.x', $branchSHA = SHAMother::random())
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $branchSHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Unreleased 3', $branchSHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits', ResponseMother::jsonSuccess(
                [
                    GitHubResponseMother::commit('Unreleased 3', $branchSHA),
                    GitHubResponseMother::commit('Unreleased 2', $unreleased2 = SHAMother::random()),
                    GitHubResponseMother::commit('Unreleased 1', $unreleased1 = SHAMother::random()),
                ]
            )),
        ));

        $calendar = new GregorianCalendarStub(TimeZone::UTC());
        $calendar->setNow(DateTime::fromString('2021-01-01'));

        $command = new ChangelogGenerate(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);
        $command->setCalendar($calendar);

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerate::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation', '--only-commits' => true],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Format: markdown', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit Start: ' . $branchSHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit End: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes After: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes Before: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Total commits: 3', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] All commits analyzed, generating changelog:', $commandTester->getDisplay());
        $this->assertStringContainsString('## [Unreleased] - 2021-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($branchSHA, 0, 6) . '](http://api.github.com) - **Unreleased 3**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($unreleased2, 0, 6) . '](http://api.github.com) - **Unreleased 2**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [' . \substr($unreleased1, 0, 6) . '](http://api.github.com) - **Unreleased 1**', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_changelog_generate_for_given_tag() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/tags', ResponseMother::jsonSuccess([
                GitHubResponseMother::tag('1.0.0', $tag100SHA = SHAMother::random()),
                GitHubResponseMother::tag('1.1.0', $tag110SHA = SHAMother::random()),
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.1.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.1.0', $tag110SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/git/refs/tags/1.0.0', ResponseMother::jsonSuccess(
                GitHubResponseMother::refCommit('tags/1.0.0', $tag100SHA)
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag110SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.1.0', $tag110SHA, '2021-01-01'),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag100SHA, ResponseMother::jsonSuccess(
                GitHubResponseMother::commit('Tag 1.0.0', $tag100SHA),
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/compare/' . $tag100SHA . '...' . $tag110SHA, ResponseMother::jsonSuccess(
                [
                    'total_commits' => 3,
                    'commits' => [
                        GitHubResponseMother::commit('Release 1.1.0 - 1', $unreleased1 = SHAMother::random()),
                        GitHubResponseMother::commit('Release 1.1.0 - 2', $unreleased2 = SHAMother::random()),
                        GitHubResponseMother::commit('Release 1.1.0 - 3 ', $tag110SHA),
                    ],
                ]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $tag110SHA . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(3, 'Release 1.1.0 Title - 3')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased2 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(2, 'Release 1.1.0 Title - 2')]
            )),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/commits/' . $unreleased1 . '/pulls', ResponseMother::jsonSuccess(
                [GitHubResponseMother::pullRequest(1, 'Release 1.1.0 Title - 1')]
            )),
        ));

        $command = new ChangelogGenerate(\getenv('AUTOMATION_ROOT_DIR'));
        $command->setGithub($client);

        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(ChangelogGenerate::getDefaultName()));

        $commandTester->setInputs(['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);

        $commandTester->execute(
            ['project' => 'aeon-php/automation', '--tag' => '1.1.0'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Changelog - Generate', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Format: markdown', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Project: aeon-php/automation', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit Start: ' . $tag110SHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Commit End: ' . $tag100SHA, $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes After: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Changes Before: N/A', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] Total commits: 3', $commandTester->getDisplay());
        $this->assertStringContainsString('! [NOTE] All commits analyzed, generating changelog:', $commandTester->getDisplay());
        $this->assertStringContainsString('## [1.1.0] - 2021-01-01', $commandTester->getDisplay());
        $this->assertStringContainsString('### Changed', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#3](http://api.github.com) - **Release 1.1.0 Title - 3**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#2](http://api.github.com) - **Release 1.1.0 Title - 2**', $commandTester->getDisplay());
        $this->assertStringContainsString(' - [#1](http://api.github.com) - **Release 1.1.0 Title - 1**', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}

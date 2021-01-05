<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\PullRequestDescriptionCheck;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class PullRequestDescriptionCheckTest extends CommandTestCase
{
    public function test_pull_request_without_valid_changelog_html_syntax() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/pulls/1', ResponseMother::jsonSuccess(
                GitHubResponseMother::pullRequest(1, 'Pull Request 1', 'not valid html syntax with changelog', '2021-01-01 00:00:00', 'user_login')
            )),
        ));

        $command = new PullRequestDescriptionCheck();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestDescriptionCheck::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', 'number' => 1],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request - Description - Check', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Author: @user_login', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Date: 2021-01-01T00:00:00+00:00', $commandTester->getDisplay());
        $this->assertStringContainsString('[ERROR] Invalid Pull Request syntax.', $commandTester->getDisplay());

        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function test_pull_request_without_valid_changelog_html_syntax_but_from_skipped_author() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/pulls/1', ResponseMother::jsonSuccess(
                GitHubResponseMother::pullRequest(1, 'Pull Request 1', 'not valid html syntax with changelog', '2021-01-01 00:00:00', 'user_login')
            )),
        ));

        $command = new PullRequestDescriptionCheck();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestDescriptionCheck::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', 'number' => 1, '--skip-from' => 'user_login'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request - Description - Check', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Author: @user_login', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Date: 2021-01-01T00:00:00+00:00', $commandTester->getDisplay());
        $this->assertStringContainsString('[OK] Skipping syntax check because of the author.', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_pull_request_with_valid_changes_syntax_but_without_content() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/pulls/1', ResponseMother::jsonSuccess(
                GitHubResponseMother::pullRequest(
                    1,
                    'Pull Request 1',
                    <<<'TEMPLATE'
<h2>Change Log</h2> 
<div id="change-log">
  <h4>Added</h4>
  <ul id="added">
    <!-- <li>Something that makes everything better</li> -->
  </ul> 
  <h4>Fixed</h4>  
  <ul id="fixed">
    <!-- <li>Something that wasn't working fine</li> -->
  </ul>
  <h4>Changed</h4>
  <ul id="changed">
    <!-- <li>Something into something new</li> -->
  </ul>  
  <h4>Removed</h4>
  <ul id="removed">
    <!-- <li>Something old or redundant</li> -->
  </ul>
  <h4>Deprecated</h4>
  <ul id="deprecated">
    <!-- <li>Something that is no more needed</li> -->
  </ul>  
  <h4>Security</h4> 
  <ul id="security">
    <!-- <li>Something that wasn't secure</li> -->
  </ul>     
</div>
TEMPLATE,
                    '2021-01-01 00:00:00',
                    'user_login'
                )
            )),
        ));

        $command = new PullRequestDescriptionCheck();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestDescriptionCheck::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', 'number' => 1],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request - Description - Check', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Author: @user_login', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Date: 2021-01-01T00:00:00+00:00', $commandTester->getDisplay());
        $this->assertStringContainsString('[ERROR] Pull Request syntax is valid but it\'s empty.', $commandTester->getDisplay());

        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function test_pull_request_with_valid_changes_syntax_without_content_but_with_skip_changes_count_option() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/pulls/1', ResponseMother::jsonSuccess(
                GitHubResponseMother::pullRequest(
                    1,
                    'Pull Request 1',
                    <<<'TEMPLATE'
<h2>Change Log</h2> 
<div id="change-log">
  <h4>Added</h4>
  <ul id="added">
    <!-- <li>Something that makes everything better</li> -->
  </ul> 
  <h4>Fixed</h4>  
  <ul id="fixed">
    <!-- <li>Something that wasn't working fine</li> -->
  </ul>
  <h4>Changed</h4>
  <ul id="changed">
    <!-- <li>Something into something new</li> -->
  </ul>  
  <h4>Removed</h4>
  <ul id="removed">
    <!-- <li>Something old or redundant</li> -->
  </ul>
  <h4>Deprecated</h4>
  <ul id="deprecated">
    <!-- <li>Something that is no more needed</li> -->
  </ul>  
  <h4>Security</h4> 
  <ul id="security">
    <!-- <li>Something that wasn't secure</li> -->
  </ul>     
</div>
TEMPLATE,
                    '2021-01-01 00:00:00',
                    'user_login'
                )
            )),
        ));

        $command = new PullRequestDescriptionCheck();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestDescriptionCheck::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', 'number' => 1, '--skip-changes-count' => true],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request - Description - Check', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Author: @user_login', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Date: 2021-01-01T00:00:00+00:00', $commandTester->getDisplay());
        $this->assertStringContainsString('[OK] Detected changes: 0', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_pull_request_with_valid_changes_syntax_and_at_least_one_change() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/pulls/1', ResponseMother::jsonSuccess(
                GitHubResponseMother::pullRequest(
                    1,
                    'Pull Request 1',
                    <<<'TEMPLATE'
<h2>Change Log</h2> 
<div id="change-log">
  <h4>Added</h4>
  <ul id="added">
    <li>Something was added</li>
  </ul> 
  <h4>Fixed</h4>  
  <ul id="fixed">
    <!-- <li>Something that wasn't working fine</li> -->
  </ul>
  <h4>Changed</h4>
  <ul id="changed">
    <!-- <li>Something into something new</li> -->
  </ul>  
  <h4>Removed</h4>
  <ul id="removed">
    <!-- <li>Something old or redundant</li> -->
  </ul>
  <h4>Deprecated</h4>
  <ul id="deprecated">
    <!-- <li>Something that is no more needed</li> -->
  </ul>  
  <h4>Security</h4> 
  <ul id="security">
    <!-- <li>Something that wasn't secure</li> -->
  </ul>     
</div>
TEMPLATE,
                    '2021-01-01 00:00:00',
                    'user_login'
                )
            )),
        ));

        $command = new PullRequestDescriptionCheck();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(PullRequestDescriptionCheck::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation', 'number' => 1],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Pull Request - Description - Check', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Author: @user_login', $commandTester->getDisplay());
        $this->assertStringContainsString('[NOTE] Date: 2021-01-01T00:00:00+00:00', $commandTester->getDisplay());
        $this->assertStringContainsString('[OK] Detected changes: 1', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}

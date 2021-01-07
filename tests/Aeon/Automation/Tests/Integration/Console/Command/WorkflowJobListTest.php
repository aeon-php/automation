<?php

declare(strict_types=1);

namespace Aeon\Automation\Tests\Integration\Console\Command;

use Aeon\Automation\Console\AeonApplication;
use Aeon\Automation\Console\Command\WorkflowJobList;
use Aeon\Automation\Tests\Http\HttpRequestStub;
use Aeon\Automation\Tests\Integration\Console\CommandTestCase;
use Aeon\Automation\Tests\Mother\GitHubResponseMother;
use Aeon\Automation\Tests\Mother\ResponseMother;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;

final class WorkflowJobListTest extends CommandTestCase
{
    public function test_project_workflow_list() : void
    {
        $client = Client::createWithHttpClient($httpClient = $this->httpClient(
            new HttpRequestStub('GET', '/repos/aeon-php/automation/actions/workflows', ResponseMother::jsonSuccess([
                'workflows' => [
                    GitHubResponseMother::workflow('Tests', $testsId = 1000000),
                    GitHubResponseMother::workflow('Static Analyze', $staticId = 1000001),
                ],
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/actions/workflows/' . $testsId . '/runs', ResponseMother::jsonSuccess([
                'workflow_runs' => [GitHubResponseMother::workflowRun($testsRunId = 2000000)],
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/actions/workflows/' . $staticId . '/runs', ResponseMother::jsonSuccess([
                'workflow_runs' => [GitHubResponseMother::workflowRun($staticRunId = 2000001)],
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/actions/runs/' . $testsRunId . '/jobs', ResponseMother::jsonSuccess([
                'jobs' => [GitHubResponseMother::workflowRunJob('tests', 'completed', 'success', '2020-01-01 00:00:00')],
            ])),
            new HttpRequestStub('GET', '/repos/aeon-php/automation/actions/runs/' . $staticRunId . '/jobs', ResponseMother::jsonSuccess([
                'jobs' => [GitHubResponseMother::workflowRunJob('static analyze', 'completed', 'success', '2020-01-01 00:00:00')],
            ])),
        ));

        $command = new WorkflowJobList();
        $command->setGithub($client);
        $application = new AeonApplication();
        $application->add($command);

        $commandTester = new CommandTester($application->get(WorkflowJobList::getDefaultName()));

        $commandTester->execute(
            ['project' => 'aeon-php/automation'],
            ['verbosity' => ConsoleOutput::VERBOSITY_VERBOSE]
        );

        $this->assertStringContainsString('Workflow - Job - List', $commandTester->getDisplay());
        $this->assertStringContainsString('---------------- ---------------- --------- ----------------------------', $commandTester->getDisplay());
        $this->assertStringContainsString(' Workflow         Job              Status    Completed At               ', $commandTester->getDisplay());
        $this->assertStringContainsString('---------------- ---------------- --------- ----------------------------', $commandTester->getDisplay());
        $this->assertStringContainsString(' Tests            tests            success   2020-01-01 00:00:00 +00:00 ', $commandTester->getDisplay());
        $this->assertStringContainsString(' Static Analyze   static analyze   success   2020-01-01 00:00:00 +00:00 ', $commandTester->getDisplay());
        $this->assertStringContainsString('---------------- ---------------- --------- ----------------------------', $commandTester->getDisplay());

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}

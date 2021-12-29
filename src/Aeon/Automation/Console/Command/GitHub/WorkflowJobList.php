<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command\GitHub;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkflowJobList extends AbstractCommand
{
    protected static $defaultName = 'gh:workflow:job:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List GitHub project actions jobs status from the latest workflow run')
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Workflow - Job - List');

        $workflows = $this->githubClient($project)->workflows();

        $tableHeaders = ['Workflow', 'Job', 'State', 'Status', 'Completed At'];

        $tableBody = [];

        foreach ($workflows->all() as $workflow) {
            $run = $this->githubClient($project)->workflowLatestRun($workflow);

            if ($run) {
                foreach ($this->githubClient($project)->workflowRunJobs($run)->all() as $job) {
                    $status = null;

                    if ($job->isSuccessful()) {
                        $status .= '<fg=green>success</>';
                    }

                    if (!$job->isCompleted()) {
                        $status .= ' <fg=yellow>running</>';
                    }

                    if ($status === null) {
                        $status .= ' <fg=red>failed</>';
                    }

                    $tableBody[] = [
                        $workflow->name(),
                        $job->name(),
                        $workflow->state(),
                        $status,
                        $job->isCompleted() ? $job->completedAt()->format('Y-m-d H:i:s P') : 'N/A',
                    ];
                }
            } else {
                $tableBody[] = [
                    $workflow->name(),
                    'N/A',
                    'N/A',
                ];
                $io->note('Workflow ' . $workflow->name() . ' was never executed.');
            }
        }

        $io->table($tableHeaders, $tableBody);

        return Command::SUCCESS;
    }
}

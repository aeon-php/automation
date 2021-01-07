<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Workflows;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkflowJobList extends AbstractCommand
{
    protected static $defaultName = 'workflow:job:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List project Github actions jobs status from the latest workflow run')
            ->addArgument('project', InputArgument::REQUIRED, 'project name');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = $this->configuration()->project($input->getArgument('project'));

        $io->title('Workflow - Job - List');

        $workflows = Workflows::getAll($this->github(), $project);

        $tableHeaders = ['Workflow', 'Job', 'Status'];

        $tableBody = [];

        foreach ($workflows->all() as $workflow) {
            $run = $workflow->latestRun($this->github(), $project);

            if ($run) {
                foreach ($run->jobs($this->github(), $project)->all() as $job) {
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
                        $status,
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

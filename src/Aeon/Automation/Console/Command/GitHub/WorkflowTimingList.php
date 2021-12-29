<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command\GitHub;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Aeon\Automation\GitHub\Project;
use Aeon\Calendar\TimeUnit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WorkflowTimingList extends AbstractCommand
{
    protected static $defaultName = 'gh:workflow:timing:list';

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setDescription('List GitHub project actions workflows billable minutes usage in current billing cycle')
            ->setHelp('Billable minutes only apply to workflows in private repositories that use GitHub-hosted runners.')
            ->addArgument('project', InputArgument::REQUIRED, 'project name')
            ->addOption('os', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Show billable minutes for operating systems', ['ubuntu', 'macos', 'windows']);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $project = new Project($input->getArgument('project'));

        $io->title('Workflow - Timing - List');

        $operatingSystems = (array) $input->getOption('os');

        if (!\count($operatingSystems)) {
            $io->error('Please provide at least one operating system');

            return Command::FAILURE;
        }

        foreach ($operatingSystems as $operatingSystem) {
            if (!\in_array($operatingSystem, ['ubuntu', 'macos', 'windows'], true)) {
                $io->error("Invalid operating system {$operatingSystem}, allowed operating systems are: ubuntu, macos, windows.");

                return Command::FAILURE;
            }
        }

        $workflows = $this->githubClient($project)->workflows();

        $tableHeaders = ['Name', 'Path', 'Operating System', 'Minutes'];

        $tableBody = [];

        $total = TimeUnit::seconds(0);

        foreach ($workflows->all() as $workflow) {
            $workflowTiming = $this->githubClient($project)->workflowTiming($workflow);

            if ($workflowTiming->isEmpty()) {
                continue;
            }

            $ubuntu = $workflowTiming->ubuntuTime();
            $macos = $workflowTiming->macosTime();
            $windows = $workflowTiming->windowsTime();

            if (\in_array('ubuntu', $operatingSystems, true)) {
                $tableBody[] = [
                    $workflow->name(),
                    $workflow->path(),
                    'Ubuntu',
                    $ubuntu ? "{$ubuntu->inMinutes()} min" : '-',
                ];

                if ($ubuntu) {
                    $total = $total->add($ubuntu);
                }
            }

            if (\in_array('macos', $operatingSystems, true)) {
                $tableBody[] = [
                    $workflow->name(),
                    $workflow->path(),
                    'MacOS',
                    $macos ? "{$macos->inMinutes()} min" : '-',
                ];

                if ($macos) {
                    $total = $total->add($macos);
                }
            }

            if (\in_array('windows', $operatingSystems, true)) {
                $tableBody[] = [
                    $workflow->name(),
                    $workflow->path(),
                    'Windows',
                    $windows ? "{$windows->inMinutes()} min" : '-',
                ];

                if ($windows) {
                    $total = $total->add($windows);
                }
            }
        }

        $io->table($tableHeaders, $tableBody);

        $io->note("Total: {$total->inMinutes()} min");

        return Command::SUCCESS;
    }
}

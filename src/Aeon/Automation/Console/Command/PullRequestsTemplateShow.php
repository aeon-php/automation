<?php

declare(strict_types=1);

namespace Aeon\Automation\Console\Command;

use Aeon\Automation\Console\AbstractCommand;
use Aeon\Automation\Console\AeonStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PullRequestsTemplateShow extends AbstractCommand
{
    protected static $defaultName = 'pull-request:template:show';

    protected function configure() : void
    {
        parent::configure();

        $this->setDescription('Display pull request template required by this tool to properly parse keepachangelog format');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new AeonStyle($input, $output);

        $io->title('Pull Request Template - Show');

        $io->note('Add file to the repository: .github/PULL_REQUEST_TEMPLATE.md');

        $io->writeln(
            <<<'TEMPLATE'
<!-- Bellow section will be used to automatically generate changelog, please do not modify HTML code structure -->
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
TEMPLATE
        );

        return Command::SUCCESS;
    }
}

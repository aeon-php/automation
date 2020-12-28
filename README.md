# Automation 

Automation is an internal Aeon tool used to automate release process of all Aeon libraries. 
It's still in the development phase, currently focused on generating change log files. 

## Composer Installation 

Before you start, [generate](https://github.com/settings/tokens) your own GitHub personal access token.

```
git clone git@github.com:aeon-php/automation.git
cd automation 
composer install
bin/automation change-log:get aeon-php/calendar --github-token="*********"
```

## Phar Installation

TODO: Coming soon 

## Documentation

```bash
aeon-automation

Usage:
  command [options] [arguments]

Options:
  -h, --help                         Display help for the given command. When no command is given display help for the list command
  -q, --quiet                        Do not output any message
  -V, --version                      Display this application version
      --ansi                         Force ANSI output
      --no-ansi                      Disable ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -c, --configuration=CONFIGURATION  Custom path to the automation.xml configuration file.
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  -gt, --github-token=GITHUB-TOKEN   Github personal access token, generated here: https://github.com/settings/tokens By default taken from AEON_AUTOMATION_GH_TOKEN env variable

Available commands:
  help               Displays help for a command
  list               Lists commands
 branch
  branch:list        List project branches
 cache
  cache:clear        Clears cache used to cache HTTP responses from GitHub
 change-log
  change-log:get     Get project changelog from commits and pull requests
 milestone
  milestone:create   Create new milestone for project
  milestone:list     
 project
  project:list       List all projects defined in automation.xml file
 pull-request
  pull-request:list  
 release
  release:list       List all project releases
 tag
  tag:list           Display all tags following SemVer convention sorted from the latest to oldest

```

In general the tool is harmless, however it can create things like milestones so for now use them at your own risk.

* [Contributing & Development](https://github.com/aeon-php/.github/blob/master/CONTRIBUTING.md)
* [Forum](https://forum.aeon-php.org/)
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

### Commands

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
  help                Displays help for a command
  list                Lists commands
 branch
  branch:list         List project branches
 cache
  cache:clear         Clears cache used to cache HTTP responses from GitHub
 changelog
  changelog:generate  Generate change log for a release.
 milestone
  milestone:create    Create new milestone for project
  milestone:list      
 project
  project:list        List all projects defined in automation.xml file
 pull-request
  pull-request:list   
 release
  release:list        List all project releases
 tag
  tag:list            Display all tags following SemVer convention sorted from the latest to oldest

```

### changelog:generate

```bash
Description:
  Generate change log for a release.

Usage:
  changelog:generate [options] [--] <project>

Arguments:
  project                               project name, for example aeon-php/calendar

Options:
  -b, --branch=BRANCH                   Get the the branch used instead of tag-start option when it's not provided. If empty, default repository branch is taken.
  -t, --tag=TAG                         List only changes from given release
  -f, --format=FORMAT                   How to format generated changelog, available formatters: "markdown" [default: "markdown"]
  -h, --help                            Display help for the given command. When no command is given display help for the list command
  -q, --quiet                           Do not output any message
  -V, --version                         Display this application version
      --ansi                            Force ANSI output
      --no-ansi                         Disable ANSI output
  -n, --no-interaction                  Do not ask any interactive question
  -c, --configuration=CONFIGURATION     Custom path to the automation.xml configuration file.
  -cs, --commit-start=COMMIT-START      Optional commit sha from which changelog is generated . When not provided, default branch latest commit is taken
  -ce, --commit-end=COMMIT-END          Optional commit sha until which changelog is generated . When not provided, latest tag is taken
  -ca, --changed-after=CHANGED-AFTER    Ignore all changes after given date, relative date formats like "-1 day" are also supported
  -cb, --changed-before=CHANGED-BEFORE  Ignore all changes before given date, relative date formats like "-1 day" are also supported
  -oc, --only-commits                   Use only commits to generate changelog
  -opr, --only-pull-requests            Use only pull requests to generate changelog
  -v|vv|vvv, --verbose                  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  -gt, --github-token=GITHUB-TOKEN      Github personal access token, generated here: https://github.com/settings/tokens By default taken from AEON_AUTOMATION_GH_TOKEN env variable

Help:
  When no parameters are provided, this command will generate UNRELEASED change log.
```

### tag:list

```bash
Description:
  Display all tags following SemVer convention sorted from the latest to oldest

Usage:
  tag:list [options] [--] <project>

Arguments:
  project                            project name

Options:
  -l, --limit=LIMIT                  Maximum number of tags to get
  -h, --help                         Display help for the given command. When no command is given display help for the list command
  -q, --quiet                        Do not output any message
  -V, --version                      Display this application version
      --ansi                         Force ANSI output
      --no-ansi                      Disable ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -c, --configuration=CONFIGURATION  Custom path to the automation.xml configuration file.
  -wd, --with-date                   display date when tag was committed
  -wc, --with-commit                 display commit SHA of tag
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  -gt, --github-token=GITHUB-TOKEN   Github personal access token, generated here: https://github.com/settings/tokens By default taken from AEON_AUTOMATION_GH_TOKEN env variable
```

In general the tool is harmless, however it can create things like milestones so for now use them at your own risk.

* [Contributing & Development](https://github.com/aeon-php/.github/blob/master/CONTRIBUTING.md)
* [Forum](https://forum.aeon-php.org/)
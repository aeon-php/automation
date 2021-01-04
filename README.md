# Automation 

Command Line Application to automatically generate changelog from any github project pull requests/commits history.

## Why? 

There are similar, more popular projects around, why this? Automation is designed to give project owner a bit more
flexibility and control over the changelog generation process. If pull requests are properly described Automation
will take that description, parse it and extract following types of changes from it: 

* added
* changed
* fixed
* removed
* deprecated
* security 

Those types are part of [keep a changelog](https://keepachangelog.com/en/1.0.0/) notation. 

Pull Request Description by definition is easy to update/change, also once commits are merged which makes it really easy
to get back to a specific PR when generating a changelog, fix it and get a better changelog output. 

What if pull requests does not have expected description? Then Automation will fall back into commit messages but even here it will
first try to look for [conventional commit](https://www.conventionalcommits.org/) notation and if that fail it will look for common prefixes
like `Fix`, `Added`, `Removed`.

Still not convinced by keep a changelog theme? No problem, Automation is supporting also more classical approach, just use `--theme=classic` option. 

What if one would still prefer commit messages over pull requests? Don't worry, `--only-commits` option is here for him, just like `--only-pull-requests` is for the opposite camp. 


### Turn This

![Pull Request](img/pull_request.png)

### Into This 

![Pull Request](img/output.gif)

## When? 

Not sure if automated changelog generation is for you? 

* Software with stable release cycle, following SemVer 
* Continuous Integration and Continuous Delivery (CI/CD)

Thos are two most popular use cases.
 
Releasing open source project you will probably look into `--tag` option that will take a all changes between given and previous tag and generate changelog. 

When working on a project that is released multiple times every day and does not really have a predictable release cycle you might want to store last deployed commit SHA hash and pass it later through `--commit-end` option. Automation will be able then to generate changelog since last release for you. 

## Installation & Usage

Before you start, [generate](https://github.com/settings/tokens) your own GitHub personal access token.
It can be provided as environment variable `AUTOMATION_GH_TOKEN` or through CLI option `--github-token` 

If Automation is not working as you expected, increase verbosity to see how it works under the hood.
It can be done by providing one of following options: 

* `-v` - normal
* `-vv` - verbose
* `-vvv` - debug

### Docker

```
docker pull aeonphp/automation
docker run -t --rm aeonphp/automation --help
```

### Composer 

```
git clone git@github.com:aeon-php/automation.git
cd automation 
composer install
bin/automation --help
```

### Phar 

TODO: Coming soon 

---

Because Automation is using GitHub API to grab project history you can use it against any popular github projects. 

```
automation changelog:generate organization/name -v
```

## Contributing 

Looking for a way to contribute? Awesome ❤️ Below you can find few places to start with:

* [Contributing & Development](https://github.com/aeon-php/.github/blob/master/CONTRIBUTING.md)
* [Forum](https://forum.aeon-php.org/)

You are also more than welcome to open an [issue](https://github.com/aeon-php/automation/issues) if anything about this project bothers you.

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
  help                        Displays help for a command
  list                        Lists commands
 branch
  branch:list                 List project branches
 cache
  cache:clear                 Clears cache used to cache HTTP responses from GitHub
 changelog
  changelog:generate          Generate change log for a release.
 milestone
  milestone:create            Create new milestone for project
  milestone:list              
 project
  project:list                List all projects defined in automation.xml file
 pull-request
  pull-request:list           
  pull-request:template:show  Display pull request template required by this tool to properly parse keepachangelog format
 release
  release:list                List all project releases
 tag
  tag:list                    Display all tags following SemVer convention sorted from the latest to oldest
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

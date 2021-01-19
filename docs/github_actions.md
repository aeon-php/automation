## GitHub Actions - Automation integration 

The goal of this integration is to keep your project changelog up to date in fully automated way. 

In order to make that happen we need to hook into following events: 

* pull request opened/updated
* commit pushed to branch 
* tag created 
* release created 

> If project supports multiple release branches it's necessary to add --github-file-update-ref option to point automation into
> the right changelog file. 

### Changelog Update 

This workflow will generate Unreleased changelog of the project, and it will commit it directly to `CHANGELOG.md` file 
in the repository. If the file does not exist, it will be created.

> If project already have existing changelog file, it's recommended to first use `changelog:genrate:all` command
and check if proposed changelog meets expectations. 

[.github/workflows/changelog-update.yml](/.github/workflows/changelog-update.yml)

### Changelog Release 

This workflow will listen to "Tag Created" event, and it will turn "Unreleased" section from the Changelog into the
release from tag using current date as a release date. 

[.github/workflows/changelog-release.yml](/.github/workflows/changelog-release.yml)

### Release Description Update

This workflow will listen to "Release Created" event, and it will update GitHub release description with changelog
limited only to tag associated with the release.

> Creating new release through GitHub UI will also create new Tag (with the name taken from release name), new Tag
> will trigger Changelog Release which will also turn Unreleased section into given release. 

[.github/workflows/release-description-update.yml](/.github/workflows/release-description-update.yml)

### Pull request Description Check

This workflow will listen to all events triggered after pull request description changes. It will fetch the pull request
and check if it has valid changelog section.

Before using this workflow it's recommended to set up pull request template first, this can be achieved by creating
[.github/PULL_REQUEST_TEMPLATE.md](https://github.com/aeon-php/.github/blob/master/.github/PULL_REQUEST_TEMPLATE.md) file in the repository. 

Content of this file can be taken from the output of `pull-request:template:show` command. 

> This workflow is mandatory only if you want to take changes, and their types (for keep a changelog theme) from pull
> request description provided by project contributors. It's also perfectly fine to not setup this workflow, then automation
> will use other ways to determine change types. 

[.github/workflows/pull-request-description-check.yml](/.github/workflows/pull-request-description-check.yml)

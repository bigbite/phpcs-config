# Contributing

Reading and following these guidelines will help us make the contribution process easy and effective for everyone involved. It also communicates that you agree to respect the time of the developers managing and developing these open source projects. In return, we will reciprocate that respect by addressing your issue, assessing changes, and helping you finalize your pull requests.

First things first, though: we want to ensure a safe environment for all contributors; therefore, you are expected to adhere to our [Code of Conduct](./CODE_OF_CONDUCT.md).

Please make sure to search for existing issues and PRs before creating your own. We work hard to makes sure issues are handled in a timely manner but, depending on the impact, it could take a while to investigate the root cause. A friendly ping in the comment thread to the submitter or a contributor can help draw attention if your issue is blocking.

### Issues

Issues should be used to report problems with the library, request a new feature, or to discuss potential changes before a PR is created. When you create a new issue, a template will be loaded that will guide you through collecting and providing the information we need to investigate. If you find an issue that addresses the problem you're having, please add your own reproduction information to the existing issue rather than creating a new one.

### Pull Requests

PRs to our libraries are always welcome and can be a quick way to get your fix or improvement slated for the next release. In general, PRs should:

- Only fix/add the functionality in question **OR** address wide-spread whitespace/style issues, not both.
- Add unit or integration tests for fixed or changed functionality.
- Address a single concern in the least number of changed lines as possible.
- Include documentation in the repo.
- Be accompanied by a complete Pull Request template.

For changes that address core functionality or would require breaking changes (e.g. a major release), it's best to open an issue to discuss your proposal first. This is not required but can save time creating and reviewing changes.

To create a pull request:

1. Fork the repository to your own Github account
2. Clone the project to your machine
3. Create a branch locally following our branching model
4. Commit changes to the branch
5. Following any formatting and testing guidelines specific to this repo
6. Push changes to your fork
7. Open a PR in our repository and follow the PR template so that we can efficiently review the changes.

### Branching Model

The central repo has one permanent main branches:

- main
- develop

The main branch at origin should be familiar to every Git user. Parallel to the main branch, another branch exists called develop. We consider origin/main to be the main branch where the source code of HEAD always reflects a production-ready state. We consider origin/develop to be the main branch where the source code of HEAD always reflects a state with the latest delivered development changes for the next release. When the source code in the develop branch reaches a stable point and is ready to be released, all of the changes should be merged back into main somehow and then tagged with a release number.

The other different types of branches we may use are:

- Feature branches
- Hotfix branches
- Chore branches

### Feature Branch

Feature branches are used to develop new features for the upcoming or a distant future release.

May branch off from: `develop`
Must merge back into: `develop`
Branch naming convention: `feature/<name-of-feature>`


### Hotfix Branch

Hotfix branches are meant to prepare for a new production release, albeit unplanned. They arise from the necessity to act immediately upon an undesired state of a live production version.

May branch off from: `main`
Must merge back into: `develop and main`
Branch naming convention: `hotfix/<name-of-hotfix>`


### Chore Branch

Chore branches are used when changes need to be made that don't impact functionality in any way, such as for adding/amending documentation.

May branch off from: `main`
Must merge back into: `develop and main`
Branch naming convention: `chor/<name-of-chore>`

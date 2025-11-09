# GitHub Actions Setup Notes

This document provides instructions for completing the GitHub Actions setup.

## ✅ Completed

- [x] CI workflow for automated testing
- [x] Code quality workflow (PHPStan, Rector, CodeSniffer)
- [x] Coverage workflow with Codecov integration
- [x] Release workflow for automated releases
- [x] Dependabot configuration for dependency updates
- [x] Issue templates (bug reports, feature requests)
- [x] Pull request template

## 🔧 Required Setup Steps

### 1. Codecov Integration (Optional but Recommended)

To enable code coverage reporting:

1. Go to [Codecov](https://codecov.io/)
2. Sign in with your GitHub account
3. Add the `prettyph/pretty-php` repository
4. Get your Codecov token
5. Add it to GitHub Secrets:
   - Go to: `Settings` → `Secrets and variables` → `Actions`
   - Create new secret: `CODECOV_TOKEN`
   - Paste the token from Codecov

**Note:** The CI will work without this, but coverage reports won't be uploaded.

### 2. Packagist Setup (For Release Automation)

To enable automatic Packagist updates on release:

1. Go to [Packagist](https://packagist.org/)
2. Submit your package: `prettyph/pretty-php`
3. Get API token from Packagist settings
4. Add GitHub Secrets:
   - `PACKAGIST_USERNAME` - Your Packagist username
   - `PACKAGIST_TOKEN` - Your Packagist API token

**Note:** The release workflow will work without this, but Packagist won't auto-update.

### 3. First Push to GitHub

```bash
# Stage all changes
git add .

# Commit
git commit -m "docs: add complete documentation and CI/CD setup

- Add README.md with project overview
- Add LICENSE (MIT)
- Add CHANGELOG.md
- Add ROADMAP.md
- Add CONTRIBUTING.md
- Setup GitHub Actions workflows (CI, coverage, release)
- Add Dependabot configuration
- Add issue and PR templates
- Translate Russian comments to English

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"

# Push to GitHub
git push origin main
```

### 4. Enable GitHub Actions

After pushing, GitHub Actions should automatically enable. Verify:

1. Go to your repository on GitHub
2. Click the `Actions` tab
3. You should see workflows running

### 5. Create First Release (Optional)

When ready for v0.1.0 release:

```bash
# Create and push a tag
git tag -a v0.1.0 -m "Release v0.1.0"
git push origin v0.1.0
```

This will trigger the release workflow automatically.

### 6. Branch Protection Rules (Recommended)

Protect the `main` branch:

1. Go to: `Settings` → `Branches`
2. Add rule for `main`
3. Enable:
   - ✅ Require pull request reviews before merging
   - ✅ Require status checks to pass before merging
     - Select: `Tests`, `Code Quality`, `Code Coverage`
   - ✅ Require branches to be up to date before merging
   - ✅ Include administrators

## 📊 Workflows Overview

### CI Workflow (`ci.yml`)
- **Triggers:** Push to `main`/`develop`, Pull Requests
- **Jobs:**
  - Tests on PHP 8.4
  - Code quality checks (PHPStan, Rector, CodeSniffer)
  - Performance benchmarks (on PRs)
- **Duration:** ~2-3 minutes

### Coverage Workflow (`coverage.yml`)
- **Triggers:** Push to `main`, Pull Requests
- **Jobs:**
  - Generate code coverage reports
  - Upload to Codecov
  - Comment coverage % on PRs
- **Duration:** ~2-4 minutes

### Release Workflow (`release.yml`)
- **Triggers:** Push tags matching `v*.*.*`
- **Jobs:**
  - Run all tests
  - Create GitHub release
  - Update Packagist
- **Duration:** ~2-3 minutes

## 🤖 Dependabot

Dependabot will automatically:
- Check for Composer dependency updates (weekly on Mondays)
- Check for GitHub Actions updates (weekly on Mondays)
- Create PRs for updates
- Label PRs appropriately

## 🎯 Next Steps

1. **Complete secret setup** (Codecov, Packagist tokens)
2. **Push to GitHub** and verify workflows run
3. **Setup branch protection** for main branch
4. **Publish to Packagist** manually first time
5. **Create v0.1.0 release** when ready

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Codecov Documentation](https://docs.codecov.com/)
- [Packagist Documentation](https://packagist.org/about)
- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)

## 🔍 Troubleshooting

### Workflows Not Running

- Check if GitHub Actions are enabled: `Settings` → `Actions` → `General`
- Verify workflow files have correct YAML syntax
- Check repository permissions for Actions

### Coverage Upload Fails

- Verify `CODECOV_TOKEN` is set correctly
- Check if Codecov repository is activated
- Review Codecov dashboard for errors

### Release Workflow Fails

- Verify tag format is `v*.*.*` (e.g., `v0.1.0`)
- Check CHANGELOG.md has entry for the version
- Review GitHub Actions logs for specific errors

## ✨ Features

The setup includes:

- ✅ Automated testing on every push/PR
- ✅ Code quality enforcement
- ✅ Coverage tracking and reporting
- ✅ Automated releases with changelog
- ✅ Dependency update automation
- ✅ Professional issue/PR templates
- ✅ Performance benchmark tracking

---

**Questions?** Open an issue or check [CONTRIBUTING.md](../CONTRIBUTING.md)

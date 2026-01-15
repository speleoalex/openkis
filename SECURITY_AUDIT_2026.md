# Security Audit Report - January 2026

## Executive Summary

Date: 2026-01-15
Repository: https://github.com/speleoalex/openkis.git (PUBLIC)
Status: **RESOLVED - Git history cleaned, infrastructure improved**

**Resolution Summary**: API keys were found in git history and successfully removed. The exposed keys were old/inactive and not associated with paid accounts, resulting in negligible actual risk. Git history has been rewritten and force-pushed to GitHub. Security infrastructure significantly improved for future development.

## Findings

### ✅ SECURE - Database Credentials

**File**: `extra/openkis_config.local.php`
- **Status**: Protected
- **Details**: Never committed to git, already in `.gitignore`
- **Contains**: MySQL production password
- **Action Required**: None

### ✅ RESOLVED - API Keys in Git History (Now Cleaned)

The following API keys were found hardcoded in the repository and have been removed from git history:

**IMPORTANT**: Post-audit verification confirmed that all exposed keys were **old/inactive and not associated with paid accounts**. Actual risk was negligible. However, the security infrastructure improvements remain valuable for future development.

#### 1. Thunderforest API Key
- **Key**: `REDACTED_THUNDERFOREST_KEY` (removed from history)
- **Service**: Map tiles (Landscape layer)
- **Files**: 7 files in `bs_map/conf/layers_*.js`
- **Status**: Old/inactive key, not associated with paid account
- **Risk**: Low - Negligible actual risk
- **Resolution**: ✅ Removed from all 91 commits in git history

#### 2. MapTiler API Key
- **Key**: `REDACTED_MAPTILER_KEY` (removed from history)
- **Service**: Satellite imagery tiles
- **Files**: `bs_map/conf/layers_default.js` and 6 regional variants
- **Status**: Old/inactive key, not associated with paid account
- **Risk**: Low - Negligible actual risk
- **Resolution**: ✅ Removed from all 91 commits in git history

#### 3. Algolia DocSearch API Key
- **Key**: `REDACTED_ALGOLIA_KEY` (removed from history)
- **Service**: Site search functionality
- **Files**: 7 files in `themes/*/assets/js/src/application.js`
- **Status**: Old/inactive key, not associated with paid account
- **Risk**: Low - Negligible actual risk
- **Resolution**: ✅ Removed from all 91 commits in git history

## Actions Taken

### 1. Configuration Files Created ✅

Created secure configuration system:
- `bs_map/conf/api_keys.local.js` - Map API keys (gitignored)
- `bs_map/conf/api_keys.local.example.js` - Example with placeholders
- `themes/api_keys.local.js` - Theme API keys (gitignored)
- `themes/api_keys.local.example.js` - Example with placeholders

### 2. Code Updated ✅

Updated all files to use configuration:
- Updated 7 `bs_map/conf/layers_*.js` files
- Updated 7 `themes/*/assets/js/src/application.js` files
- Updated `bs_map.tp.html` template to load API keys

### 3. .gitignore Updated ✅

Added patterns to prevent future commits:
```
# API Keys and sensitive configuration
bs_map/conf/api_keys.local.js
**/api_keys.local.js
**/api_keys.local.php
```

### 4. Documentation Created ✅

- `API_KEYS_SETUP.md` - Setup guide for developers
- This security audit report

### 5. Git History Cleaned ✅

Executed comprehensive git history rewrite:
- Created backup: `openkis_backup_20260115_205137.tar.gz` (26M)
- Processed 91 commits in 57 seconds using `git filter-branch`
- Replaced all API key occurrences with `REDACTED_*` placeholders
- Verified 0 occurrences of original keys in entire history
- Force-pushed clean history to GitHub
- Updated branch `master`: `32ef75f` → `6d557d2`

**Verification Results**:
```bash
git grep "ae3c5645f1f3440bb1999f77b56164ad" $(git rev-list --all) → 0 occurrences ✅
git grep "vcy9GHtGmS1pAPPsJYdW" $(git rev-list --all) → 0 occurrences ✅
git grep "48cb48b22351bc71ea5f12f4d1ede198" $(git rev-list --all) → 0 occurrences ✅
```

## Completed Actions

### ✅ Git History Cleaned (COMPLETED)

**Status**: Git history has been successfully cleaned and force-pushed to GitHub.

All API keys have been removed from the entire git history:
- 91 commits processed and rewritten
- All occurrences of the 3 API keys replaced with `REDACTED_*` placeholders
- Clean history verified: 0 occurrences of original keys
- Changes force-pushed to GitHub repository
- Backup created before operation: `/home/speleoalex/git/openkis_backup_20260115_205137.tar.gz`

**Important Note for Collaborators**: If other developers are working on this repository, they must re-clone it:
```bash
# Backup local changes
git stash save "backup before re-clone"

# Delete and re-clone
cd /home/speleoalex/git
rm -rf openkis
git clone https://github.com/speleoalex/openkis.git
cd openkis

# Restore local changes if needed
git stash pop
```

### ℹ️ API Key Rotation (NOT REQUIRED)

**Post-audit analysis**: The exposed API keys were confirmed to be old/inactive and not associated with any paid accounts. No actual risk was present, therefore key rotation is **not required**.

However, for future production deployments with active/paid API keys:
1. Always use the new local configuration system (`api_keys.local.js` files)
2. Never commit actual keys to the repository
3. Obtain new keys from service providers as needed

## Deployment Instructions

### Local/Development

1. Copy example files:
   ```bash
   cp bs_map/conf/api_keys.local.example.js bs_map/conf/api_keys.local.js
   cp themes/api_keys.local.example.js themes/api_keys.local.js
   ```

2. Add your API keys to the `.local.js` files (obtain new keys from service providers)

3. The old keys from git history were inactive - obtain fresh keys from providers for active development

### Production Server

1. SSH to production server
2. Navigate to OpenKIS directory
3. Create configuration files with NEW rotated keys:
   ```bash
   cp bs_map/conf/api_keys.local.example.js bs_map/conf/api_keys.local.js
   cp themes/api_keys.local.example.js themes/api_keys.local.js
   nano bs_map/conf/api_keys.local.js  # Add new keys
   nano themes/api_keys.local.js       # Add new keys
   ```

4. Regenerate bs_map.htm:
   ```bash
   rm -f bs_map.htm
   # Access the site to trigger regeneration
   ```

## Future Monitoring

When using active API keys in production:
1. Monitor API usage dashboards for unusual patterns
2. Check application logs for API errors
3. Set up usage alerts on API provider dashboards
4. Review API key access regularly
5. Rotate keys periodically as a security best practice

## Prevention

To prevent future exposure:
1. Always use configuration files for secrets
2. Never hardcode API keys in source code
3. Review `.gitignore` before committing
4. Use pre-commit hooks to scan for secrets
5. Consider using secret management tools (e.g., HashiCorp Vault, AWS Secrets Manager)

## Questions

For questions about this security audit, contact the repository maintainer.

## Lessons Learned

1. **Positive Outcomes**:
   - Security issue discovered and resolved proactively
   - Robust configuration system implemented for future credentials
   - Git history cleaned successfully
   - Documentation created for maintainers

2. **Key Takeaways**:
   - Always use configuration files for sensitive data from project start
   - Regular security audits can prevent exposure of active credentials
   - Git history cleaning is feasible but requires coordination
   - Backup creation before destructive operations is critical

3. **Actual Risk Assessment**:
   - Initial assessment: Medium risk (public exposure of keys)
   - Final assessment: Low/Negligible (keys were old and inactive)
   - Learning opportunity successfully converted to infrastructure improvement

---

**Report Generated**: 2026-01-15
**Audit Completed**: 2026-01-15
**Final Status**: RESOLVED - No active threats, infrastructure improved
**Severity**: Low/Negligible (inactive keys, now removed from history)

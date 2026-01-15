# Security Audit Report - January 2026

## Executive Summary

Date: 2026-01-15
Repository: https://github.com/speleoalex/openkis.git (PUBLIC)
Status: **API keys found in git history - REQUIRES ACTION**

## Findings

### ✅ SECURE - Database Credentials

**File**: `extra/openkis_config.local.php`
- **Status**: Protected
- **Details**: Never committed to git, already in `.gitignore`
- **Contains**: MySQL production password
- **Action Required**: None

### ⚠️ AT RISK - API Keys in Git History

The following API keys were found hardcoded in the repository and are present in git history:

#### 1. Thunderforest API Key
- **Key**: `REDACTED_THUNDERFOREST_KEY`
- **Service**: Map tiles (Landscape layer)
- **Files**: 7 files in `bs_map/conf/layers_*.js`
- **Commits**: Multiple commits since commit c4138af
- **Risk**: Medium - Public repository exposes key to anyone

#### 2. MapTiler API Key
- **Key**: `REDACTED_MAPTILER_KEY`
- **Service**: Satellite imagery tiles
- **Files**: `bs_map/conf/layers_default.js` and 6 regional variants
- **Risk**: Medium - Public repository exposes key to anyone

#### 3. Algolia DocSearch API Key
- **Key**: `REDACTED_ALGOLIA_KEY`
- **Service**: Site search functionality
- **Files**: 7 files in `themes/*/assets/js/src/application.js`
- **Risk**: Low - DocSearch keys are typically public, but should be rotated

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

## Required Actions

### CRITICAL - Rotate API Keys

The exposed API keys should be rotated immediately:

1. **Thunderforest**
   - Login to: https://manage.thunderforest.com/
   - Delete or regenerate the exposed key: `REDACTED_THUNDERFOREST_KEY`
   - Generate new key
   - Update `bs_map/conf/api_keys.local.js` with new key

2. **MapTiler**
   - Login to: https://cloud.maptiler.com/account/keys/
   - Delete or regenerate the exposed key: `REDACTED_MAPTILER_KEY`
   - Generate new key
   - Update `bs_map/conf/api_keys.local.js` with new key

3. **Algolia DocSearch**
   - Login to: https://www.algolia.com/account/api-keys/
   - Check if key `REDACTED_ALGOLIA_KEY` needs rotation
   - If using public DocSearch, may not need rotation
   - Update `themes/api_keys.local.js` if rotated

### OPTIONAL - Clean Git History

**Warning**: This requires force push and coordination with all developers.

To completely remove keys from git history:

```bash
# Use git-filter-repo (recommended) or BFG Repo-Cleaner
# This will rewrite ALL commit hashes

# Example with git-filter-repo:
git filter-repo --replace-text <(echo 'REDACTED_THUNDERFOREST_KEY==>REDACTED_THUNDERFOREST_KEY')
git filter-repo --replace-text <(echo 'REDACTED_MAPTILER_KEY==>REDACTED_MAPTILER_KEY')
git filter-repo --replace-text <(echo 'REDACTED_ALGOLIA_KEY==>REDACTED_ALGOLIA_KEY')

# Force push to remote
git push origin --force --all
git push origin --force --tags
```

**Important**: All collaborators must re-clone the repository after this operation.

## Deployment Instructions

### Local/Development

1. Copy example files:
   ```bash
   cp bs_map/conf/api_keys.local.example.js bs_map/conf/api_keys.local.js
   cp themes/api_keys.local.example.js themes/api_keys.local.js
   ```

2. Add your API keys to the `.local.js` files

3. For temporary testing, you can use the old keys (now in git history), but rotate them soon

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

## Monitoring

After rotating keys:
1. Monitor API usage dashboards for unauthorized access
2. Check application logs for API errors
3. Set up usage alerts on API provider dashboards
4. Review API key access regularly

## Prevention

To prevent future exposure:
1. Always use configuration files for secrets
2. Never hardcode API keys in source code
3. Review `.gitignore` before committing
4. Use pre-commit hooks to scan for secrets
5. Consider using secret management tools (e.g., HashiCorp Vault, AWS Secrets Manager)

## Questions

For questions about this security audit, contact the repository maintainer.

---

**Report Generated**: 2026-01-15
**Next Review**: After key rotation (immediate)
**Severity**: Medium (API keys exposed but easily rotatable)

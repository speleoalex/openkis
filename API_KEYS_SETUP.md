# API Keys Configuration Guide

This document explains how to configure API keys for OpenKIS external services.

## Overview

OpenKIS uses external mapping and search services that require API keys. These keys have been moved to local configuration files that are **not committed to version control** to protect them from public exposure.

## Configuration Files

### For Maps (bs_map)

**File**: `bs_map/conf/api_keys.local.js`

1. Copy the example file:
   ```bash
   cp bs_map/conf/api_keys.local.example.js bs_map/conf/api_keys.local.js
   ```

2. Edit `api_keys.local.js` and add your API keys:
   ```javascript
   var API_KEYS = {
       THUNDERFOREST: 'your_thunderforest_api_key_here',
       MAPTILER: 'your_maptiler_api_key_here',
       ALGOLIA_DOCSEARCH: 'your_algolia_docsearch_key_here'
   };
   ```

### For Themes

**File**: `themes/api_keys.local.js`

1. Copy the example file:
   ```bash
   cp themes/api_keys.local.example.js themes/api_keys.local.js
   ```

2. Edit `api_keys.local.js` and add your API key:
   ```javascript
   var THEME_API_KEYS = {
       ALGOLIA_DOCSEARCH: 'your_algolia_docsearch_key_here'
   };
   ```

## Getting API Keys

### Thunderforest (Map Tiles)

- Service: Map tiles (Landscape layer)
- Sign up: https://www.thunderforest.com/docs/apikeys/
- Free tier: 150,000 map tile views per month

### MapTiler (Satellite Imagery)

- Service: Satellite map tiles
- Sign up: https://www.maptiler.com/
- Free tier: 100,000 map tile loads per month

### Algolia DocSearch (Site Search)

- Service: Documentation search functionality
- Sign up: https://docsearch.algolia.com/
- Note: This service is free for open source documentation

## Security Notes

- **Never commit** `*api_keys.local.js` files to version control
- These files are already in `.gitignore`
- The example files (`*.example.js`) contain placeholder keys and are safe to commit
- Keep your API keys private and rotate them regularly
- Monitor your API usage to detect any unauthorized access

## Troubleshooting

### Maps not loading

If map tiles are not displaying:
1. Check browser console for 401/403 errors
2. Verify API keys are correctly configured in `bs_map/conf/api_keys.local.js`
3. Check that the file is being loaded (view page source)
4. Verify your API keys are valid and have not expired

### Search not working

If site search is not functioning:
1. Check browser console for errors
2. Verify API key in `themes/api_keys.local.js`
3. Ensure the theme template includes the API keys file

## Development Setup

For local development without external services:
1. You can leave the example keys in place (they may have limited functionality)
2. Or comment out the relevant layer configurations in `bs_map/conf/layers_*.js`

## Production Deployment

When deploying to production:
1. Ensure `api_keys.local.js` files are created on the server
2. Use production-specific API keys (not development keys)
3. Configure appropriate usage limits and restrictions on your API provider dashboards
4. Set up monitoring for API usage and costs

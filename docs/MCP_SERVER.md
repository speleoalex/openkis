# OpenKIS MCP server

`openkis_mcp.php` is a [Model Context Protocol](https://modelcontextprotocol.io) server:
AI assistants (Claude, ChatGPT, ...) can read the cadastre and, when allowed, edit it.
Every call runs with the identity and the permissions of the owner of the API key.

- Endpoint: `https://<site>/openkis_mcp.php`
- Transport: Streamable HTTP (JSON responses)
- Output: CSV, to keep the token usage low
- Writes go through the same logic as the web UI: history in `<table>_versions`,
  `recordupdate`/`userupdate`, `function_on_insert`/`update`/`delete` hooks (e.g. the
  automatic cadastre number), list cache invalidation.

Italian version: [MCP_SERVER_IT.md](MCP_SERVER_IT.md).

## Authentication

Every request carries an API key in one of these forms:

```
X-API-Key: <key>
Authorization: Bearer <key>
```

Some hosting providers (e.g. Aruba) strip the `Authorization` header before PHP: use
`X-API-Key` there.

### Personal keys (recommended)

- The key is stored in `fn_users.apikey` (the same key is accepted by the Finis CMS MCP
  server `index.php?fnapp=mcp`, see `finis/doc/en_mcp_guide.md`).
- Only the administrators (level 10) and the users **enabled** by an administrator can
  use a key: enabled users are the members of the group `mcp_users`
  (`$_FN['apikey_groups']`). The keys of the other users are refused.
- By default an enabled user is **read only**. The write tools (`insert`, `update`,
  `upload`, `delete`) are available only to the administrators and to the users an
  administrator allowed to write: group `mcp_write` (`$_FN['openkis_mcp_write_group']`).
- Within those limits the server applies the web UI rules:
  - only the dbview sections the user can view; inside sections with per record
    permissions (`groupview`, `viewonlycreator`) only the records the user can see
    (e.g. hidden caves);
  - writes only where the section allows them (`groupinsert`, `groupadmin`, record
    owner, `fieldusers`);
  - the lookup tables referenced by the readable sections are readable; `fn_users`,
    `*_versions` and the other tables are not;
  - the `sql` tool, deleted records and `where` filters with subqueries are reserved to
    the administrators.

### Global key (legacy)

`$_FN['openkis_mcp_apikey']` (string or array) gives full access; the changes are
recorded as `$_FN['openkis_mcp_user']`.

## The "MCP server" page

**Configuration > MCP server** (`index.php?mod=openkis_mcp`, section `sections/openkis_mcp`):

- **enabled user**: server URL, their own key (generate, regenerate, revoke), the
  `claude mcp add` command, the JSON configuration for other clients and the table of the
  effective permissions of the key;
- **user not enabled**: only a message asking to contact an administrator;
- **administrator**: searches a user and
  - enables / disables the MCP server (disabling also revokes the key and the write
    permission);
  - allows the write tools or sets the user back to read only;
  - generates or revokes the key;
  - sees the list of the enabled users and of the users with a key (masked key).

Permissions table icons: 🛡️ read and edit all records, ✏️ read, insert and edit the
records allowed, 👁️ read only, 🚫 no access, 🔒 only the records visible to the user.
When the MCP access is lower than the access on the website (user without write
permission) the site access is shown too.

Every enable / disable / write / key change is written in the site log (context `apikey`).

## Configuration

Defaults in `extra/openkis_config.inc.php`, override in `extra/openkis_config.local.php`:

```php
$_FN['apikey_groups'] = "mcp_users";            // users enabled to use their key ("" = everybody)
$_FN['openkis_mcp_write_group'] = "mcp_write";  // users allowed to use the write tools
$_FN['openkis_mcp_apikey'] = "long-random-key"; // optional legacy key(s), full access
$_FN['openkis_mcp_user'] = "admin";             // author of the changes made with the legacy key
$_FN['openkis_mcp_readonly'] = 0;               // 1 = no write tools for anybody
$_FN['openkis_mcp_userkeys'] = 1;               // 0 = refuse the personal keys
```

The groups `mcp_users` and `mcp_write` are created the first time an administrator opens
the MCP server page.

## Tools

| Tool | Access | Purpose |
|---|---|---|
| `whoami` | all | user of the key, groups, access level, read only flag |
| `sections` | all | dbview sections readable with the key and their access (`admin`, `write`, `read`) |
| `tables` | all | readable tables |
| `schema` | all | fields of a table or section: type, flags, foreign keys, options |
| `query` | all | records as CSV with `filter`, `where`, `order`, `limit`, `offset`, `count` |
| `get` | all | full record by primary key |
| `sql` | admin | read only SQL (SELECT/SHOW/DESCRIBE/EXPLAIN) |
| `insert` | write | insert records (runs the section hooks) |
| `update` | write | update fields; the previous version goes to the history |
| `upload` | write | set a file or image field (base64, url or file of another record; builds the thumbnail) |
| `delete` | write | delete records (or hide them in `hide_on_delete` sections) |

The write tools are not listed in `tools/list` for read only keys and are refused if
called.

## Client configuration

Claude Code:

```bash
claude mcp add --transport http openkis https://<site>/openkis_mcp.php --header "X-API-Key: <key>"
```

Other clients (JSON):

```json
{
  "mcpServers": {
    "openkis": {
      "type": "http",
      "url": "https://<site>/openkis_mcp.php",
      "headers": { "X-API-Key": "<key>" }
    }
  }
}
```

Test with curl:

```bash
curl -s https://<site>/openkis_mcp.php -H "X-API-Key: <key>" \
  -H 'Accept: application/json, text/event-stream' -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"whoami","arguments":{}}}'
```

## Security notes

- The key is personal: whoever has it acts on the cadastre as that user. Revoke it from
  the MCP server page when a client is no longer used.
- Give the write permission only to the users who need it: the read only default keeps an
  assistant from changing data by mistake.
- Use HTTPS: the key travels in a header of every request.

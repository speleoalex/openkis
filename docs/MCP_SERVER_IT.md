# Server MCP di OpenKIS

`openkis_mcp.php` è un server [Model Context Protocol](https://modelcontextprotocol.io):
gli assistenti AI (Claude, ChatGPT, ...) possono leggere il catasto e, se autorizzati,
modificarlo. Ogni chiamata viene eseguita con l'identità e i permessi dell'utente, che si
collega con utente e password (OAuth) o con una chiave API personale.

- Endpoint: `https://<sito>/openkis_mcp.php`
- Trasporto: Streamable HTTP (risposte JSON)
- Output: CSV, per consumare pochi token
- Le scritture seguono la stessa logica del sito: storico in `<tabella>_versions`,
  `recordupdate`/`userupdate`, funzioni `function_on_insert`/`update`/`delete` (es.
  numero catastale automatico), aggiornamento della cache degli elenchi.

Versione inglese: [MCP_SERVER.md](MCP_SERVER.md).

## Autenticazione

Ogni richiesta contiene la chiave API in uno di questi header:

```
X-API-Key: <chiave>
Authorization: Bearer <chiave>
```

Alcuni hosting (es. Aruba) rimuovono l'header `Authorization` prima di PHP: in quel caso
usare `X-API-Key`. I client OAuth inviano solo `Authorization`: la regola che Finis scrive
nel `.htaccess` di solito lo ripristina.

### Utente e password (OAuth)

A Claude Desktop, claude.ai e agli altri client con OAuth basta l'URL del server: aprono il
browser, l'utente accede con utente e password e autorizza il client. Utenti e permessi
sono quelli delle chiavi personali descritte sotto.
Generare una nuova chiave, revocarla o cambiare la password scollega questi client;
`$_FN['openkis_mcp_userkeys'] = 0` rifiuta anche loro.

Il sito deve essere in HTTPS e l'header `Authorization` deve arrivare a PHP; un sito in una
cartella richiede anche due regole nel `.htaccess` della radice del dominio. Dettagli in
`finis/doc/it_guida_mcp.md`, sezione "Accesso con OAuth".

### Chiavi personali (consigliate)

- Ogni utente attivo genera la propria chiave in **Configurazione > Server MCP** o nella
  pagina del profilo; finis ne salva solo l'hash in `fn_users.apikey`, quindi la chiave si
  vede una volta sola (la stessa chiave vale per il server MCP del CMS Finis
  `index.php?fnapp=mcp`, vedi `finis/doc/it_guida_mcp.md`). Una nuova password revoca la
  chiave. `$_FN['apikey_groups']` ("" di default) limita le chiavi agli amministratori e
  ad alcuni gruppi.
- Di default un utente è in **sola lettura**. I tool di scrittura (`insert`, `update`,
  `upload`, `delete`) sono disponibili solo agli amministratori e agli utenti a cui un
  amministratore li ha consentiti: gruppo `mcp_write` (`$_FN['mcp_write_group']`), da
  **Pannello di controllo > Utenti e gruppi > Server MCP**, che gestisce anche le chiavi
  di tutti gli utenti.
- Entro questi limiti valgono le regole del sito:
  - solo le sezioni dbview che l'utente vede; nelle sezioni con permessi per record
    (`groupview`, `viewonlycreator`) solo i record che l'utente vede (es. grotte nascoste);
  - scrittura solo dove la sezione lo consente (`groupinsert`, `groupadmin`, autore del
    record, `fieldusers`);
  - sono leggibili le tabelle di decodifica usate dalle sezioni leggibili; `fn_users`,
    `*_versions` e le altre tabelle no;
  - lo strumento `sql`, i record cancellati e i filtri `where` con sottoquery sono
    riservati agli amministratori.

### Chiave globale (storica)

`$_FN['openkis_mcp_apikey']` (stringa o array) dà accesso completo; le modifiche sono
registrate a nome di `$_FN['openkis_mcp_user']`.

## La pagina "Server MCP"

**Configurazione > Server MCP** (`index.php?mod=openkis_mcp`, sezione `sections/openkis_mcp`):

- gli URL dei due server (record e CMS) e i metodi di autenticazione;
- la propria chiave: stato (data di creazione), **genera** (la chiave, il comando
  `claude mcp add` e la configurazione JSON per gli altri client si vedono una volta sola)
  e **revoca**;
- la tabella dei permessi effettivi della chiave sulle sezioni dei record; un
  amministratore può vedere i permessi di qualunque utente.

Le chiavi di tutti gli utenti e i permessi di scrittura si gestiscono nel pannello di
controllo di finis, **Utenti e gruppi > Server MCP** (`index.php?fnapp=controlcenter&opt=users_and_groups/mcp`):
utenti con chiave (data di creazione), generare una chiave per un utente (mostrata una
volta), revocarla, consentire i tool di scrittura o rimettere l'utente in sola lettura.

Icone della tabella dei permessi: 🛡️ lettura e modifica di tutti i record, ✏️ lettura,
inserimento e modifica dei record consentiti, 👁️ sola lettura, 🚫 nessun accesso,
🔒 solo i record visibili all'utente. Se l'accesso via MCP è inferiore a quello del sito
(utente senza permesso di scrittura) viene mostrato anche l'accesso sul sito.

Ogni modifica di chiave o di permesso di scrittura viene registrata nel log del sito
(contesto `apikey`).

## Configurazione

Valori di default in `extra/openkis_config.inc.php`, da sovrascrivere in
`extra/openkis_config.local.php`:

```php
$_FN['apikey_groups'] = "";                     // "" = ogni utente attivo può usare una chiave, oppure gruppi
$_FN['mcp_write_group'] = "mcp_write";          // utenti che possono usare i tool di scrittura (entrambi i server)
$_FN['openkis_mcp_apikey'] = "chiave-lunga";    // chiave/i storica/e facoltativa/e, accesso completo
$_FN['openkis_mcp_user'] = "admin";             // autore delle modifiche fatte con la chiave storica
$_FN['openkis_mcp_readonly'] = 0;               // 1 = nessun tool di scrittura per nessuno
$_FN['openkis_mcp_userkeys'] = 1;               // 0 = rifiuta le chiavi personali
```

Il gruppo `mcp_write` viene creato la prima volta che un amministratore apre le pagine del
server MCP.

## Tool

| Tool | Accesso | Scopo |
|---|---|---|
| `whoami` | tutti | utente della chiave, gruppi, livello di accesso, sola lettura |
| `sections` | tutti | sezioni dbview leggibili con la chiave e accesso (`admin`, `write`, `read`) |
| `tables` | tutti | tabelle leggibili |
| `schema` | tutti | campi di una tabella o sezione: tipo, flag, chiavi esterne, opzioni |
| `query` | tutti | record in CSV con `filter`, `where`, `order`, `limit`, `offset`, `count` |
| `get` | tutti | record completo per chiave primaria |
| `sql` | admin | SQL in sola lettura (SELECT/SHOW/DESCRIBE/EXPLAIN) |
| `insert` | scrittura | inserisce record (esegue le funzioni della sezione) |
| `update` | scrittura | modifica campi; la versione precedente va nello storico |
| `upload` | scrittura | imposta un campo file o immagine (base64, url o file di un altro record; crea la miniatura) |
| `delete` | scrittura | cancella record (o li nasconde nelle sezioni `hide_on_delete`) |

Con una chiave in sola lettura i tool di scrittura non compaiono in `tools/list` e, se
chiamati, vengono rifiutati.

## Configurazione del client

Claude Desktop e claude.ai: aggiungere un connettore personalizzato con l'URL
`https://<sito>/openkis_mcp.php`, poi accedere quando si apre il browser.

Claude Code con OAuth: `claude mcp add --transport http openkis https://<sito>/openkis_mcp.php`,
poi `/mcp` per accedere.

Claude Code con la chiave:

```bash
claude mcp add --transport http openkis https://<sito>/openkis_mcp.php --header "X-API-Key: <chiave>"
```

Altri client (JSON):

```json
{
  "mcpServers": {
    "openkis": {
      "type": "http",
      "url": "https://<sito>/openkis_mcp.php",
      "headers": { "X-API-Key": "<chiave>" }
    }
  }
}
```

Prova con curl:

```bash
curl -s https://<sito>/openkis_mcp.php -H "X-API-Key: <chiave>" \
  -H 'Accept: application/json, text/event-stream' -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"whoami","arguments":{}}}'
```

## Note di sicurezza

- La chiave è personale: chi la possiede agisce sul catasto come quell'utente. Revocarla
  dalla pagina del server MCP quando un client non serve più.
- Consentire la scrittura solo agli utenti che ne hanno bisogno: la sola lettura di default
  evita che un assistente modifichi i dati per errore.
- Usare HTTPS: la chiave o il token viaggiano in un header di ogni richiesta.

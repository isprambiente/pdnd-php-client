# pdnd-php-client

Client PHP per autenticazione e chiamata API PDND (Piattaforma Digitale Nazionale Dati).

## Licenza

MIT

## Requisiti

- PHP >= 8.1 (versioni precedenti fino alla 7.4 funzionano ma sono [EOL](https://endoflife.date/php))
- Composer
- Estensione cURL abilitata

## Installazione

1. Installa la libreria via composer:
   ```bash
   composer require isprambiente/pdnd-client
   ```

2. Configura il file JSON con i parametri richiesti (esempio in `configs/progetto.json`):
   ```json
    {
      "collaudo": {
        "kid": "kid",
        "issuer": "issuer",
        "clientId": "clientId",
        "purposeId": "purposeId",
        "privKeyPath": "/tmp/key.priv"
      },
      "produzione": {
        "kid": "kid",
        "issuer": "issuer",
        "clientId": "clientId",
        "purposeId": "purposeId",
        "privKeyPath": "/tmp/key.priv"
      }
    }
   ```
## Istruzioni

```php
require_once __DIR__ . '/vendor/autoload.php';
use Pdnd\Client\PdndClient;

// Istanzia la classe PdndClient
$client = new PdndClient();
// Definisci se vuoi vedere il debug
// Funzione opzionale. Default: false
$client->setDebug(true);
// Definisci se ti trovi in collaudo o produzione
// Funzione opzionale. Default: produzione
$client->setEnv("collaudo");
// Definisci il file di configurazione come indicato sopra.
// Se non indicato, è necessario indicare manualmente i vari parametri di configurazione
$client->config("/percorso/sample.json");
// Imposta l'url dell'API su PDND
$client->setApiUrl("https://api.pdnd.it/tuo/indirizzo/della/api");
// Richiedi il token alla PDND
$token = $client->requestToken();
// Richiama l'API
$result = $client->getApi($token);
// Visualizza il risultato
echo $result['body'] . "\n";
```
### Fulzioni aggiuntive

**Salva il token**

La funzione `$client->saveToken($token);` consente di memorizzare il token e non doverlo richiedere a ogni chiamata.

**Carica il token salvato**

La funzione `$client->loadToken();` consente di richiamare il token precedentemente salvato.

**Valida il token**

La funzione `$client->isTokenValid();` verifica la validità del token.

**Refresh token**

La funzione `$client->refreshToken();` effettua una nuova richiesta di token.
E' un alias di `$client->requestToken();`

## Utilizzo da CLI

Esegui il client dalla cartella principale:

```bash
php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" --config /percorso/assoluto/progetto.json
```

### Opzioni disponibili

- `-e`, `--env` : Specifica l'ambiente da usare (es. collaudo, produzione). Default: `produzione`
- `-c`, `--config` : Specifica il percorso completo del file di configurazione (es: `--config /percorso/assoluto/progetto.json`)
- `--debug` : Abilita output dettagliato
- `--api-url` : URL dell’API da chiamare dopo la generazione del token
- `--status-url` : URL dell’API di status per verificare la validità del token
- `--json`: Stampa le risposte delle API in formato JSON
- `--save`: Salva il token per evitare di richiederlo a ogni chiamata
- `--help`: Mostra questa schermata di aiuto

### Esempi

**Chiamata API generica:**
```bash
php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" --config /percorso/assoluto/progetto.json
```

**Verifica validità token:**
```bash
php bin/pdnd-client.php --status-url="https://api.pdnd.example.it/status" --config /percorso/assoluto/progetto.json
```

**Debug attivo:**
```bash
php bin/pdnd-client.php --debug --api-url="https://api.pdnd.example.it/resource"
```

### Opzione di aiuto

Se esegui il comando con `--help` oppure senza parametri, viene mostrata una descrizione delle opzioni disponibili e alcuni esempi di utilizzo:

```bash
php bin/pdnd-client.php --help
```

**Output di esempio:**
```
Utilizzo:
  php bin/pdnd-client.php -c /percorso/config.json [opzioni]

Opzioni:
  -e, --env         Specifica l'ambiente da usare (es. collaudo, produzione)
                    Default: produzione
  -c, --config      Specifica il percorso completo del file di configurazione
  --debug           Abilita output dettagliato
  --api-url         URL dell’API da chiamare dopo la generazione del token
  --status-url      URL dell’API di status per verificare la validità del token
  --json            Stampa le risposte delle API in formato JSON
  --save            Salva il token per evitare di richiederlo a ogni chiamata
  --help            Mostra questa schermata di aiuto

Esempi:
  php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" --config /percorso/config.json
  php bin/pdnd-client.php --status-url="https://api.pdnd.example.it/status" --config /percorso/config.json
  php bin/pdnd-client.php --debug --api-url="https://api.pdnd.example.it/resource"
```

## Variabili di ambiente supportate

Se un parametro non è presente nel file di configurazione, puoi definirlo come variabile di ambiente:

- `PDND_KID`
- `PDND_ISSUER`
- `PDND_CLIENT_ID`
- `PDND_PURPOSE_ID`
- `PDND_PRIVKEY_PATH`

## Note

- Il token viene salvato in un file temporaneo e riutilizzato finché è valido.
- Gli errori specifici vengono gestiti tramite la classe `PdndException`.

## Esempio di configurazione minima

```json
{
  "produzione": {
    "kid": "kid",
    "issuer": "issuer",
    "clientId": "clientId",
    "purposeId": "purposeId",
    "privKeyPath": "/tmp/key.pem"
  }
}
```
## Esempio di configurazione per collaudo e prosuzione

```json
{
  "collaudo": {
    "kid": "kid",
    "issuer": "issuer",
    "clientId": "clientId",
    "purposeId": "purposeId",
    "privKeyPath": "/tmp/key.pem"
  },
  "produzione": {
    "kid": "kid",
    "issuer": "issuer",
    "clientId": "clientId",
    "purposeId": "purposeId",
    "privKeyPath": "/tmp/key.pem"
  }
}
```
---

Per domande o suggerimenti, apri una issue!
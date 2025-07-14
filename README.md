# pdnd-php-client

Client PHP per autenticazione e chiamata API PDND (Piattaforma Digitale Nazionale Dati).

## Licenza

MIT

## Requisiti

- PHP >= 7.4
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

## Utilizzo da CLI

Esegui il client dalla cartella principale:

```bash
php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" --status-url="https://api.pdnd.example.it/status" -c /percorso/assoluto/progetto.json
```

### Opzioni disponibili

- `-e`, `--env` : Specifica l'ambiente da usare (es. collaudo, produzione). Default: `produzione`
- `-c`, `--config` : Specifica il percorso completo del file di configurazione (es: `-c /percorso/assoluto/progetto.json`)
- `--debug` : Abilita output dettagliato
- `--api-url` : URL dell’API da chiamare dopo la generazione del token
- `--status-url` : URL dell’API di status per verificare la validità del token
- `--json`: Stampa le risposte delle API in formato JSON
- `--save`: Salva il token per evitare di richiederlo a ogni chiamata
- `--help`: Mostra questa schermata di aiuto

### Esempi

**Chiamata API generica:**
```bash
php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" -c /percorso/assoluto/progetto.json
```

**Verifica validità token:**
```bash
php bin/pdnd-client.php --status-url="https://api.pdnd.example.it/status" -c /percorso/assoluto/progetto.json
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
  php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" -c /percorso/config.json
  php bin/pdnd-client.php --status-url="https://api.pdnd.example.it/status" -c /percorso/config.json
  php bin/pdnd-client.php --debug --api-url="https://api.pdnd.example.it/resource"
```

## Variabili di ambiente supportate

Se un parametro non è presente nel file di configurazione, puoi definirlo come variabile di ambiente:

- `PDND_KID`
- `PDND_ISSUER`
- `PDND_CLIENT_ID`
- `PDND_PURPOSE_ID`
- `PDND_PRIVKEY_PATH`
- `PDND_URL`

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
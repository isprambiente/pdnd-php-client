<?php
require __DIR__ . '/../vendor/autoload.php';
use Pdnd\PdndClient;
use Pdnd\PdndException;

// --- Lettura argomenti da riga di comando ---
$options = getopt("e:c:", ["env:", "config:", "debug", "pretty", "api-url:", "api-url-filters:", "status-url:", "help", "json", "save", "no-verify-ssl"]);
$env = $options["e"] ?? $options["env"] ?? "produzione";
$configPath = $options["c"] ?? $options["config"] ?? null;
$debug = isset($options["debug"]);
$pretty = isset($options["pretty"]);
$apiUrl = $options["api-url"] ?? null;
$filters = [];
if (!empty($options["api-url-filters"]) && is_string($options["api-url-filters"])) {
    parse_str($options["api-url-filters"], $filters);
}
$statusUrl = $options["status-url"] ?? null;
$jsonOutput = isset($options["json"]);
$save = isset($options["save"]);
$verifySSL = !isset($options["no-verify-ssl"]); // Disabilita verifica SSL per ambiente di collaudo

// --- Controllo configurazione ---
if (isset($options['help']) || $argc === 1) {
  echo <<<EOT
Utilizzo:
  php bin/pdnd-client.php -c /percorso/config.json [opzioni]

Opzioni:
  -e, --env         Specifica l'ambiente da usare (es. collaudo, produzione)
                    Default: produzione
  -c, --config      Specifica il percorso completo del file di configurazione
  --debug           Abilita output dettagliato
  --pretty          Abilita output dei json formattato per essere maggiormante leggibile
  --api-url         URL dell’API da chiamare dopo la generazione del token
  --api-url-filters Filtri da applicare all'API (es. ?parametro=valore)
  --status-url      URL dell’API di status per verificare la validità del token
  --json            Stampa le risposte delle API in formato JSON
  --save            Salva il token per evitare di richiederlo a ogni chiamata
  --no-verify-ssl   Disabilita la verifica SSL (utile per ambienti di collaudo)
  --help            Mostra questa schermata di aiuto

Esempi:
  php bin/pdnd-client.php --api-url="https://api.pdnd.example.it/resource" -c /percorso/config.json
  php bin/pdnd-client.php --status-url="https://api.pdnd.example.it/status" -c /percorso/config.json
  php bin/pdnd-client.php --debug --api-url="https://api.pdnd.example.it/resource"
  php bin/pdnd-client.php --pretty --api-url="https://api.pdnd.example.it/resource"

EOT;
  exit(0);
}

// --- Istanzia la classe PdndClient ---
$client = new PdndClient();
$client->setDebug($debug);
$client->setEnv($env);
$client->setVerifySSL($verifySSL); // Disabilita verifica SSL per ambiente di collaudo

// --- Caricamento configurazione se presente ---
if ($configPath) {
  $client->config($configPath);
}

// --- Richiesta del token ---
if ($save) {
  $token = $client->loadToken(); // Prova a caricare il token salvato
} else {
  $token = null;
  $file = sys_get_temp_dir() . "/pdnd_token_".$env.".json";
  if (file_exists($file)) unlink($file);
}

try {
  if (!$token || !$client->isTokenValid()) {
    $token = $client->requestToken();
  }
  if ($token) {
    if ($save) $client->saveToken($token);
    if ($statusUrl) {
      try {
        $client->setStatusUrl($statusUrl);
        $status = $client->getStatus($token);
        if ($debug || $pretty) {
          echo "\n✅ Response status:\n";
          echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
          echo json_encode($status, 0 | JSON_UNESCAPED_UNICODE);
        }
      } catch (PdndException $e) {
        echo $jsonOutput
            ? json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n"
            : "Errore Status API: " . $e->getMessage() . "\n";
        exit(1);
      }
    }
    if ($apiUrl) {
      $client->setApiUrl($apiUrl);
      $client->setFilters($filters ?? []); // Imposta i filtri se presenti
      $result = $client->getApi($token);
      $body = $result['body'];
      if ($debug) {
        echo "\n🌐 Chiamata API ($apiUrl):\n";
        echo "Status: {$result['status']}\n";
        echo "Body: \n";
      }
      if ($debug || $pretty) {
        $decoded = json_decode($body, true);
        $body = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      }
      echo $body . "\n";
    } else {
      echo "$token\n";
    }
    exit(0);
  } else {
    echo "⚠️ Nessun access token trovato.\n";
    exit(1);
  }
} catch (PdndException $e) {
  echo "Errore PDND: " . $e->getMessage() . "\n";
  exit(1);
} catch (Exception $e) {
  echo "Errore generico: " . $e->getMessage() . "\n";
  exit(1);
}

// Esempio di utilizzo:
// php bin/pdnd-client.php -c /mnt/c/Users/francesco.loreti/sviluppo/pdnd-client/php/configs/rendis.json --api-url="https://pdnd-test.isprambiente.it/rest/rendis/v1/oas/rendis/api/v1/infoDissesto?id_intervento=001/B2" --status-url="https://pdnd-test.isprambiente.it/rest/rendis/v1/status" --debug -e collaudo
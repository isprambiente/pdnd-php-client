<?php
/**
 * @package Pdnd\Client
 * @name PdndClient
 * @license MIT
 * @file PdndClient.php
 * @brief Classe per interagire con l'API PDND (Piattaforma Digitale Nazionale dei Dati).
 * @author Francesco Loreti
 * @mailto francesco.loreti@isprambiente.it
 * @first_release 2025-07-13
 */

use PdndException;
use Exception;
use DateTime;
use DateTimeZone;
use Firebase\JWT\JWT;

/**
 * Classe per interagire con l'API PDND (Piattaforma Digitale Nazionale dei Dati).
 * Questa classe gestisce la configurazione, la richiesta di token e le chiamate API.
 */
class PdndClient
{
  /**
   * @var string $kid Il Key ID del client.
   * @var string $issuer L'issuer del client.
   * @var string $clientId L'ID del client.
   * @var string $purposeId L'ID dello scopo per cui viene richiesto il token.
   * @var string $privKeyPath Il percorso della chiave privata in formato PEM.
   * @var bool $debug Abilita o disabilita la modalità di debug.
   * @var string $url L'URL dell'API da richiamare.
   * @var string|null $apiUrl L'URL dell'API da richiamare (opzionale).
   * @var string|null $statusUrl L'URL per verificare lo stato del token (opzionale).
   * @var int|null $tokenExp La data di scadenza del token (timestamp).
   * @var string $env L'ambiente dell'API PDND (default: "produzione").
   * @var string $endpoint L'endpoint per la richiesta del token.
   * @var string $aud L'audience per il token JWT.
   * @var string $tokenFile Il percorso del file in cui salvare il token.
   * @var bool $sslValidation Abilita o disabilita la verifica SSL (default: true).
   * @var string $dateTimeZone Il fuso orario da utilizzare per le date (default: 'Europe/Rome').
   * @var array $filters Array per i filtri personalizzati da applicare alle chiamate API.
   */

  private $kid;
  private $issuer;
  private $clientId;
  private $purposeId;
  private $privKeyPath;
  private $debug = false;
  private $url;
  private $apiUrl = null;
  private $statusUrl = null;
  private $tokenExp = null;
  private $env = "produzione";
  private $endpoint = "https://auth.interop.pagopa.it/token.oauth2";
  private $aud = "auth.interop.pagopa.it/client-assertion";
  private $tokenFile = "";
  private $verifySSL = true; // Default verifica SSL abilitata
  private $dateTimeZone = 'Europe/Rome'; // Default timezone
  private $filters = []; // Array per i filtri personalizzati

  // -- Setters --
  public function setApiUrl(string $apiUrl) { $this->apiUrl = $apiUrl; }
  public function setAud(string $aud) { $this->aud = $aud; }
  public function setClientId(string $clientId) { $this->clientId = $clientId; }
  public function setDebug(bool $debug) { $this->debug = $debug; }
  public function setEndpoint(string $endpoint) { $this->endpoint = $endpoint; }

  /**
   * Imposta l'ambiente dell'API PDND.
   * @param string $env L'ambiente da impostare (default: "produzione").
   * Se l'ambiente è "collaudo", imposta l'endpoint e l'aud specifici.
   * Altrimenti, usa i valori predefiniti per produzione.
  */
  public function setEnv(string $env = "produzione") {
    $this->env = $env;
    if ($env === "collaudo") {
      $this->endpoint = "https://auth.uat.interop.pagopa.it/token.oauth2";
      $this->aud = "auth.uat.interop.pagopa.it/client-assertion";
    }
  }
  public function setFilters(array $filters) { $this->filters = $filters; }
  public function setKid(string $kid) { $this->kid = $kid; }
  public function setIssuer(string $issuer) { $this->issuer = $issuer; }
  public function setPurposeId(string $purposeId) { $this->purposeId = $purposeId; }
  public function setPrivKeyPath(string $privKeyPath) { $this->privKeyPath = $privKeyPath; }
  public function setStatusUrl(string $statusUrl) { $this->statusUrl = $statusUrl; }
  public function setVerifySSL(string $verifySSL) { $this->verifySSL = $verifySSL; }

  // -- Getters --
  public function getApiUrl() { return $this->apiUrl ?? $this->prompt("apiUrl", "Inserisci l'url dell'API che vuoi richiamare dall'API PDND"); }
  public function getAud() { return $this->aud ?? $this->prompt("aud", "Inserisci l'aud dell'API PDND"); }
  public function getClientId() { return $this->clientId ?? $this->prompt("clientId", "Inserisci il clientId"); }
  public function getEndpoint() { return $this->endpoint ?? $this->prompt("endpoint", "Inserisci l'endpoint dell'API PDND"); }
  public function getEnv() { return $this->env ?? $this->prompt("env", "Inserisci l'enviroment dell'API PDND"); }
  public function getFilters() { return $this->filters; }
  public function getKid() { return $this->kid ?? $this->prompt("kid", "Inserisci il kid"); }
  public function getIssuer() { return $this->issuer ?? $this->prompt("issuer", "Inserisci l'issuer"); }
  public function getPurposeId() { return $this->purposeId ?? $this->prompt("purposeId", "Inserisci il purposeId"); }
  public function getPrivKeyPath() { return $this->privKeyPath ?? $this->prompt("privKeyPath", "Inserisci il path della chiave privata"); }
  public function getStatusUrl() { return $this->statusUrl ?? $this->prompt("statusUrl", "Inserisci l'url dell'API per lo status"); }
  public function getVerifySSL() { return $this->verifySSL; }

  /**
   * Carica la configurazione da un file JSON.
   * @param string|null $configPath Il percorso del file di configurazione. Se non specificato, usa le variabili di ambiente.
   * @throws PdndException Se il file di configurazione non esiste o se l'ambiente non è trovato.
   */
  public function config(string $configPath = null)
  {
    $config = [];

    // Se il percorso del file di configurazione è specificato, carica la configurazione da quel file
    if ($configPath && file_exists($configPath)) {
      $allEnvs = json_decode(file_get_contents($configPath), true);
      if (!isset($allEnvs[$this->env])) {
          throw new PdndException("Errore: environment '$this->env' non trovato nel file di configurazione.");
      }
      $config = $allEnvs[$this->env];
    }

    // Carica i parametri dalle variabili di ambiente se non sono già impostati
    $this->kid = $config['kid'] ?? getenv('PDND_KID');
    $this->issuer = $config['issuer'] ?? getenv('PDND_ISSUER');
    $this->clientId = $config['clientId'] ?? getenv('PDND_CLIENT_ID');
    $this->purposeId = $config['purposeId'] ?? getenv('PDND_PURPOSE_ID');
    $this->privKeyPath = $config['privKeyPath'] ?? getenv('PDND_PRIVKEY_PATH');
    $this->tokenFile = sys_get_temp_dir() . "/pdnd_token_" . $this->purposeId . ".json";

    $this->validateUrl($this->apiUrl);
    $this->validateUrl($this->statusUrl);
    $this->validateConfig();
  }

  /**
   * Effettua una chiamata API con il token fornito.
   * @param string $token Il token da utilizzare per l'autenticazione.
   * @return array La risposta dell'API.
   * @throws PdndException Se la risposta JSON non è valida o se si verifica un errore nella chiamata API.
   */
  public function getApi(string $token)
  {
    $url = $this->apiUrl ?? $this->getApiUrl();
    if ($this->filters) {
      $query = http_build_query($this->filters);
      $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: */*"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$response) {
      throw new PdndException("❌ Errore nella chiamata API: " . curl_error($ch));
    }
    if ($this->debug) {
      $decoded = json_decode($response, true);
      $response = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    return [
      'status' => $statusCode,
      'body' => $response
    ];
  }

  /**
   * Verifica lo stato del token.
   * @param string $token Il token da verificare.
   * @return array Il risultato della verifica dello stato del token.
   * @throws PdndException Se la risposta JSON non è valida o se il token non è valido.
   */
  public function getStatus(string $token)
  {
    $statusUrl = $this->statusUrl ?? $this->getStatusUrl();
    $ch = curl_init($statusUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: */*"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode >= 200 && $statusCode < 300) {
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json['status'])) {
            if ($this->debug) echo "\n✅ Token valido. Stato: {$json['status']}\n";
            return $json;
        } else {
            if ($this->debug) echo "\n⚠️ Risposta JSON non valida o chiave 'status' mancante.\n";
            throw new PdndException("⚠️ Risposta JSON non valida o chiave 'status' mancante.");
        }
    } else {
        if ($this->debug) echo "\n❌ Errore nella chiamata. Codice: $statusCode\nRisposta: $response\n";
        throw new PdndException("❌ Errore nella chiamata. Codice: $statusCode\nRisposta: $response\n");
    }
  }

  /**
   * Verifica se il token è ancora valido.
   * @return bool True se il token è valido, altrimenti false.
   */
  public function isTokenValid(): bool
  {
    if (!$this->tokenExp) return false;
    return time() < $this->tokenExp;
  }

  /**
   * Carica il token da un file.
   * @param string|null $file Il percorso del file da cui caricare il token. Se non specificato, usa il percorso predefinito.
   * @return string|null Il token se caricato con successo, altrimenti null.
   */
  public function loadToken(string $file = null)
  {
    $file = $file ?? $this->tokenFile;
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    if (!$data || !isset($data['token'], $data['exp'])) return null;
    $this->tokenExp = $data['exp'];
    return $data['token'];
  }

  /**
   * Richiede un nuovo token, se il token corrente è scaduto o non esiste.
   * @return string Il nuovo token.
   * @throws PdndException Se si verifica un errore durante la richiesta del token.
   */
  public function refreshToken()
  {
    return $this->requestToken(); // Richiama la funzione per ottenere un nuovo token
  }

  /**
   * Richiede un nuovo token utilizzando le credenziali fornite.
   * @return string Il token ottenuto.
   * @return false Se non è stato possibile ottenere un token.
   * @throws PdndException Se uno dei campi obbligatori non è impostato.
   * @throws PdndException Se l'URL non è raggiungibile o non valido.
   * @throws PdndException Se la generazione del token JWT fallisce.
   * @throws PdndException Se la risposta JSON non è valida o se non contiene un access token.
   * @throws PdndException Se si verifica un errore durante la richiesta del token.
   * @throws PdndException Se si verifica un errore durante la generazione del token.
   */
  public function requestToken()
  {
    $kid = $this->getKid();
    $issuer = $this->getIssuer();
    $clientId = $this->getClientId();
    $purposeId = $this->getPurposeId();
    $privKeyPath = $this->getPrivKeyPath();
    $endpoint = $this->getEndpoint();
    $aud = $this->getAud();
    $subject = $issuer;

    $rsaKey = file_get_contents($privKeyPath);

    $issuedAt = time();
    $expirationTime = $issuedAt + (43200 * 60); // 30 giorni
    $jti = bin2hex(random_bytes(16));

    $payload = [
      "iss" => $issuer,
      "sub" => $subject,
      "aud" => $aud,
      "purposeId" => $purposeId,
      "jti" => $jti,
      "iat" => $issuedAt,
      "exp" => $expirationTime
    ];

    $headers = [
      "kid" => $kid,
      "alg" => "RS256",
      "typ" => "JWT"
    ];

    try {
      $clientAssertion = JWT::encode($payload, $rsaKey, 'RS256', null, $headers);
    } catch (Exception $e) {
      throw new PdndException("❌ Errore durante la generazione del client_assertion JWT:\n" . $e->getMessage());
    }

    if ($this->debug) {
      echo "\n✅ Enviroment: $this->env \n";
      echo "\n✅ Client assertion generato con successo.\n";
      echo "\n📄 JWT (client_assertion):\n$clientAssertion\n";
    }

    $ch = curl_init($endpoint);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "client_id" => $clientId,
        "client_assertion" => $clientAssertion,
        "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
        "grant_type" => "client_credentials"
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode === 200) {
      $json = json_decode($response, true);
      $accessToken = $json["access_token"] ?? null;
      if ($accessToken) {
        // Decodifica il JWT per estrarre la scadenza
        $parts = explode('.', $accessToken);
        if (count($parts) === 3) {
          $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
          $this->tokenExp = $payload['exp'] ?? null;
        }
        if ($this->debug) {
          if (isset($this->tokenExp)) {
            $dt = new DateTime("@$this->tokenExp", new DateTimeZone($this->dateTimeZone));
            $dt->setTimezone(new DateTimeZone($this->dateTimeZone)); // Apply your desired timezone
            $tokenExp = $dt->format('Y-m-d H:i:s');
          } else {
            $tokenExp = 'non disponibile';
          }
          echo "\n🔐 Access Token:\n$accessToken\n";
          echo "\n⏰ Scadenza token (exp): " . $tokenExp . "\n";
        }
        return $accessToken;
      } else {
        throw new PdndException("⚠️ Nessun access token trovato:\n" . json_encode($json, JSON_PRETTY_PRINT));
      }
    }
    return false; // Se non è stato possibile ottenere un token, ritorna false
  }

  /**
   * Salva il token in un file.
   * @param string $token Il token da salvare.
   * @param string|null $file Il percorso del file in cui salvare il token. Se non specificato, usa il percorso predefinito.
   */
  public function saveToken(string $token, string $file = null)
  {
    $file = $file ?? $this->tokenFile;
    $data = [
      'token' => $token,
      'exp' => $this->tokenExp
    ];
    file_put_contents($file, json_encode($data));
  }

  /**
   * Verifica se l'URL è raggiungibile e valido.
   * @param string $url L'URL da validare.
   * @return bool True se l'URL è valido, altrimenti lancia un'eccezione.
   * @throws PdndException Se l'URL non è raggiungibile o non valido.
   */
  public function validateUrl($url) {
    if (empty($url)) return false;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Evita di scaricare il contenuto
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout di 10 secondi
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 400) {
      throw new PdndException("Errore: URL non raggiungibile o non valida.", 1002);
    }
    return true;
  }

  // -- Private functions --
  /**
   * Chiede all'utente di inserire un valore per un campo obbligatorio.
   * @param string $key La chiave del campo.
   * @param string $message Il messaggio da mostrare all'utente.
   * @return string Il valore inserito dall'utente.
   * @throws PdndException Se l'utente non inserisce un valore.
   */
  private function prompt(string $key, string $message)
  {
    // if ($this->debug) {
      echo "$message: ";
    // }
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    if (!$line) {
      throw new PdndException("Errore: il campo '$key' è obbligatorio.");
    }
    return $line;
  }

  /**
   * Verifica che tutti i campi obbligatori siano stati impostati.
   * @throws PdndException Se uno dei campi obbligatori è mancante.
   */
  private function validateConfig()
  {
    $required = ['kid', 'issuer', 'clientId', 'purposeId', 'privKeyPath'];
    foreach ($required as $key) {
      if (empty($this->$key)) {
        throw new PdndException("Configurazione mancante: '$key' è obbligatorio.", 1001);
      }
    }
  }
}
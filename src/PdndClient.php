<?php
require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

class PdndClient
{
  private $kid;
  private $issuer;
  private $clientId;
  private $purposeId;
  private $privKeyPath;
  private $debug = false;
  private $url;
  private $tokenExp = null;
  private $env = "produzione";
  private $endpoint = "https://auth.interop.pagopa.it/token.oauth2";
  private $aud = "auth.interop.pagopa.it/client-assertion";
  private $tokenFile = "";

  public function __construct()
  {
    $this->tokenFile = sys_get_temp_dir() . "/pdnd_token_" . $this->purposeId . ".json";
  }

  public function setDebug(bool $debug) { $this->debug = $debug; }
  public function setKid(string $kid) { $this->kid = $kid; }
  public function setIssuer(string $issuer) { $this->issuer = $issuer; }
  public function setClientId(string $clientId) { $this->clientId = $clientId; }
  public function setPurposeId(string $purposeId) { $this->purposeId = $purposeId; }
  public function setPrivKeyPath(string $privKeyPath) { $this->privKeyPath = $privKeyPath; }
  public function setUrl(string $url) { $this->url = $url; }
  public function setEnv(string $env) { $this->env = $env; }
  public function setEndpoint(string $endpoint) { $this->endpoint = $endpoint; }
  public function setAud(string $aud) { $this->aud = $aud; }

  public function getKid() { return $this->kid ?? $this->prompt("kid", "Inserisci il kid"); }
  public function getIssuer() { return $this->issuer ?? $this->prompt("issuer", "Inserisci l'issuer"); }
  public function getClientId() { return $this->clientId ?? $this->prompt("clientId", "Inserisci il clientId"); }
  public function getPurposeId() { return $this->purposeId ?? $this->prompt("purposeId", "Inserisci il purposeId"); }
  public function getPrivKeyPath() { return $this->privKeyPath ?? $this->prompt("privKeyPath", "Inserisci il path della chiave privata"); }
  public function getUrl() { return $this->url ?? $this->prompt("url", "Inserisci la URL dell'API PDND"); }
  public function getEnv() { return $this->env ?? $this->prompt("env", "Inserisci l'enviroment dell'API PDND"); }
  public function getEndpoint() { return $this->endpoint ?? $this->prompt("endpoint", "Inserisci l'endpoint dell'API PDND"); }
  public function getAud() { return $this->aud ?? $this->prompt("aud", "Inserisci l'aud dell'API PDND"); }

  private function validateConfig()
  {
    $required = ['kid', 'issuer', 'clientId', 'purposeId', 'privKeyPath'];
    foreach ($required as $key) {
      if (empty($this->$key)) {
        throw new PdndException("Configurazione mancante: '$key' è obbligatorio.", 1001);
      }
    }
  }

  public function config(string $configPath = null)
  {
    $config = [];

    // Se esiste il file di configurazione, carica i parametri
    if ($configPath && file_exists($configPath)) {
      $allEnvs = json_decode(file_get_contents($configPath), true);
      if (!isset($allEnvs[$this->env])) {
          throw new PdndException("Errore: environment '$this->env' non trovato nel file di configurazione.");
      }
      $config = $allEnvs[$this->env];
    }

    // Fallback su variabili di ambiente se non presenti nel file
    $this->kid = $config['kid'] ?? getenv('PDND_KID');
    $this->issuer = $config['issuer'] ?? getenv('PDND_ISSUER');
    $this->clientId = $config['clientId'] ?? getenv('PDND_CLIENT_ID');
    $this->purposeId = $config['purposeId'] ?? getenv('PDND_PURPOSE_ID');
    $this->privKeyPath = $config['privKeyPath'] ?? getenv('PDND_PRIVKEY_PATH');
    $this->url = $config['url'] ?? getenv('PDND_URL');
    $this->url = $config['env'] ?? getenv('PDND_ENV');

    $this->validateConfig();
  }

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

  public function requestToken()
  {
    $kid = $this->getKid();
    $issuer = $this->getIssuer();
    $clientId = $this->getClientId();
    $purposeId = $this->getPurposeId();
    $privKeyPath = $this->getPrivKeyPath();
    $endpoint = $this->getEndpoint();
    $aud = $this->getAud();
    if ($this->env === "collaudo") {
      $endpoint = "https://auth.uat.interop.pagopa.it/token.oauth2";
      $aud = "auth.uat.interop.pagopa.it/client-assertion";
    }
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
          echo "\n🔐 Access Token:\n$accessToken\n";
          echo "\n⏰ Scadenza token (exp): " . ($this->tokenExp ? date('Y-m-d H:i:s', $this->tokenExp) : 'non disponibile') . "\n";
        }
        return $accessToken;
      } else {
        throw new PdndException("⚠️ Nessun access token trovato:\n" . json_encode($json, JSON_PRETTY_PRINT));
      }
    }
  }

  // Chiamata generica API con Bearer token
  public function getApi(string $token, string $apiUrl = null, $verifySSL = true)
  {
    $url = $apiUrl ?? $this->getUrl();
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: */*"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
      'status' => $statusCode,
      'body' => $response
    ];
  }

  // Verifica validità token su API di status
  public function getStatus(string $token, string $statusUrl, $verifySSL = true)
  {
      $ch = curl_init($statusUrl);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Authorization: Bearer $token",
          "Accept: */*"
      ]);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);

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

  public function isTokenValid(): bool
  {
    if (!$this->tokenExp) return false;
    return time() < $this->tokenExp;
  }

  public function saveToken(string $token, string $file = null)
  {
    $file = $file ?? $this->tokenFile;
    $data = [
      'token' => $token,
      'exp' => $this->tokenExp
    ];
    file_put_contents($file, json_encode($data));
  }

  public function loadToken(string $file = null)
  {
    $file = $file ?? $this->tokenFile;
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    if (!$data || !isset($data['token'], $data['exp'])) return null;
    $this->tokenExp = $data['exp'];
    return $data['token'];
  }
}
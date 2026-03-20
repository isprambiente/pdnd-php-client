<?php

namespace Pdnd;

class PdndConfig
{
    public string $kid;
    public string $issuer;
    public string $clientId;
    public string $purposeId;
    public string $privKeyPath;
    public bool $debug = false;
    public ?string $apiUrl = null;
    public ?string $statusUrl = null;
    public ?int $tokenExp = null;
    public string $env = "produzione";
    public string $endpoint = "https://auth.interop.pagopa.it/token.oauth2";
    public string $aud = "auth.interop.pagopa.it/client-assertion";
    public string $tokenFile = "";
    public bool $verifySSL = true;
    public string $dateTimeZone = 'Europe/Rome';
    /** @var array<string,mixed> */
    public array $filters = [];

    // Setters
    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }

    public function setAud(string $aud): void
    {
        $this->aud = $aud;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function setEnv(string $env = "produzione"): void
    {
        $this->env = $env;
        if ($env === "attestazione") {
            $this->endpoint = "https://auth.att.interop.pagopa.it/token.oauth2";
            $this->aud = "auth.att.interop.pagopa.it/client-assertion";
        } elseif ($env === "collaudo") {
            $this->endpoint = "https://auth.uat.interop.pagopa.it/token.oauth2";
            $this->aud = "auth.uat.interop.pagopa.it/client-assertion";
        } else {
            $this->endpoint = "https://auth.interop.pagopa.it/token.oauth2";
            $this->aud = "auth.interop.pagopa.it/client-assertion";
        }
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function setKid(string $kid): void
    {
        $this->kid = $kid;
    }

    public function setIssuer(string $issuer): void
    {
        $this->issuer = $issuer;
    }

    public function setPurposeId(string $purposeId): void
    {
        $this->purposeId = $purposeId;
    }

    public function setPrivKeyPath(string $privKeyPath): void
    {
        $this->privKeyPath = $privKeyPath;
    }

    public function setStatusUrl(string $statusUrl): void
    {
        $this->statusUrl = $statusUrl;
    }

    public function setVerifySSL(bool $verifySSL): void
    {
        $this->verifySSL = $verifySSL;
    }

    // Getters
    public function getApiUrl(): string
    {
        return $this->apiUrl ?? $this->prompt("apiUrl", "Inserisci l'url dell'API che vuoi richiamare dall'API PDND");
    }

    public function getAud(): string
    {
        return $this->aud ?? $this->prompt("aud", "Inserisci l'aud dell'API PDND");
    }

    public function getClientId(): string
    {
        return $this->clientId ?? $this->prompt("clientId", "Inserisci il clientId");
    }

    public function getEndpoint(): string
    {
        return $this->endpoint ?? $this->prompt("endpoint", "Inserisci l'endpoint dell'API PDND");
    }

    public function getEnv(): string
    {
        return $this->env ?? $this->prompt("env", "Inserisci l'enviroment dell'API PDND");
    }

    /**
     * @return array<string,mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getKid(): string
    {
        return $this->kid ?? $this->prompt("kid", "Inserisci il kid");
    }

    public function getIssuer(): string
    {
        return $this->issuer ?? $this->prompt("issuer", "Inserisci l'issuer");
    }

    public function getPurposeId(): string
    {
        return $this->purposeId ?? $this->prompt("purposeId", "Inserisci il purposeId");
    }

    public function getPrivKeyPath(): string
    {
        return $this->privKeyPath ?? $this->prompt("privKeyPath", "Inserisci il path della chiave privata");
    }

    public function getStatusUrl(): string
    {
        return $this->statusUrl ?? $this->prompt("statusUrl", "Inserisci l'url dell'API per lo status");
    }

    public function getVerifySSL(): bool
    {
        return $this->verifySSL;
    }

    public function config(?string $configPath = null): void
    {
        $config = [];
        if ($configPath && file_exists($configPath)) {
            $allEnvs = json_decode(file_get_contents($configPath), true);
            if (!isset($allEnvs[$this->env])) {
                throw new PdndException("Errore: environment '$this->env' non trovato nel file di configurazione.");
            }
            $config = $allEnvs[$this->env];
        }

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

    public function validateUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 400) {
            throw new PdndException("Errore: URL non raggiungibile o non valida.", 1002);
        }
        return true;
    }

    private function prompt(string $key, string $message): string
    {
        echo "$message: ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        if (!$line) {
            throw new PdndException("Errore: il campo '$key' è obbligatorio.");
        }
        return $line;
    }

    private function validateConfig(): void
    {
        $required = ['kid', 'issuer', 'clientId', 'purposeId', 'privKeyPath'];
        foreach ($required as $key) {
            if (empty($this->$key)) {
                throw new PdndException("Configurazione mancante: '$key' è obbligatorio.", 1001);
            }
        }
    }
}

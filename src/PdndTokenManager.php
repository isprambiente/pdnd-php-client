<?php

namespace Pdnd;

use Exception;
use DateTime;
use DateTimeZone;
use Firebase\JWT\JWT;

class PdndTokenManager
{
    private PdndConfig $config;

    public function __construct(PdndConfig $config)
    {
        $this->config = $config;
    }

    public function isTokenValid(): bool
    {
        if (!$this->config->tokenExp) {
            return false;
        }
        return time() < $this->config->tokenExp;
    }

    public function loadToken(?string $file = null): ?string
    {
        $file = $file ?? $this->config->tokenFile;
        if (!file_exists($file)) {
            return null;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['token'], $data['exp'])) {
            return null;
        }
        $this->config->tokenExp = $data['exp'];
        return $data['token'];
    }

    public function refreshToken(): string|false
    {
        return $this->requestToken();
    }

    public function requestToken(): string|false
    {
        $kid = $this->config->getKid();
        $issuer = $this->config->getIssuer();
        $clientId = $this->config->getClientId();
        $purposeId = $this->config->getPurposeId();
        $privKeyPath = $this->config->getPrivKeyPath();
        $endpoint = $this->config->getEndpoint();
        $aud = $this->config->getAud();
        $subject = $issuer;

        $rsaKey = file_get_contents($privKeyPath);

        $issuedAt = time();
        $expirationTime = $issuedAt + (43200 * 60);
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

        if ($this->config->debug) {
            echo "\n✅ Enviroment: {$this->config->env} \n";
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

        if ($statusCode === 200) {
            $json = json_decode($response, true);
            $accessToken = $json["access_token"] ?? null;
            if ($accessToken) {
                $parts = explode('.', $accessToken);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    $this->config->tokenExp = $payload['exp'] ?? null;
                }
                if ($this->config->debug) {
                    if (isset($this->config->tokenExp)) {
                        $dt = new DateTime("@{$this->config->tokenExp}", new DateTimeZone($this->config->dateTimeZone));
                        $dt->setTimezone(new DateTimeZone($this->config->dateTimeZone));
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
        return false;
    }

    public function saveToken(string $token, ?string $file = null): void
    {
        $file = $file ?? $this->config->tokenFile;
        $data = [
            'token' => $token,
            'exp' => $this->config->tokenExp
        ];
        file_put_contents($file, json_encode($data));
    }
}

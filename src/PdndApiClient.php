<?php

namespace Pdnd;

class PdndApiClient
{
    private PdndConfig $config;

    public function __construct(PdndConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array<string,mixed>
     */
    public function getApi(string $token): array
    {
        $url = $this->config->apiUrl ?? $this->config->getApiUrl();
        if ($this->config->filters) {
            $query = http_build_query($this->config->filters);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: */*"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verifySSL);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!$response) {
            throw new PdndException("❌ Errore nella chiamata API: " . curl_error($ch));
        }
        if ($this->config->debug) {
            $decoded = json_decode($response, true);
            $response = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return [
            'status' => $statusCode,
            'body' => $response
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getStatus(string $token): array
    {
        $statusUrl = $this->config->statusUrl ?? $this->config->getStatusUrl();
        $ch = curl_init($statusUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: */*"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verifySSL);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode >= 200 && $statusCode < 300) {
            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['status'])) {
                if ($this->config->debug) {
                    echo "\n✅ Token valido. Stato: {$json['status']}\n";
                }
                return $json;
            } else {
                if ($this->config->debug) {
                    echo "\n⚠️ Risposta JSON non valida o chiave 'status' mancante.\n";
                }
                throw new PdndException("⚠️ Risposta JSON non valida o chiave 'status' mancante.");
            }
        } else {
            if ($this->config->debug) {
                echo "\n❌ Errore nella chiamata. Codice: $statusCode\nRisposta: $response\n";
            }
            throw new PdndException("❌ Errore nella chiamata. Codice: $statusCode\nRisposta: $response\n");
        }
    }
}

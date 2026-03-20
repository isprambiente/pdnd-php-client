<?php

/**
 * @package Pdnd
 * @name PdndClient
 * @license MIT
 * @file PdndClient.php
 * @brief Classe per interagire con l'API PDND (Piattaforma Digitale Nazionale dei Dati).
 * @author Francesco Loreti
 * @mailto francesco.loreti@isprambiente.it
 * @first_release 2025-07-13
 */

namespace Pdnd;

/**
 * Classe per interagire con l'API PDND (Piattaforma Digitale Nazionale dei Dati).
 * Questa classe gestisce la configurazione, la richiesta di token e le chiamate API.
 */
class PdndClient
{
    private PdndConfig $config;
    private PdndTokenManager $tokenManager;
    private PdndApiClient $apiClient;

    public function __construct(?PdndConfig $config = null)
    {
        $this->config = $config ?? new PdndConfig();
        $this->tokenManager = new PdndTokenManager($this->config);
        $this->apiClient = new PdndApiClient($this->config);
    }

    public function getConfig(): PdndConfig
    {
        return $this->config;
    }

    // Proxy setters to config
    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this->config, $name)) {
            return $this->config->$name(...$arguments);
        }
        throw new PdndException("Metodo non trovato: $name");
    }

    // Configuration
    public function config(?string $configPath = null): void
    {
        $this->config->config($configPath);
    }

    // Token management
    public function requestToken(): string|false
    {
        return $this->tokenManager->requestToken();
    }

    public function refreshToken(): string|false
    {
        return $this->tokenManager->refreshToken();
    }

    public function loadToken(?string $file = null): ?string
    {
        return $this->tokenManager->loadToken($file);
    }

    public function saveToken(string $token, ?string $file = null): void
    {
        $this->tokenManager->saveToken($token, $file);
    }

    public function isTokenValid(): bool
    {
        return $this->tokenManager->isTokenValid();
    }

    // API calls
    /**
     * @return array<string,mixed>
     */
    public function getApi(string $token): array
    {
        return $this->apiClient->getApi($token);
    }

    /**
     * @return array<string,mixed>
     */
    public function getStatus(string $token): array
    {
        return $this->apiClient->getStatus($token);
    }
}

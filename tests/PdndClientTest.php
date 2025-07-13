<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/PdndClient.php';
require_once __DIR__ . '/../src/PdndException.php';

class PdndClientTest extends TestCase
{
    public function testConfigMissingFile()
    {
        $client = new PdndClient();
        $this->expectException(PdndException::class);
        $client->config('/path/invalido/config.json');
    }

    public function testValidateConfigThrowsException()
    {
        $client = new PdndClient();
        // Non settiamo nessun parametro, deve lanciare PdndException
        $this->expectException(PdndException::class);
        $client->config(null);
    }

    public function testConfigValidFile()
    {
        $client = new PdndClient();
        $configPath = __DIR__ . '/../configs/sample.json';
        $client->config($configPath);
        $this->assertNotEmpty($client->getKid());
        $this->assertEquals('kid', $client->getKid()); // Adatta il valore atteso in base al contenuto di sample.json
        $client->setKid('');
        $this->assertEmpty($client->getKid());
    }

    public function testSetAndGet()
    {
        $client = new PdndClient();
        $client->setKid('kid');
        $client->setIssuer('issuer');
        $client->setClientId('clientId');
        $client->setPurposeId('purposeId');
        $client->setPrivKeyPath('/tmp/key.pem');
        $this->assertEquals('kid', $client->getKid());
        $this->assertEquals('issuer', $client->getIssuer());
        $this->assertEquals('clientId', $client->getClientId());
        $this->assertEquals('purposeId', $client->getPurposeId());
        $this->assertEquals('/tmp/key.pem', $client->getPrivKeyPath());
    }

    public function testIsTokenValidFalse()
    {
        $client = new PdndClient();
        $reflection = new ReflectionClass($client);
        $prop = $reflection->getProperty('tokenExp');
        $prop->setAccessible(true);
        $prop->setValue($client, time() - 100); // scaduto
        $this->assertFalse($client->isTokenValid());
    }

    public function testIsTokenValidTrue()
    {
        $client = new PdndClient();
        $reflection = new ReflectionClass($client);
        $prop = $reflection->getProperty('tokenExp');
        $prop->setAccessible(true);
        $prop->setValue($client, time() + 100); // valido
        $this->assertTrue($client->isTokenValid());
    }
}
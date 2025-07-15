<?php
/**
 * @package Pdnd
 * @name PdndClientTest
 * @license MIT
 * @file PdndClientTest.php
 * @brief Test suite for the PDND client.
 * @author Francesco Loreti
 * @mailto francesco.loreti@isprambiente.it
 * @first_release 2025-07-13
 */

namespace Pdnd;
use Pdnd\PdndClient;
use Pdnd\PdndException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Suite di test per il client PDND.
 * Questa classe contiene test per la configurazione, la validazione del token e la validazione degli URL.
 */
class PdndClientTest extends TestCase
{
  /**
   * Test per la configurazione con un file mancante.
   * Si aspetta che venga lanciata un'eccezione PdndException.
   */
  public function testConfigMissingFile()
  {
    $client = new PdndClient();
    $this->expectException(PdndException::class);
    $client->config('/path/invalido/config.json');
  }

  /**
   * Test per la configurazione con un file JSON valido.
   * Si aspetta che i parametri vengano impostati correttamente.
   */
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

  /**
   * Test per la validazione del token.
   * Si aspetta che venga lanciata un'eccezione PdndException se il token non è valido.
   */
  public function testIsTokenValidFalse()
  {
    $client = new PdndClient();
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('tokenExp');
    $prop->setAccessible(true);
    $prop->setValue($client, time() - 100); // scaduto
    $this->assertFalse($client->isTokenValid());
  }

  /**
   * Test per la validazione del token.
   * Si aspetta che ritorni true se il token è valido.
   */
  public function testIsTokenValidTrue()
  {
    $client = new PdndClient();
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('tokenExp');
    $prop->setAccessible(true);
    $prop->setValue($client, time() + 100); // valido
    $this->assertTrue($client->isTokenValid());
  }

  /**
   * Test per la configurazione con un file JSON valido.
   * Si aspetta che i parametri vengano impostati correttamente.
   */
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

  /**
   * Test per la configurazione con un file JSON non valido.
   * Si aspetta che venga lanciata un'eccezione PdndException.
   */
  public function testValidateConfigThrowsException()
  {
    $client = new PdndClient();
    // Non settiamo nessun parametro, deve lanciare PdndException
    $this->expectException(PdndException::class);
    $client->config(null);
  }

  /**
   * Test per la validazione degli URL.
   * Si aspetta che venga lanciata un'eccezione PdndException se l'URL non è valido.
   */
  public function testValidateUrlThrowsException()
  {
    $client = new PdndClient();
    $configPath = __DIR__ . '/../configs/sample.json';
    $client->config($configPath);
    $client->setApiUrl('https://api.example.com');
    $this->expectException(PdndException::class);
    $client->validateUrl($client->getApiUrl());
  }

  /**
   * Test per la validazione degli URL.
   * Si aspetta che ritorni true se l'URL è valido.
   */
  public function testValidateUrlTrue()
  {
    $client = new PdndClient();
    $configPath = __DIR__ . '/../configs/sample.json';
    $client->config($configPath);
    $client->setApiUrl('https://www.google.com');
    $this->assertTrue($client->validateUrl($client->getApiUrl()));
  }
}
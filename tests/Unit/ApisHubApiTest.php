<?php

namespace Tests\Unit;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApisHubApiTest extends TestCase
{
    protected string $baseUrl = 'https://hub.example.com';
    protected string $apiKey = 'test-api-key-123';

    protected function createMockedClient(array $responses = [], ?MockHandler $mock = null): ApisHubApi
    {
        if ($mock === null) {
            $mock = new MockHandler($responses);
        }
        $handler = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handler]);
        
        return new ApisHubApi($this->baseUrl, $this->apiKey, $guzzle);
    }

    public function testConstructorSetsCorrectSettings(): void
    {
        $client = new ApisHubApi($this->baseUrl, $this->apiKey);
        
        $this->assertEquals($this->baseUrl . '/', $client->getBaseUrl());
        $auth = $client->getAuthSettings();
        $this->assertEquals('header', $auth['location']);
        $this->assertEquals('X-Admin-API-Key', $auth['name']);
    }

    public function testGetHeartbeatSuccess(): void
    {
        $responseData = ['status' => 'healthy', 'db' => 'online'];
        $mock = new MockHandler([
            new Response(200, [], json_encode($responseData)),
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        $response = $client->getHeartbeat();
        
        $this->assertEquals($responseData, $response);
        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
        $this->assertEquals($this->baseUrl . '/api/heartbeat', (string) $lastRequest->getUri());
        $this->assertEquals($this->apiKey, $lastRequest->getHeaderLine('X-Admin-API-Key'));
    }

    public function testTriggerRedeploy(): void
    {
        $responseData = ['success' => true];
        $mock = new MockHandler([
            new Response(200, [], json_encode($responseData)),
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        $response = $client->triggerRedeploy();
        
        $this->assertEquals($responseData, $response);
        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertEquals($this->baseUrl . '/api/management/redeploy', (string) $lastRequest->getUri());
    }

    public function testUpdateCredentials(): void
    {
        $responseData = ['success' => true];
        $payload = ['KEY' => 'VALUE'];
        $mock = new MockHandler([
            new Response(200, [], json_encode($responseData)),
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        $response = $client->updateCredentials($payload);
        
        $this->assertEquals($responseData, $response);
        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('POST', $lastRequest->getMethod());
        $this->assertEquals(json_encode($payload), (string) $lastRequest->getBody());
    }

    public function testCRUDMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => []])), // list
            new Response(200, [], json_encode(['id' => 1])), // create
            new Response(200, [], json_encode(['id' => 1])), // read
            new Response(200, [], json_encode(['success' => true])), // update
            new Response(200, [], json_encode(['success' => true])), // delete
            new Response(200, [], json_encode(['count' => 10])), // count
            new Response(200, [], json_encode(['result' => 500])), // aggregate
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        
        $client->listEntities('metrics', ['page' => 1]);
        $this->assertEquals($this->baseUrl . '/entity/metrics?page=1', (string) $mock->getLastRequest()->getUri());

        $client->createEntity('metrics', ['value' => 100]);
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());

        $client->readEntity('metrics', 1);
        $this->assertEquals($this->baseUrl . '/entity/metrics/1', (string) $mock->getLastRequest()->getUri());

        $client->updateEntity('metrics', 1, ['value' => 200]);
        $this->assertEquals('PUT', $mock->getLastRequest()->getMethod());

        $client->deleteEntity('metrics', 1);
        $this->assertEquals('DELETE', $mock->getLastRequest()->getMethod());

        $client->countEntities('metrics');
        $this->assertStringContainsString('/count', (string) $mock->getLastRequest()->getUri());

        $client->aggregateEntities('metrics', ['sum' => 'value']);
        $this->assertStringContainsString('/aggregate', (string) $mock->getLastRequest()->getUri());
    }

    public function testChanneledMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([])), // list
            new Response(200, [], json_encode([])), // read
            new Response(200, [], json_encode([])), // count
            new Response(200, [], json_encode([])), // aggregate
            new Response(200, [], json_encode([])), // range
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        
        $client->listChanneled('fb', 'metrics');
        $this->assertEquals($this->baseUrl . '/fb/metrics', (string) $mock->getLastRequest()->getUri());

        $client->readChanneled('fb', 'metrics', 'xyz');
        $this->assertEquals($this->baseUrl . '/fb/metrics/xyz', (string) $mock->getLastRequest()->getUri());

        $client->countChanneled('fb', 'metrics');
        $this->assertEquals($this->baseUrl . '/fb/metrics/count', (string) $mock->getLastRequest()->getUri());

        $client->aggregateChanneled('fb', 'metrics', []);
        $this->assertEquals($this->baseUrl . '/fb/metrics/aggregate', (string) $mock->getLastRequest()->getUri());

        $client->rangeChanneled('fb', 'metrics');
        $this->assertEquals($this->baseUrl . '/fb/metrics/range', (string) $mock->getLastRequest()->getUri());
    }

    public function testCacheMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])), // trigger
            new Response(200, [], json_encode(['success' => true])), // interrupt
            new Response(200, [], json_encode(['success' => true])), // reset
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        
        $client->triggerCache('fb', 'metrics');
        $this->assertEquals($this->baseUrl . '/cache/fb/metrics', (string) $mock->getLastRequest()->getUri());

        $client->interruptCache('fb');
        $this->assertEquals($this->baseUrl . '/cache/interrupt', (string) $mock->getLastRequest()->getUri());

        $client->resetCache('metrics', [1, 2]);
        $this->assertEquals($this->baseUrl . '/cache/reset/metrics', (string) $mock->getLastRequest()->getUri());
    }

    public function testMonitoringAndConfigMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([])), // getStatus
            new Response(200, [], json_encode([])), // getLogList
            new Response(200, [], json_encode([])), // getLogs
            new Response(200, [], json_encode([])), // jobAction
            new Response(200, [], json_encode([])), // fetchAssets
            new Response(200, [], json_encode([])), // validateTokens
            new Response(200, [], json_encode([])), // exportConfig
            new Response(200, [], json_encode([])), // flushCache
            new Response(200, [], json_encode([])), // getMonitoringData
            new Response(200, [], json_encode([])), // updateConfig
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        
        $client->getStatus();
        $this->assertStringContainsString('/management/status', (string) $mock->getLastRequest()->getUri());

        $client->getLogList();
        $this->assertStringContainsString('/monitoring/logs/list', (string) $mock->getLastRequest()->getUri());

        $client->getLogs(['file' => 'test.log']);
        $this->assertStringContainsString('file=test.log', (string) $mock->getLastRequest()->getUri());

        $client->jobAction('job-1', 'cancel');
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertStringContainsString('/monitoring/jobs/action', (string) $mock->getLastRequest()->getUri());

        $client->fetchAssets();
        $this->assertStringContainsString('/config-manager/assets', (string) $mock->getLastRequest()->getUri());

        $client->validateTokens(['type' => 'fb']);
        $this->assertStringContainsString('/config-manager/validate-tokens', (string) $mock->getLastRequest()->getUri());

        $client->exportConfig();
        $this->assertStringContainsString('/config-manager/export', (string) $mock->getLastRequest()->getUri());

        $client->flushCache();
        $this->assertStringContainsString('/config-manager/flush-cache', (string) $mock->getLastRequest()->getUri());

        $client->getMonitoringData();
        $this->assertStringContainsString('/monitoring/data', (string) $mock->getLastRequest()->getUri());

        $client->updateConfig(['key' => 'value']);
        $this->assertStringContainsString('/config-manager/update', (string) $mock->getLastRequest()->getUri());
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
    }

    public function testSynchronizationMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])), // sync
            new Response(200, [], json_encode(['success' => true])), // stop
        ]);
        
        $client = $this->createMockedClient(mock: $mock);
        
        $client->triggerSync('facebook');
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/cache/facebook/all', (string) $mock->getLastRequest()->getUri());

        $client->stopJobs();
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/cache/interrupt', (string) $mock->getLastRequest()->getUri());
    }
}

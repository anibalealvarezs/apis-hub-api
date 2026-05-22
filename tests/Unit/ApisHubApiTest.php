<?php

namespace Tests\Unit;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Anibalealvarezs\ApisHubApi\AggregationQuery;
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

    public function testNewAndMissingMethods(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])), // resetChannel
            new Response(200, [], json_encode(['success' => true])), // importCredentials
            new Response(200, [], json_encode(['success' => true])), // containerAction
            new Response(200, [], json_encode(['success' => true])), // getSyncStatus
            new Response(200, [], json_encode(['success' => true])), // getSyncAccountStats
            new Response(200, [], json_encode(['success' => true])), // getPublicResourceData
            new Response(200, [], json_encode(['openapi' => '3.0.0'])), // getApiSpec
        ]);

        $client = $this->createMockedClient(mock: $mock);

        // 1. resetChannel
        $client->resetChannel('facebook_marketing');
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/management/reset-channel', (string) $mock->getLastRequest()->getUri());
        $this->assertEquals(json_encode(['channel' => 'facebook_marketing']), (string) $mock->getLastRequest()->getBody());

        // 2. importCredentials
        $client->importCredentials('facebook', 'mock-token', ['user_id' => '123']);
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/auth/facebook/import', (string) $mock->getLastRequest()->getUri());

        // 3. containerAction
        $client->containerAction('redis', 'restart');
        $this->assertEquals('POST', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/management/container/action', (string) $mock->getLastRequest()->getUri());

        // 4. getSyncStatus
        $client->getSyncStatus(['channel' => 'facebook']);
        $this->assertEquals('GET', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/sync/status?channel=facebook', (string) $mock->getLastRequest()->getUri());

        // 5. getSyncAccountStats
        $client->getSyncAccountStats(['channel' => 'facebook']);
        $this->assertEquals('GET', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/sync/account-stats?channel=facebook', (string) $mock->getLastRequest()->getUri());

        // 6. getPublicResourceData
        $client->getPublicResourceData('facebook', 'metrics', ['limit' => 10]);
        $this->assertEquals('GET', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/v1/public/facebook/metrics?limit=10', (string) $mock->getLastRequest()->getUri());

        // 7. getApiSpec
        $spec = $client->getApiSpec();
        $this->assertEquals('GET', $mock->getLastRequest()->getMethod());
        $this->assertEquals($this->baseUrl . '/api/spec', (string) $mock->getLastRequest()->getUri());
        $this->assertEquals(['openapi' => '3.0.0'], $spec);
    }

    public function testAggregationQueryBuilder(): void
    {
        $query = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'ctr' => 'ctr'])
            ->groupBy(['campaign_id'])
            ->dateRange('2026-05-01', '2026-05-15')
            ->orderBy('clicks', 'DESC')
            ->setPeriod('daily')
            ->withSnapshotDelta(true)
            ->withLatestSnapshot(true)
            ->setFallbackMode('resilient');

        $built = $query->build();

        $expected = [
            'aggregations' => ['clicks' => 'clicks', 'ctr' => 'ctr'],
            'groupBy' => ['campaign_id'],
            'startDate' => '2026-05-01',
            'endDate' => '2026-05-15',
            'orderBy' => 'clicks',
            'orderDir' => 'DESC',
            'filters' => [
                'period' => 'daily',
                'snapshot_delta' => true,
                'latest_snapshot' => true,
                'snapshot_fallback_mode' => 'resilient'
            ]
        ];

        $this->assertEquals($expected, $built);
    }

    public function testDashboardSampleQueries(): void
    {
        // 1. Query 1
        $q1 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['query'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');
        
        $this->assertEquals(
            json_decode('{"aggregations":{"clicks":"clicks","impressions":"impressions","ctr":"ctr","position":"position"},"groupBy":["query"],"filters":{"page":"1","dimensions.searchAppearance":"standard"},"startDate":"2026-04-18","endDate":"2026-05-16"}', true),
            $q1->build()
        );

        // 2. Query 2
        $q2 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy([])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');

        $this->assertEquals(
            json_decode('{"aggregations":{"clicks":"clicks","impressions":"impressions","ctr":"ctr","position":"position"},"groupBy":[],"filters":{"page":"1","dimensions.searchAppearance":"standard"},"startDate":"2026-04-18","endDate":"2026-05-16"}', true),
            $q2->build()
        );

        // 3. Query 3
        $q3 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy([])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-03-20', '2026-04-17');

        $this->assertEquals(
            json_decode('{"aggregations":{"clicks":"clicks","impressions":"impressions","ctr":"ctr","position":"position"},"groupBy":[],"filters":{"page":"1","dimensions.searchAppearance":"standard"},"startDate":"2026-03-20","endDate":"2026-04-17"}', true),
            $q3->build()
        );

        // 4. Query 4
        $q4 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['daily'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');

        $this->assertEquals(
            json_decode('{"aggregations":{"clicks":"clicks","impressions":"impressions","ctr":"ctr","position":"position"},"groupBy":["daily"],"filters":{"page":"1","dimensions.searchAppearance":"standard"},"startDate":"2026-04-18","endDate":"2026-05-16"}', true),
            $q4->build()
        );

        // 5. Query 5
        $q5 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['dimensions.searchAppearance'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', ['operator' => 'not_equal', 'value' => 'standard'])
            ->dateRange('2026-04-18', '2026-05-16');

        $this->assertEquals(
            json_decode('{"aggregations":{"clicks":"clicks","impressions":"impressions","ctr":"ctr","position":"position"},"groupBy":["dimensions.searchAppearance"],"filters":{"page":"1","dimensions.searchAppearance":{"operator":"not_equal","value":"standard"}},"startDate":"2026-04-18","endDate":"2026-05-16"}', true),
            $q5->build()
        );
    }
}

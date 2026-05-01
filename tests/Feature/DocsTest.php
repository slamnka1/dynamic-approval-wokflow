<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_swagger_ui_html_is_served(): void
    {
        $resp = $this->get('/docs');
        $resp->assertOk();
        $resp->assertSee('SwaggerUIBundle', false);
    }

    public function test_openapi_json_is_served(): void
    {
        $resp = $this->get('/docs/openapi.json');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/json');

        $body = json_decode($resp->getContent(), true);
        $this->assertSame('3.1.0', $body['openapi']);
        $this->assertNotEmpty($body['paths']);
    }

    public function test_postman_collection_is_served(): void
    {
        $resp = $this->get('/docs/postman_collection.json');
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'application/json');

        $body = json_decode($resp->getContent(), true);
        $this->assertArrayHasKey('info', $body);
        $this->assertArrayHasKey('item', $body);
    }
}

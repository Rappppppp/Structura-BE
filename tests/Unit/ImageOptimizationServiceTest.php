<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageOptimizationService;

class ImageOptimizationServiceTest extends TestCase
{
    public function testCompressBase64ReturnsDataUri()
    {
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';

        $result = ImageOptimizationService::compressBase64($png);
        $this->assertStringStartsWith('data:image/', $result);

        $payload = preg_replace('#^data:image/[^;]+;base64,#', '', $result);
        $this->assertNotEmpty($payload);
        $decoded = base64_decode($payload, true);
        $this->assertNotFalse($decoded);

        $size = ImageOptimizationService::getBase64Size($result);
        $this->assertIsInt($size);
        $this->assertGreaterThan(0, $size);

        $formatted = ImageOptimizationService::formatBytes($size);
        $this->assertIsString($formatted);
        $this->assertStringContainsString(' ', $formatted);
    }

    public function testGetBase64SizeMatchesDecodedLength()
    {
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $payload = preg_replace('#^data:image/[^;]+;base64,#', '', $png);
        $decoded = base64_decode($payload, true);
        $expectedSize = $decoded === false ? 0 : strlen($decoded);

        $size = ImageOptimizationService::getBase64Size($png);
        $this->assertEquals($expectedSize, $size);
    }

    public function testCompressBase64InvalidReturnsOriginal()
    {
        $bad = 'this-is-not-base64';
        $result = ImageOptimizationService::compressBase64($bad);
        $this->assertEquals($bad, $result);
    }
}

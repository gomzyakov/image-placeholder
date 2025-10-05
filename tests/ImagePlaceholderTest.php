<?php

declare(strict_types = 1);

namespace Tests;

use Gomzyakov\ImagePlaceholder;
use PHPUnit\Framework\TestCase;

class ImagePlaceholderTest extends TestCase
{
    public function test_generates_png_binary_with_expected_dimensions()
    {
        $generator = new ImagePlaceholder();
        $pngData   = $generator->generate(16, 10, 'seed-1');

        // Assert PNG signature (first 8 bytes) to ensure valid PNG binary
        $this->assertNotFalse($pngData);
        $this->assertIsString($pngData);
        $this->assertGreaterThan(8, strlen($pngData));
        $pngSignature = substr($pngData, 0, 8);
        $this->assertSame("\x89PNG\r\n\x1a\n", $pngSignature);

        // Verify width and height by parsing the IHDR chunk
        $this->assertSame([16, 10], $this->extractPngSize($pngData));
    }

    public function test_same_seed_produces_identical_output()
    {
        $generator = new ImagePlaceholder();

        $a = $generator->generate(32, 24, 'repeatable-seed');
        $b = $generator->generate(32, 24, 'repeatable-seed');

        // Same seed and params -> deterministic identical PNG
        $this->assertSame($a, $b);
    }

    public function test_different_seed_changes_output()
    {
        $generator = new ImagePlaceholder();

        $a = $generator->generate(32, 24, 'seed-a');
        $b = $generator->generate(32, 24, 'seed-b');

        // Different seed should lead to different image content
        $this->assertNotSame($a, $b);
    }

    public function test_bounds_are_clamped()
    {
        $generator = new ImagePlaceholder();

        // Below minimum should clamp to 1x1
        $minData = $generator->generate(0, -5, 's');
        $this->assertSame([1, 1], $this->extractPngSize($minData));

        // Above maximum should clamp to 2000x2000
        $maxData = $generator->generate(99999, 99999, 's');
        $this->assertSame([2000, 2000], $this->extractPngSize($maxData));
    }

    public function test_components_bounds_are_clamped_but_png_valid()
    {
        $generator = new ImagePlaceholder();

        // cx, cy are clamped internally to [1..9]; ensure generation succeeds
        $data = $generator->generate(8, 6, 'seed', 0, 99);
        $this->assertIsString($data);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($data, 0, 8));
    }

    /**
     * Extract width and height from PNG IHDR chunk without GD dependency.
     * PNG layout: 8B signature, then 4B length, 4B type ("IHDR"), data (13B), 4B CRC.
     * IHDR data: 4B width, 4B height (both big-endian).
     *
     * @return array{0:int,1:int}
     */
    private function extractPngSize(string $pngData): array
    {
        $this->assertGreaterThanOrEqual(24, strlen($pngData), 'PNG too small');

        // After signature (8 bytes) comes first chunk header
        $offset = 8;
        $len    = unpack('N', substr($pngData, $offset, 4))[1];
        $type   = substr($pngData, $offset + 4, 4);
        $this->assertSame('IHDR', $type, 'First chunk is not IHDR');
        $this->assertSame(13, $len, 'IHDR length must be 13');

        $ihdrData = substr($pngData, $offset + 8, 13);
        $width    = unpack('N', substr($ihdrData, 0, 4))[1];
        $height   = unpack('N', substr($ihdrData, 4, 4))[1];

        return [$width, $height];
    }
}

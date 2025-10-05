<?php

declare(strict_types = 1);

namespace Gomzyakov;

use kornrunner\Blurhash\Blurhash;
use RuntimeException;

use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagepng;
use function imagesetpixel;

class ImagePlaceholder
{
    /**
     * Generate a placeholder image using blurhash.
     */
    public function generate(
        int $width,
        int $height,
        string $seed = 'default',
        int $cx = 4,
        int $cy = 3
    ): string {
        $width  = max(1, min(2000, $width));
        $height = max(1, min(2000, $height));
        $cx     = max(1, min(9, $cx));
        $cy     = max(1, min(9, $cy));

        $src_w     = 4;
        $src_h     = 3;
        $pixels    = [];
        $seed_hash = crc32($seed);

        $rand = function () use (&$seed_hash): int {
            $seed_hash = (1103515245 * $seed_hash + 12345) & 0x7fffffff;

            return $seed_hash & 0xff;
        };

        for ($y = 0; $y < $src_h; $y++) {
            $row = [];
            for ($x = 0; $x < $src_w; $x++) {
                $row[] = [$rand(), $rand(), $rand()];
            }
            $pixels[] = $row;
        }

        $blurhash = Blurhash::encode($pixels, $cx, $cy);
        $decoded  = Blurhash::decode($blurhash, $width, $height);

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Failed to create image');
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                /** @phpstan-ignore-next-line */
                [$r, $g, $b] = $decoded[$y][$x];
                /** @phpstan-ignore-next-line */
                imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
            }
        }

        ob_start();
        imagepng($image);
        $image_data = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        if ($image_data === false) {
            throw new RuntimeException('Failed to generate image');
        }

        return $image_data;
    }
}

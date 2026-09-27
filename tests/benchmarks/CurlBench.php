<?php

declare(strict_types=1);

namespace PrettyPhp\Tests\benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PrettyPhp\Curl\CurlHandle;

/**
 * Measures wrapper overhead using file:// transfers, so no network is involved
 */
#[BeforeMethods('setUp')]
class CurlBench
{
    private string $url;

    public function setUp(): void
    {
        $this->url = 'file://' . __FILE__;
    }

    #[Revs(500)]
    #[Iterations(10)]
    public function benchHandleRequest(): void
    {
        $handle = new CurlHandle($this->url);
        (void) $handle->setOption(CURLOPT_RETURNTRANSFER, true);
        (void) $handle->execute();
    }

    #[Revs(500)]
    #[Iterations(10)]
    public function benchNativeHandleRequest(): void
    {
        $handle = curl_init($this->url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($handle);
    }
}

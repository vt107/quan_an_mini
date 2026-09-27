<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * Test dùng cache file thật (như production) để bắt lỗi serialize; xóa cache giữa các test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }
}

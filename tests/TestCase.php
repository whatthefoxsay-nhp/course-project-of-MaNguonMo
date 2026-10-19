<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test không phụ thuộc public/build: clone mới chưa chạy `npm run build` vẫn xanh.
        $this->withoutVite();
    }
}

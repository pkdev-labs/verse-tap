<?php

namespace PkDev\VerseClient\Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\ClientFactory;

#[Group('live')]
class VerseFetchTest extends TestCase
{
    public function test_it_can_fetch_a_verse(): void
    {
        if (!getenv('ESV_API_TOKEN')) {
            $this->markTestSkipped('ESV_API_TOKEN is not set.');
        }

        $client = ClientFactory::make('esv');
        $result = $client->fetch('John 3:16');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('God', $result);
    }
}

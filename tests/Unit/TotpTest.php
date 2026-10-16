<?php

namespace Tests\Unit;

use App\Security\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_rfc_6238_sha1_vector_and_replay_window(): void
    {
        $totp = new Totp;
        $secret = $totp->base32Encode('12345678901234567890');
        $this->assertSame('287082', $totp->codeAt($secret, 1));
        $this->assertSame(1, $totp->verify($secret, '287082', timestamp: 59));
        $this->assertNull($totp->verify($secret, '287082', lastUsedStep: 1, timestamp: 59));
    }
}

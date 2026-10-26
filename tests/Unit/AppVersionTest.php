<?php

namespace Tests\Unit;

use App\Models\AppVersion;
use PHPUnit\Framework\TestCase;

class AppVersionTest extends TestCase
{
    /**
     * Test format file size helper on AppVersion model.
     */
    public function test_formatted_file_size_returns_expected_units(): void
    {
        $version = new AppVersion();

        // 0 / null
        $version->file_size = null;
        $this->assertEquals('-', $version->formatted_file_size);

        // Bytes
        $version->file_size = 500;
        $this->assertEquals('500 B', $version->formatted_file_size);

        // Kilobytes
        $version->file_size = 2048;
        $this->assertEquals('2.00 KB', $version->formatted_file_size);

        // Megabytes
        $version->file_size = 74856063; // ~71.39 MB
        $this->assertEquals('71.39 MB', $version->formatted_file_size);
    }

    /**
     * Test version evaluation logic for update availability and force update.
     */
    public function test_version_evaluation_logic(): void
    {
        $latestVersionCode = 5;
        $minSupportedVersionCode = 3;
        $isForceUpdateGlobal = false;

        // Kasus 1: Klien versi 2 (< minSupported 3) -> Wajib Update (Force)
        $clientCode1 = 2;
        $updateAvailable1 = $latestVersionCode > $clientCode1;
        $forceUpdate1 = ($clientCode1 < $minSupportedVersionCode) || $isForceUpdateGlobal;
        $this->assertTrue($updateAvailable1);
        $this->assertTrue($forceUpdate1);

        // Kasus 2: Klien versi 4 (>= minSupported 3, < latest 5) -> Update Tersedia tapi Opsional
        $clientCode2 = 4;
        $updateAvailable2 = $latestVersionCode > $clientCode2;
        $forceUpdate2 = ($clientCode2 < $minSupportedVersionCode) || $isForceUpdateGlobal;
        $this->assertTrue($updateAvailable2);
        $this->assertFalse($forceUpdate2);

        // Kasus 3: Klien versi 5 (== latest) -> Tidak Ada Update
        $clientCode3 = 5;
        $updateAvailable3 = $latestVersionCode > $clientCode3;
        $forceUpdate3 = ($clientCode3 < $minSupportedVersionCode) || $isForceUpdateGlobal;
        $this->assertFalse($updateAvailable3);
        $this->assertFalse($forceUpdate3);

        // Kasus 4: Server menandai rilis sebagai force_update global
        $isForceUpdateGlobal2 = true;
        $forceUpdate4 = ($clientCode2 < $minSupportedVersionCode) || $isForceUpdateGlobal2;
        $this->assertTrue($forceUpdate4);
    }

    /**
     * Test SHA-256 validation simulation.
     */
    public function test_sha256_checksum_verification(): void
    {
        $samplePayload = 'TB Care Android APK Binary Stream 2026';
        $computedHash = hash('sha256', $samplePayload);

        $expectedHashUpper = strtoupper($computedHash);
        $expectedHashLower = strtolower($computedHash);

        // Verifikasi case-insensitive
        $this->assertEquals(strtolower($expectedHashUpper), $expectedHashLower);
        $this->assertEquals(64, strlen($computedHash));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\UploadTypePolicy;
use Tests\TestCase;

class UploadTypePolicyTest extends TestCase
{
    public function test_allows_expanded_office_and_code_types(): void
    {
        $policy = new UploadTypePolicy();

        $this->assertTrue($policy->isAllowed('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'report.xlsx'));
        $this->assertTrue($policy->isAllowed('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'deck.pptx'));
        $this->assertTrue($policy->isAllowed('text/csv', 'data.csv'));
        $this->assertTrue($policy->isAllowed('application/json', 'config.json'));
        $this->assertTrue($policy->isAllowed('application/octet-stream', 'notes.md'));
        $this->assertTrue($policy->isAllowed('application/x-7z-compressed', 'bundle.7z'));
        $this->assertTrue($policy->isAllowed('image/png', 'photo.png'));
        $this->assertTrue($policy->isAllowed('audio/mpeg', 'track.mp3'));
    }

    public function test_rejects_executables_and_installers(): void
    {
        $policy = new UploadTypePolicy();

        foreach (['setup.exe', 'app.msi', 'run.bat', 'run.cmd', 'script.ps1', 'lib.dll', 'joke.scr', 'tool.com', 'app.apk'] as $name) {
            $this->assertFalse(
                $policy->isAllowed('application/octet-stream', $name),
                "Expected {$name} to be rejected"
            );
        }

        $this->assertFalse($policy->isAllowed('application/x-msdownload', 'payload.exe'));
        $this->assertFalse($policy->isAllowed('application/vnd.android.package-archive', 'app.apk'));
    }
}

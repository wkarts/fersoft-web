<?php

namespace Tests\Unit;

use App\Services\LoginLogoSettings;
use PHPUnit\Framework\TestCase;

class LoginLogoSettingsTest extends TestCase
{
    public function test_missing_or_empty_profiles_do_not_override_the_approved_layouts(): void
    {
        foreach ([null, [], 'invalid', ['desktop' => [], 'mobile' => []]] as $input) {
            $value = LoginLogoSettings::presentation($input);
            $this->assertSame([], $value['attributes']);
            $this->assertSame('', $value['style']);
            $this->assertSame(LoginLogoSettings::defaults(), $value['profiles']['desktop']);
            $this->assertSame(LoginLogoSettings::defaults(), $value['profiles']['mobile']);
        }
    }

    public function test_desktop_and_mobile_profiles_are_independent_and_numeric_only(): void
    {
        $value = LoginLogoSettings::presentation([
            'desktop' => ['width' => '240', 'height' => 120, 'align' => 'right', 'position' => 'after'],
            'mobile' => ['width' => 96, 'align' => 'center', 'space_after' => 0],
        ]);
        $this->assertSame(240, $value['profiles']['desktop']['width']);
        $this->assertSame(96, $value['profiles']['mobile']['width']);
        $this->assertSame('right', $value['attributes']['data-logo-desktop-align']);
        $this->assertSame('center', $value['attributes']['data-logo-mobile-align']);
        $this->assertSame('0', $value['attributes']['data-logo-mobile-space-after']);
        $this->assertStringContainsString('--login-logo-desktop-width:240px', $value['style']);
        $this->assertStringContainsString('--login-logo-mobile-width:96px', $value['style']);
        $this->assertArrayNotHasKey('data-logo-mobile-height', $value['attributes']);
    }

    public function test_corrupt_json_and_css_injection_fall_back_without_breaking_login(): void
    {
        $value = LoginLogoSettings::presentation([
            'desktop' => ['width' => '90px;display:none', 'height' => -10, 'align' => ['left'], 'position' => 'fixed'],
            'mobile' => ['width' => 99999, 'height' => false, 'inset' => 1000, 'space_before' => 'url(evil)'],
        ]);
        $this->assertSame([], $value['attributes']);
        $this->assertSame('', $value['style']);
        $this->assertSame([], LoginLogoSettings::presentation(['desktop' => new \stdClass()])['attributes']);
    }

    public function test_restoring_one_profile_keeps_the_other_profile(): void
    {
        $input = ['desktop' => ['width' => 300], 'mobile' => ['width' => 80]];
        $input['desktop'] = LoginLogoSettings::defaults();
        $value = LoginLogoSettings::presentation($input);
        $this->assertArrayNotHasKey('data-logo-desktop', $value['attributes']);
        $this->assertSame('80', $value['attributes']['data-logo-mobile-width']);
    }
}

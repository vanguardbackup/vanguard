<?php

declare(strict_types=1);

use App\Models\User;
use Cjmellor\BrowserSessions\Facades\BrowserSessions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Session Manager Component', function (): void {
    test('the component can be rendered', function (): void {
        Volt::test('profile.session-manager')
            ->assertOk();
    });

    test('the page can be visited by authenticated users', function (): void {
        $this->get(route('profile.sessions'))
            ->assertOk()
            ->assertSeeLivewire('profile.session-manager');
    });

    test('the page cannot be visited by guests', function (): void {
        Auth::logout();
        $this->get(route('profile.sessions'))
            ->assertRedirect('login');
        $this->assertGuest();
    });
});

describe('Session Loading', function (): void {
    test('it loads sessions for authenticated user', function (): void {
        $mockSessions = collect([
            (object) ['id' => '1', 'ip_address' => '127.0.0.1', 'is_current_device' => true, 'last_active' => now(), 'device' => ['browser' => 'Chrome', 'platform' => 'Windows', 'desktop' => true]],
            (object) ['id' => '2', 'ip_address' => '192.168.1.1', 'is_current_device' => false, 'last_active' => now()->subHour(), 'device' => ['browser' => 'Firefox', 'platform' => 'MacOS', 'desktop' => true]],
        ]);

        BrowserSessions::shouldReceive('sessions')->once()->andReturn($mockSessions);

        Volt::test('profile.session-manager')
            ->assertSet('sessions', $mockSessions);
    });

    test('it shows a warning when session driver is not database', function (): void {
        Config::set('session.driver', 'file');

        Volt::test('profile.session-manager')
            ->assertSee('The session driver is not configured to use the database');
    });
});

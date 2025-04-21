<?php

declare(strict_types=1);

use App\Livewire\Admin\IPChecker\IPCheckerPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires admin privileges to access', function (): void {
    $regularUser = User::factory()->create(['email' => 'regular@email.com']);
    Config::set('auth.admin_email_addresses', ['admin@email.com']);

    $this->actingAs($regularUser)
        ->get(route('admin.ip-checker'))
        ->assertStatus(404);
});

it('allows admin users to access', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser)
        ->get(route('admin.ip-checker'))
        ->assertStatus(200);
});

it('validates IP address input', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', 'invalid-ip')
        ->call('check')
        ->assertHasErrors(['ipAddress' => 'ipv4']);
});

it('finds users by registration IP', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    User::factory()->create([
        'registration_ip' => '192.168.1.1',
        'most_recent_login_ip' => '10.0.0.1',
    ]);

    User::factory()->create([
        'registration_ip' => '192.168.1.1',
        'most_recent_login_ip' => '10.0.0.2',
    ]);

     User::factory()->create([
        'registration_ip' => '10.0.0.3',
        'most_recent_login_ip' => '10.0.0.3',
    ]);

    Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', '192.168.1.1')
        ->set('searchType', 'registration')
        ->call('check')
        ->assertSet('checked', true)
        ->assertSet('totalMatches', 2)
        ->assertCount('results', 2);
});

it('finds users by login IP', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    User::factory()->create([
        'registration_ip' => '192.168.1.1',
        'most_recent_login_ip' => '10.0.0.1',
    ]);

    User::factory()->create([
        'registration_ip' => '192.168.1.2',
        'most_recent_login_ip' => '10.0.0.1',
    ]);

    User::factory()->create([
        'registration_ip' => '10.0.0.3',
        'most_recent_login_ip' => '10.0.0.3',
    ]);

    Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', '10.0.0.1')
        ->set('searchType', 'login')
        ->call('check')
        ->assertSet('checked', true)
        ->assertSet('totalMatches', 2)
        ->assertCount('results', 2);
});

it('finds users by either registration or login IP when using both search type', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    // Create users with specific IPs
    User::factory()->create([
        'registration_ip' => '192.168.1.1',
        'most_recent_login_ip' => '10.0.0.1',
    ]);

    User::factory()->create([
        'registration_ip' => '192.168.1.1',
        'most_recent_login_ip' => '10.0.0.2',
    ]);

    User::factory()->create([
        'registration_ip' => '10.0.0.3',
        'most_recent_login_ip' => '192.168.1.1',
    ]);

    Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', '192.168.1.1')
        ->set('searchType', 'both')
        ->call('check')
        ->assertSet('checked', true)
        ->assertSet('totalMatches', 3)
        ->assertCount('results', 3);
});

it('formats results correctly', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    $testIp = '192.168.1.5';

    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'registration_ip' => $testIp,
        'most_recent_login_ip' => $testIp,
        'created_at' => now()->subDays(5),
        'last_login_at' => now()->subHours(2),
    ]);

    $livewire = Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', $testIp)
        ->call('check');

    $results = $livewire->get('results');
    $this->assertArrayHasKey(0, $results);

    $firstResult = $results[0];
    $this->assertEquals($user->id, $firstResult['id']);
    $this->assertEquals($user->name, $firstResult['name']);
    $this->assertEquals($user->email, $firstResult['email']);
    $this->assertTrue($firstResult['registration_match']);
    $this->assertTrue($firstResult['login_match']);
    $this->assertIsString($firstResult['created_at']);
    $this->assertIsString($firstResult['last_login']);
});

it('clears search results', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    $testIp = '192.168.1.5';

     User::factory()->create([
        'registration_ip' => $testIp,
        'most_recent_login_ip' => $testIp,
    ]);

    $livewire = Livewire::test(IPCheckerPage::class)
        ->set('ipAddress', $testIp)
        ->call('check')
        ->assertSet('checked', true)
        ->assertSet('totalMatches', 1)
        ->assertCount('results', 1);

    $livewire->call('clear')
        ->assertSet('ipAddress', null)
        ->assertSet('checked', false)
        ->assertSet('totalMatches', 0)
        ->assertCount('results', 0)
        ->assertSet('searchType', 'both');
});

it('updates search type correctly', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    Livewire::test(IPCheckerPage::class)
        ->assertSet('searchType', 'both')
        ->call('updateSearchType', 'registration')
        ->assertSet('searchType', 'registration')
        ->call('updateSearchType', 'login')
        ->assertSet('searchType', 'login')
        ->call('updateSearchType', 'both')
        ->assertSet('searchType', 'both');
});

it('initializes search from URL parameter', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $this->actingAs($adminUser);

    $testIp = '192.168.1.5';

    $user = User::factory()->create([
        'registration_ip' => $testIp,
        'most_recent_login_ip' => '10.0.0.1',
    ]);

    Livewire::test(IPCheckerPage::class, ['ipAddress' => $testIp])
        ->assertSet('ipAddress', $testIp)
        ->assertSet('checked', true)
        ->assertSet('totalMatches', 1)
        ->assertCount('results', 1);
});

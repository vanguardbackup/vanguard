<?php

declare(strict_types=1);

use App\Livewire\RemoteServers\CreateRemoteServerForm;
use App\Models\RemoteServer;
use App\Models\User;

test('create remote server form is rendered', function (): void {

    Livewire::test(CreateRemoteServerForm::class)
        ->assertStatus(200);
});

test('a user can create a remote server that we cannot connect to', function (): void {

    $component = Livewire::test(CreateRemoteServerForm::class)
        ->set('label', 'Test Server')
        ->set('host', '127.0.0.1')
        ->set('username', 'test')
        ->set('port', 22)
        ->call('submit');

    $component->assertSet('showingConnectionView', true);
    $component->assertSet('canConnectToRemoteServer', false);

    $this->assertDatabaseMissing('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '127.0.0.1',
        'username' => 'test',
        'port' => 22,
    ]);
});

test('a user can create a remote server that we can connect to', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(CreateRemoteServerForm::class)
        ->set('label', 'Test Server')
        ->set('host', '127.0.0.1')
        ->set('username', 'test')
        ->set('port', 22)
        ->set('databasePassword', 'password')
        ->set('testOverride', true) // This is a test override to allow the test to bypass the connection check
        ->call('submit');

    $component->assertSet('showingConnectionView', true);
    $component->assertSet('canConnectToRemoteServer', true);

    $component->assertDispatched('serverAdded');

    $remoteServer = RemoteServer::where('label', 'Test Server')->first();

    $this->assertDatabaseHas('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '127.0.0.1',
        'username' => 'test',
        'port' => 22,
        'user_id' => $user->id,
    ]);

    $this->assertTrue($remoteServer->hasDatabasePassword());
    $this->assertEquals('password', $remoteServer->getDecryptedDatabasePassword());
});

test('required fields are required', function (): void {

    Livewire::test(CreateRemoteServerForm::class)
        ->call('submit')
        ->assertHasErrors(['label', 'host', 'username']);
});

test('the ip address must be an ip address', function (): void {

    Livewire::test(CreateRemoteServerForm::class)
        ->set('host', 'not an ip address')
        ->call('submit')
        ->assertHasErrors(['host']);
});

test('ip addresses must be unique', function (): void {

    $remoteServer = RemoteServer::factory()->create();

    Livewire::test(CreateRemoteServerForm::class)
        ->set('host', $remoteServer->ip_address)
        ->call('submit')
        ->assertHasErrors(['host']);
});

test('port must be in valid range', function (): void {

    Livewire::test(CreateRemoteServerForm::class)
        ->set('port', 0)
        ->call('submit')
        ->assertHasErrors(['port']);

    Livewire::test(CreateRemoteServerForm::class)
        ->set('port', 65536)
        ->call('submit')
        ->assertHasErrors(['port']);
});

test('username is set correctly after provider method called', function (): void {
    Toaster::fake();

    $component = Livewire::test(CreateRemoteServerForm::class)
        ->set('username', 'test')
        ->call('usingServerProvider', 'ploi');

    $component->assertSet('username', 'ploi');

    Toaster::assertDispatched(__('The username has been updated to ":username".', ['username' => 'ploi']));
});

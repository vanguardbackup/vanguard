<?php

declare(strict_types=1);

use App\Livewire\RemoteServers\UpdateRemoteServerForm;
use App\Models\RemoteServer;
use App\Models\User;

test('the component can be rendered', function (): void {

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => RemoteServer::factory()->create()])
        ->assertStatus(200);
});

test('the component can be updated by the owner', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
        'database_password' => 'old_password',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('label', 'Test Server')
        ->set('host', '10.10.0.1')
        ->set('username', 'testuser')
        ->set('port', 22)
        ->set('databasePassword', 'password')
        ->call('submit');

    $this->assertDatabaseHas('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '10.10.0.1',
        'username' => 'testuser',
        'port' => 22,
    ]);

    $remoteServer = $remoteServer->fresh();

    $this->assertEquals('password', $remoteServer->getDecryptedDatabasePassword());
    $this->assertNotEquals('old_password', $remoteServer->getDecryptedDatabasePassword());

    $component->assertRedirect(route('remote-servers.index'));

    $this->assertEquals($user->id, $remoteServer->user_id);
});

test('the component cannot be updated by another user', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create();

    $component = Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('label', 'Test Server')
        ->set('host', '10.10.0.1')
        ->set('username', 'testuser')
        ->set('port', 22)
        ->set('databasePassword', 'password')
        ->call('submit');

    $this->assertDatabaseMissing('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '10.10.0.1',
        'username' => 'testuser',
        'database_password' => 'password',
        'port' => 22,
    ]);

    $component->assertStatus(403);

    $this->assertNotEquals($user->id, $remoteServer->user_id);
});

test('required is required', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('label', '')
        ->set('host', '')
        ->set('username', '')
        ->set('databasePassword', '')
        ->call('submit')
        ->assertHasErrors([
            'label' => 'required',
            'host' => 'required',
            'username' => 'required',
        ]);
});

test('ip addresses must be unique', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $remoteServer2 = RemoteServer::factory()->create();

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('host', $remoteServer2->ip_address)
        ->call('submit')
        ->assertHasErrors([
            'host' => 'unique',
        ]);
});

test('ip address must be an ip address', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('host', 'not an ip address')
        ->call('submit')
        ->assertHasErrors([
            'host' => 'ip',
        ]);
});

test('port value cannot fall outside of allowed range', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('port', 0)
        ->call('submit')
        ->assertHasErrors([
            'port' => 'min',
        ]);

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('port', 65536)
        ->call('submit')
        ->assertHasErrors([
            'port' => 'max',
        ]);
});

test('it does not update the database password unless set', function (): void {

    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
        'database_password' => Crypt::encryptString('password'),
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateRemoteServerForm::class, ['remoteServer' => $remoteServer])
        ->set('databasePassword', '')
        ->call('submit');

    $remoteServer = $remoteServer->fresh();

    $this->assertEquals('password', $remoteServer->getDecryptedDatabasePassword());
});

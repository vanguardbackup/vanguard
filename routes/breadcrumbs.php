<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('overview', function (BreadcrumbTrail $trail) {

    if (Auth::user()?->backupTasks()->exists()) {
        $trail->push(__('Overview'), route('overview'));
    } else {
        $trail->push(__('Steps to Get Started'), route('overview'));
    }
});

Breadcrumbs::for('profile', function (BreadcrumbTrail $trail) {
    $trail->push(__('My Profile'), route('profile'));
});

Breadcrumbs::for('account.remove-account', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Delete Account'), route('account.remove-account'));
});

Breadcrumbs::for('backup-tasks.index', function (BreadcrumbTrail $trail) {
    $trail->push(__('Backup Tasks'), route('backup-tasks.index'));
});

Breadcrumbs::for('backup-tasks.create', function (BreadcrumbTrail $trail) {
    $trail->parent('backup-tasks.index');
    $trail->push(__('Add Backup Task'), route('backup-tasks.create'));
});

Breadcrumbs::for('backup-tasks.edit', function (BreadcrumbTrail $trail, $backupTask) {
    $trail->parent('backup-tasks.index');
    $trail->push(__('Update Backup Task'), route('backup-tasks.edit', $backupTask));
});

Breadcrumbs::for('backup-destinations.index', function (BreadcrumbTrail $trail) {
    $trail->push(__('Backup Destinations'), route('backup-destinations.index'));
});

Breadcrumbs::for('backup-destinations.create', function (BreadcrumbTrail $trail) {
    $trail->parent('backup-destinations.index');
    $trail->push(__('Add Backup Destination'), route('backup-destinations.create'));
});

Breadcrumbs::for('backup-destinations.edit', function (BreadcrumbTrail $trail, $backupDestination) {
    $trail->parent('backup-destinations.index');
    $trail->push(__('Update Backup Destination'), route('backup-destinations.edit', $backupDestination));
});

Breadcrumbs::for('remote-servers.index', function (BreadcrumbTrail $trail) {
    $trail->push(__('Remote Servers'), route('remote-servers.index'));
});

Breadcrumbs::for('remote-servers.create', function (BreadcrumbTrail $trail) {
    $trail->parent('remote-servers.index');
    $trail->push(__('Add Remote Server'), route('remote-servers.create'));
});

Breadcrumbs::for('remote-servers.edit', function (BreadcrumbTrail $trail, $remoteServer) {
    $trail->parent('remote-servers.index');
    $trail->push(__('Update Remote Server'), route('remote-servers.edit', $remoteServer));
});

Breadcrumbs::for('tags.index', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Manage Tags'), route('tags.index'));
});

Breadcrumbs::for('tags.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tags.index');
    $trail->push(__('Create Tag'), route('tags.create'));
});

Breadcrumbs::for('tags.edit', function (BreadcrumbTrail $trail, $tag) {
    $trail->parent('tags.index');
    $trail->push(__('Update Tag'), route('tags.edit', $tag));
});

Breadcrumbs::for('scripts.index', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Manage Scripts'), route('scripts.index'));
});

Breadcrumbs::for('scripts.create', function (BreadcrumbTrail $trail) {
    $trail->parent('scripts.index');
    $trail->push(__('Create Script'), route('scripts.create'));
});

Breadcrumbs::for('notification-streams.index', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Notification Streams'), route('notification-streams.index'));
});

Breadcrumbs::for('notification-streams.create', function (BreadcrumbTrail $trail) {
    $trail->parent('notification-streams.index');
    $trail->push(__('Create Notification Stream'), route('notification-streams.create'));
});

Breadcrumbs::for('notification-streams.edit', function (BreadcrumbTrail $trail, $tag) {
    $trail->parent('notification-streams.index');
    $trail->push(__('Update Notification Stream'), route('notification-streams.edit', $tag));
});

Breadcrumbs::for('statistics', function (BreadcrumbTrail $trail) {
    $trail->push(__('Statistics'), route('statistics'));
});

Breadcrumbs::for('profile.api', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('API Tokens'), route('profile.api'));
});

Breadcrumbs::for('profile.mfa', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Two-Factor Authentication'), route('profile.mfa'));
});

Breadcrumbs::for('profile.sessions', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Active Sessions'), route('profile.sessions'));
});

Breadcrumbs::for('profile.experiments', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Experiments'), route('profile.experiments'));
});

Breadcrumbs::for('profile.quiet-mode', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Quiet Mode'), route('profile.quiet-mode'));
});

Breadcrumbs::for('profile.connections', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Connections'), route('profile.connections'));
});

Breadcrumbs::for('profile.help', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Help Centre'), route('profile.help'));
});

Breadcrumbs::for('profile.audit-logs', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Audit Logs'), route('profile.audit-logs'));
});

Breadcrumbs::for('profile.year-in-review', function (BreadcrumbTrail $trail) {
    $trail->parent('profile');
    $trail->push(__('Year in Review'), route('profile.year-in-review'));
});

Breadcrumbs::for('admin.instance-details', function (BreadcrumbTrail $trail) {
    $trail->push(__('Instance Details'), route('admin.instance-details'));
});

Breadcrumbs::for('admin.users', function (BreadcrumbTrail $trail) {
    $trail->push(__('Users'), route('admin.users'));
});

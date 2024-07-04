<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;

it('can see another language', function () {

    $danishUser = User::factory()->create([
        'language' => 'da',
    ]);

    $response = $this->actingAs($danishUser)
        ->withSession(['app_locale' => 'da'])
        ->get(route('overview'));

    $response->assertStatus(200);

    // The user should see the steps to get started string!
    $response->assertSee(__('Trin til at komme i gang'));
    $response->assertDontSee('Steps to Get Started'); // Raw english string

    $this->assertEquals($danishUser->language, 'da');
    $this->assertNotEquals($danishUser->language, 'en');

    // Make sure that the locales are set via Middleware.
    $this->assertEquals(App::getLocale(), $danishUser->language);
    $this->assertEquals(Carbon::getLocale(), $danishUser->language);
});

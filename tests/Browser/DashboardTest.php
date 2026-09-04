<?php

use App\Models\User;

test('the dashboard renders without javascript errors', function () {
    $this->actingAs(User::factory()->create(['email' => 'sara@example.com']));

    $page = visit(route('dashboard', absolute: false));

    $page->assertNoJavaScriptErrors()
        ->assertSee('اطلاعات من')
        ->assertSee('تغییر رمز عبور');
});

test('the dashboard saves a name, a username and a bio', function () {
    $sara = signInAsSara();

    visit(route('dashboard', absolute: false))
        ->fill('name', 'سارا احمدی')
        ->fill('username', 'sara-ahmadi')
        ->fill('bio', 'دنبال یک دوچرخه‌ی کوهستانم.')
        ->click('@save-account')
        ->assertPresent('@account-saved');

    $sara->refresh();

    expect($sara->name)->toBe('سارا احمدی')
        ->and($sara->username)->toBe('sara-ahmadi')
        ->and($sara->bio)->toBe('دنبال یک دوچرخه‌ی کوهستانم.');
});

test('the dashboard says in Persian that it saved', function () {
    signInAsSara();

    visit(route('dashboard', absolute: false))
        ->fill('name', 'سارا احمدی')
        ->click('@save-account')
        ->assertSee('اطلاعاتت ذخیره شد.');
});

test('the dashboard writes a new email and takes the verified stamp with it', function () {
    $sara = signInAsSara();

    visit(route('dashboard', absolute: false))
        ->fill('email', 'sara@example.com')
        ->click('@save-account')
        ->assertPresent('@account-saved');

    $sara->refresh();

    expect($sara->email)->toBe('sara@example.com')
        ->and($sara->email_verified_at)->toBeNull();
});

test('the dashboard shows the error when a username somebody else holds is saved', function () {
    $sara = signInAsSara();
    User::factory()->create(['username' => 'reza']);

    visit(route('dashboard', absolute: false))
        ->fill('username', 'reza')
        ->click('@save-account')
        ->assertSee('این نام کاربری قبلاً ثبت شده است.');

    expect($sara->fresh()->username)->toBe('sara');
});

function signInAsSara(): User
{
    $sara = User::factory()->create([
        'name' => 'سارا',
        'username' => 'sara',
        'email' => 'sara-old@example.com',
        'bio' => null,
    ]);

    test()->actingAs($sara);

    return $sara;
}

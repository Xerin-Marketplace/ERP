<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_english(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $this->actingAs($u)->get('/dashboard');
        $this->assertSame('en', App::getLocale());
    }

    public function test_user_can_switch_to_swahili(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $this->actingAs($u)->post('/locale', ['locale' => 'sw']);
        $this->assertSame('sw', session('locale'));
        $this->actingAs($u)->get('/dashboard');
        $this->assertSame('sw', App::getLocale());
    }

    public function test_preferred_language_persists_on_profile(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $u->profile()->create(['preferred_language' => 'sw']);
        $this->actingAs($u)->get('/dashboard');
        $this->assertSame('sw', App::getLocale());
    }

    public function test_invalid_locale_rejected(): void
    {
        $u = User::factory()->create(['status' => 'active']);
        $this->actingAs($u)->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
    }
}

<?php

namespace Tests\Feature;

use App\Services\Navigation\NavItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_nav_label_translates_to_swahili(): void
    {
        app()->setLocale('sw');
        $item = new NavItem(module: 'Finance', label: 'Finance Dashboard', icon: '');
        $this->assertSame('Dashibodi ya Fedha', $item->displayLabel());
        $this->assertSame('Wateja', (new NavItem(module: 'C', label: 'Customers', icon: ''))->displayLabel());
    }

    public function test_english_fallback_and_unknown_labels(): void
    {
        app()->setLocale('en');
        $item = new NavItem(module: 'X', label: 'Cargo Dashboard', icon: '');
        $this->assertSame('Cargo Dashboard', $item->displayLabel());

        // unmapped label falls back to raw text in any locale
        app()->setLocale('sw');
        $this->assertSame('Custom Thing', (new NavItem(module: 'X', label: 'Custom Thing', icon: ''))->displayLabel());
    }

    public function test_group_headers_translate(): void
    {
        app()->setLocale('sw');
        $this->assertSame('Fedha', NavItem::groupLabel('Finance'));
        $this->assertSame('Uendeshaji wa Mizigo', NavItem::groupLabel('Cargo Operations'));
        app()->setLocale('en');
        $this->assertSame('TZ Arrival & Customs', NavItem::groupLabel('TZ Arrival & Customs'));
    }
}

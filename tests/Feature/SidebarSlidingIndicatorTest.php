<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SidebarSlidingIndicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_sliding_indicator_assets_exist(): void
    {
        $jsPath = public_path('js/sidebar-sliding-indicator.js');
        $this->assertTrue(File::exists($jsPath), 'sidebar-sliding-indicator.js must exist in public/js');

        $jsContent = File::get($jsPath);
        // Validates sessionStorage offsetTop and offsetHeight persistence
        $this->assertStringContainsString('_offsetTop', $jsContent);
        $this->assertStringContainsString('_offsetHeight', $jsContent);
        $this->assertStringContainsString('sessionStorage.getItem', $jsContent);
        $this->assertStringContainsString('sessionStorage.setItem', $jsContent);
        $this->assertStringContainsString('.sidebar-active-indicator', $jsContent);

        $cssPath = public_path('css/sidebar.css');
        $this->assertTrue(File::exists($cssPath), 'sidebar.css must exist in public/css');

        $cssContent = File::get($cssPath);
        $this->assertStringContainsString('.sidebar-active-indicator', $cssContent);
        $this->assertStringContainsString('.sidebar-nav-list', $cssContent);
        $this->assertStringContainsString('has-sliding-indicator', $cssContent);
    }

    public function test_admin_sidebar_includes_sliding_indicator_element_and_script(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admindashboard');

        $response->assertOk();
        // Single absolutely positioned highlight indicator element inside admin sidebar
        $response->assertSee('class="sidebar-active-indicator" id="adminSidebarIndicator"', false);
        $response->assertSee('id="adminSidebarNavList"', false);
        $response->assertSee('js/sidebar-sliding-indicator.js', false);
    }

    public function test_receptionist_sidebar_includes_sliding_indicator_element_and_script(): void
    {
        $this->actingAsReceptionist();

        $response = $this->get('/dashboardreceptionist');

        $response->assertOk();
        // Single absolutely positioned highlight indicator element inside receptionist sidebar
        $response->assertSee('class="sidebar-active-indicator" id="receptionistSidebarIndicator"', false);
        $response->assertSee('id="receptionistSidebarNavList"', false);
        $response->assertSee('js/sidebar-sliding-indicator.js', false);
    }
}

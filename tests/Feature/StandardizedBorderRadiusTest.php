<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StandardizedBorderRadiusTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_radius_css_file_defines_component_specific_design_system(): void
    {
        $path = public_path('css/global-radius.css');
        $this->assertTrue(File::exists($path), 'global-radius.css must exist in public/css');

        $content = File::get($path);

        // 1. Modals & Dialogs (12px–16px)
        $this->assertStringContainsString('--radius-modal: 16px', $content);
        $this->assertStringContainsString('.modal-content', $content);

        // 2. Cards & Panels (8px–12px)
        $this->assertStringContainsString('--radius-card: 12px', $content);
        $this->assertStringContainsString('.card', $content);

        // 3. Table Wrappers (8px–12px with overflow-hidden)
        $this->assertStringContainsString('--radius-table-wrapper: 12px', $content);
        $this->assertStringContainsString('.amenities-table-wrapper', $content);
        $this->assertStringContainsString('.staff-table-wrapper', $content);

        // 4. Table Cells (td, th: 0px)
        $this->assertStringContainsString('--radius-table-cell: 0px', $content);
        $this->assertStringContainsString('.table td', $content);
        $this->assertStringContainsString('.table th', $content);

        // 5. Dropdowns & Popovers (6px–8px)
        $this->assertStringContainsString('--radius-dropdown: 8px', $content);
        $this->assertStringContainsString('.dropdown-menu', $content);

        // 6. Alerts & Toasts (6px–8px)
        $this->assertStringContainsString('--radius-alert: 8px', $content);
        $this->assertStringContainsString('.alert', $content);

        // 7. Buttons & Form Inputs shared radius (4px–8px)
        $this->assertStringContainsString('--radius-btn-input: 6px', $content);
        $this->assertStringContainsString('.btn', $content);
        $this->assertStringContainsString('.form-control', $content);
        $this->assertStringContainsString('.form-select', $content);

        // 8. Sidebar & Navigation Links (6px–8px)
        $this->assertStringContainsString('--radius-nav-link: 8px', $content);
        $this->assertStringContainsString('.sidebar-nav-link', $content);

        // 9. Badges & Status Tags (4px–6px rectangular, 9999px pill)
        $this->assertStringContainsString('--radius-badge-rect: 6px', $content);
        $this->assertStringContainsString('--radius-badge-pill: 9999px', $content);
        $this->assertStringContainsString('.room-tag', $content);
        $this->assertStringContainsString('.filter-pill', $content);

        // 10. Checkboxes (3px–4px)
        $this->assertStringContainsString('--radius-checkbox: 4px', $content);
        $this->assertStringContainsString('input[type="checkbox"]', $content);

        // 11. Avatars & Radio Buttons (50% circular)
        $this->assertStringContainsString('--radius-circular: 50%', $content);
        $this->assertStringContainsString('.rounded-circle', $content);
        $this->assertStringContainsString('input[type="radio"]', $content);
    }

    public function test_administrator_dashboard_includes_global_radius_css(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admindashboard');

        $response->assertOk();
        $response->assertSee('css/global-radius.css');
    }

    public function test_receptionist_dashboard_includes_global_radius_css(): void
    {
        $this->actingAsReceptionist();

        $response = $this->get('/dashboardreceptionist');

        $response->assertOk();
        $response->assertSee('css/global-radius.css');
    }

    public function test_individual_stylesheets_follow_component_specifications(): void
    {
        $sidebarCss = File::get(public_path('css/sidebar.css'));
        // Sidebar navigation link uses 8px (within 6px-8px range)
        $this->assertTrue(str_contains($sidebarCss, 'border-radius: var(--radius-nav-link, 8px);') || str_contains($sidebarCss, 'border-radius: 8px;'));

        $roomGridCss = File::get(public_path('css/room-grid.css'));
        // Cards use 12px (within 8px-12px range)
        $this->assertStringContainsString('border-radius: 12px;', $roomGridCss);
        // Buttons use 6px (within 4px-8px range)
        $this->assertStringContainsString('border-radius: 6px;', $roomGridCss);
        // Badges pill use 9999px
        $this->assertStringContainsString('border-radius: 9999px;', $roomGridCss);

        $amenityCss = File::get(public_path('css/Administrator/amenity.css'));
        $this->assertStringContainsString('--amenity-cube-radius: 10px', $amenityCss);
        $this->assertStringContainsString('--amenity-preview-radius: 10px', $amenityCss);

        $saleRepCss = File::get(public_path('css/Administrator/salerep.css'));
        $this->assertStringContainsString('border-radius: 6px;', $saleRepCss);
    }
}

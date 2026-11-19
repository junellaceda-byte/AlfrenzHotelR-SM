<?php

namespace Tests\Feature;

use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarTopbarDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_uses_same_design_and_structure_as_receptionist(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        // 1. Sidebar shell styling matching Receptionist with responsive offcanvas-md
        $response->assertSee('class="app-sidebar offcanvas-md offcanvas-start d-flex flex-column h-100 py-3 px-2 flex-shrink-0"', false);
        $response->assertSee('style="background-color: #6A4435;"', false);

        // 2. Circular logo container and hotel title matching Receptionist
        $response->assertSee('sidebar-logo-circle mx-auto d-flex align-items-center justify-content-center mb-2', false);
        $response->assertSee('images/logo.png', false);
        $response->assertSee('sidebar-hotel-name text-uppercase fw-bold', false);
        $response->assertSee("ALFRENZO'S HOTEL", false);

        // 3. Navigation items maintain Administrator features but use sidebar-nav-link
        $response->assertSee('OVERVIEW');
        $response->assertSee('AMENITIES');
        $response->assertSee('ACTIVITIES LOGS');
        $response->assertSee('SALES REPORTS');
        $response->assertSee('ARCHIVE');
        $response->assertSee('ROOM MANAGEMENT');
        $response->assertSee('STAFF');
        $response->assertSee('SETTINGS');
        $response->assertSee('LOGOUT');

        // 4. Default active link is OVERVIEW
        $response->assertSee('?section=overview" class="nav-link sidebar-nav-link text-uppercase fw-bold active"', false);

        // 5. Logout Confirmation Modal is present
        $response->assertSee('id="logoutConfirmModal"', false);
        $response->assertSee('Confirm Logout');
    }

    public function test_admin_sidebar_active_state_switches_with_section(): void
    {
        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();

        // AMENITIES link should be active, OVERVIEW should not be active
        $response->assertSee('?section=amenity" class="nav-link sidebar-nav-link text-uppercase fw-bold active"', false);
        $response->assertDontSee('?section=overview" class="nav-link sidebar-nav-link text-uppercase fw-bold active"', false);
    }

    public function test_admin_topbar_uses_same_design_and_structure_as_receptionist(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        // 1. Topbar header container matching Receptionist
        $response->assertSee('header class="bg-white border-bottom shadow-sm px-3 px-sm-4 py-3 d-flex justify-content-between align-items-center"', false);

        // 2. Welcoming greeting
        $response->assertSee('<h4 class="mb-0 fw-bold text-dark text-truncate"', false);
        $response->assertSee('Good day, Name!');

        // 3. Live Date & Time boxes with Receptionist styling
        $response->assertSee('border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary small', false);
        $response->assertSee('id="current-date"', false);
        $response->assertSee('id="current-time"', false);

        // 4. Action buttons: Notification bell with SVG and Avatar
        $response->assertSee('icons/bell.svg', false);
        $response->assertSee('title="Administrator"', false);
        $response->assertSee('border rounded-circle bg-white shadow-sm fw-bold', false);
    }

    public function test_admin_responsive_mobile_hamburger_and_offcanvas(): void
    {
        $response = $this->get('/admindashboard');
        $response->assertOk();

        // Hamburger button positioned at top-left corner, visible on mobile (d-md-none)
        $response->assertSee('id="openSidebar"', false);
        $response->assertSee('data-bs-toggle="offcanvas"', false);
        $response->assertSee('data-bs-target="#sidebarMenu"', false);
        $response->assertSee('d-md-none', false);

        // Close button inside sidebar on mobile screens
        $response->assertSee('id="closeSidebar"', false);
        $response->assertSee('data-bs-dismiss="offcanvas"', false);
    }

    public function test_admin_amenities_table_responsive_layout_and_alignment(): void
    {
        Amenity::create([
            'name' => 'Premium Extra Soft Pillows and Linens',
            'price' => 250.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();

        // 1. Responsive table wrapper and table class
        $response->assertSee('amenities-table-wrapper', false);
        $response->assertSee('table-responsive', false);
        $response->assertSee('amenities-table', false);

        // 2. Column groups with defined proportional widths
        $response->assertSee('<colgroup>', false);
        $response->assertSee('col-amenity', false);
        $response->assertSee('col-price', false);
        $response->assertSee('col-charged', false);
        $response->assertSee('col-offered', false);
        $response->assertSee('col-actions', false);

        // 3. Proper alignment and responsive cell classes
        $response->assertSee('Premium Extra Soft Pillows and Linens');
        $response->assertSee('amenity-price fw-semibold', false);
        $response->assertSee('₱250.00');
        $response->assertSee('per item');
        $response->assertSee('amenity-toggle-switch', false);

        // 4. Action buttons inside non-wrapping flex container to prevent overlap
        $response->assertSee('d-inline-flex align-items-center justify-content-end gap-1 gap-sm-2', false);
        $response->assertSee('title="Edit"', false);
        $response->assertSee('title="Delete"', false);
    }

    public function test_admin_amenities_mobile_top_right_actions_and_selection_requirement(): void
    {
        $amenity = Amenity::create([
            'name' => 'Mobile Selectable Towels',
            'price' => 120.00,
            'charged' => 'item',
            'offered' => true,
        ]);

        $response = $this->get('/admindashboard?section=amenity');
        $response->assertOk();

        // 1. Mobile Edit and Delete actions positioned directly below Add Amenity outside the table
        $response->assertSee('d-flex flex-column align-items-end gap-0 ms-auto', false);
        $response->assertSee('id="mobileEditBtn"', false);
        $response->assertSee('id="mobileDeleteBtn"', false);
        $response->assertSee('btn-outline-dark btn-sm py-1.5 px-2.5', false);
        $response->assertSee('btn-danger btn-sm py-1.5 px-2.5', false);

        // 2. Mobile selection notice banner
        $response->assertSee('id="mobileSelectionNotice"', false);
        $response->assertSee('Please select an amenity by clicking its table row to proceed.');

        // 3. Row selectable attributes for direct row clicking on mobile
        $response->assertSee('class="amenity-row" data-id="'.$amenity->id.'"', false);
        $response->assertSee("setMobileMode('edit')", false);
        $response->assertSee("setMobileMode('delete')", false);

        // 4. Desktop actions column remains hidden on mobile and visible on desktop
        $response->assertSee('d-none d-md-table-cell">Actions</th>', false);
        $response->assertSee('d-none d-md-table-cell', false);
    }
}

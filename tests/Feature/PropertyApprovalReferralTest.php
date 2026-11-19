<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyApprovalReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_properties_page_has_inventory_table_and_pending_submission_queue(): void
    {
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $professional = User::factory()->create(['role_name' => 'Agent']);
        Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Waiting House',
            'property_description' => 'Awaiting Admin verification.',
            'property_type' => 'House & Lot',
            'street' => '1 Review Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 4000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)
            ->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertSee('Project Listings')
            ->assertSee('Listing Requests')
            ->assertSee('Image')
            ->assertDontSee('Assigned professionals')
            ->assertSee('Waiting House')
            ->assertSee('Awaiting Admin verification.')
            ->assertSee('Reject');
    }

    public function test_professional_listing_stays_pending_until_admin_approves_and_then_gets_a_referral_code(): void
    {
        Http::fake();
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);

        $submission = $this->actingAs($professional)->post('/admin/properties', $this->propertyData());
        $submission->assertRedirect();

        $property = Property::query()->where('property_name', 'Professional Listing')->firstOrFail();
        $this->assertSame('Pending', $property->approval_status);
        $this->assertSame('professional', $property->listing_mode);
        $this->assertDatabaseMissing('referral_codes', ['property_id' => $property->property_id]);

        $approval = $this->actingAs($admin)->post(route('admin.properties.review', $property), [
            'approval_status' => 'Approved',
        ]);
        $approval->assertRedirect();

        $this->assertDatabaseHas('property_listings', [
            'property_id' => $property->property_id,
            'approval_status' => 'Approved',
            'approved_by' => $admin->user_id,
        ]);
        $this->assertDatabaseHas('referral_codes', [
            'property_id' => $property->property_id,
            'user_id' => $professional->user_id,
        ]);

        $code = ReferralCode::query()->where('property_id', $property->property_id)->firstOrFail();
        $this->actingAs($admin)
            ->get(route('app.page', ['page' => 'property']))
            ->assertOk()
            ->assertSee('Approved by')
            ->assertSee($admin->name);
        $this->actingAs($professional)
            ->get(route('app.page', ['page' => 'listings']))
            ->assertOk()
            ->assertSee('Add Property')
            ->assertSee('My Listings')
            ->assertSee($code->code);
        $this->get(route('referrals.open', $code->code))
            ->assertRedirect(route('properties.show', ['id' => $property->property_id, 'ref' => $code->code]));
    }

    public function test_duplicate_active_or_pending_property_cannot_be_saved_again(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Duplicate Review Home',
            'property_type' => 'House & Lot',
            'street' => '1 Review Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($professional)
            ->post(route('admin.properties.store'), [
                'property_name' => '  duplicate review home ',
                'property_type' => 'House & Lot',
                'description' => 'Duplicate attempt.',
                'street' => ' 1 Review Road ',
                'city' => 'Cebu City',
                'province' => 'Cebu',
                'price' => 5000000,
                'property_status' => 'For Sale',
                'lot_area' => 120,
                'floor_area' => 90,
                'bedrooms' => 3,
                'bathrooms' => 2,
            ])
            ->assertSessionHasErrors('property_name');

        $this->assertSame(1, Property::query()
            ->where('property_name', 'Duplicate Review Home')
            ->count());
    }

    public function test_approving_a_legacy_duplicate_rejects_the_other_pending_copies(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $duplicates = collect(range(1, 2))->map(fn () => Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Legacy Duplicate Home',
            'property_type' => 'House & Lot',
            'street' => '1 Legacy Road',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]));

        $this->actingAs($admin)
            ->post(route('admin.properties.review', $duplicates->first()), ['approval_status' => 'Approved'])
            ->assertRedirect();

        $this->assertSame('Approved', $duplicates->first()->fresh()->approval_status);
        $this->assertSame('Rejected', $duplicates->last()->fresh()->approval_status);
        $this->assertSame(Property::DUPLICATE_REJECTION_REASON, $duplicates->last()->fresh()->rejection_note);
    }

    public function test_rejection_requires_and_persists_a_reason(): void
    {
        $professional = User::factory()->create(['role_name' => 'Agent']);
        $admin = User::factory()->create(['role_name' => 'Admin']);
        $property = Property::create([
            'user_id' => $professional->user_id,
            'property_name' => 'Professional Listing',
            'property_type' => 'House & Lot',
            'property_description' => 'A verified professional property listing.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'property_status' => 'For Sale',
            'approval_status' => 'Pending',
            'listing_mode' => 'professional',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.properties.review', $property), ['approval_status' => 'Rejected'])
            ->assertSessionHasErrors('rejection_note');

        $this->post(route('admin.properties.review', $property), [
            'approval_status' => 'Rejected',
            'rejection_note' => 'Please provide a clearer property title document.',
        ])->assertRedirect();

        $this->assertDatabaseHas('property_listings', [
            'property_id' => $property->property_id,
            'approval_status' => 'Rejected',
            'rejection_note' => 'Please provide a clearer property title document.',
        ]);
        $this->assertDatabaseMissing('referral_codes', ['property_id' => $property->property_id]);
    }

    private function propertyData(): array
    {
        return [
            'property_name' => 'Professional Listing',
            'property_type' => 'House & Lot',
            'description' => 'A verified professional property listing.',
            'street' => '1 Test Street',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'price' => 5000000,
            'lot_area' => 120,
            'floor_area' => 100,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'property_status' => 'For Sale',
        ];
    }
}

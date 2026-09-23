<?php

namespace Tests;

use App\Models\Breed;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected function seedRoles(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('peternak');
    }

    protected function makeAdmin(array $attrs = []): User
    {
        $this->seedRoles();
        $user = User::factory()->create($attrs);
        $user->assignRole('admin');

        return $user;
    }

    /**
     * @return array{0: User, 1: FarmerProfile, 2: Breed}
     */
    protected function makeFarmer(array $attrs = []): array
    {
        $this->seedRoles();
        $user = User::factory()->create($attrs);
        $user->assignRole('peternak');
        $profile = FarmerProfile::create([
            'user_id' => $user->id,
            'farm_name' => 'Farm '.$user->name,
            'phone' => '08123456789',
        ]);
        $breed = Breed::first() ?: Breed::create(['name' => 'Simental', 'code' => 'SIM', 'is_active' => true]);

        return [$user, $profile, $breed];
    }

    protected function makeCattle(FarmerProfile $profile, Breed $breed, array $attrs = []): Cattle
    {
        return Cattle::create(array_merge([
            'farmer_id' => $profile->id,
            'breed_id' => $breed->id,
            'code' => 'SAPI-'.str_pad((string) (Cattle::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'sex' => 'female',
            'status' => 'active',
        ], $attrs));
    }
}

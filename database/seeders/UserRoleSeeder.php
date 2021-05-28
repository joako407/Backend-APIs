<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    private $roles = ['SUPERUSER','ADMIN','MANAGER'];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        foreach ($this->roles as $role) {
            // Creates roles based on array at top (1..3)
            UserRole::create([
                'role' => $role
            ]);
        }
    }
}

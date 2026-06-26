<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // create department
        $masterDepartment = Department::create([
            'name' => 'الادارة',
            'image' => 'assets/images/admin-image.jpg',
            'code' => 789,
            'phone' => 789,
            'type' => 'master',
        ]);

        $department1 = Department::create([
            'name' => 'المخازن',
            'image' => 'assets/images/admin-image.jpg',
            'code' => 123,
            'phone' => 123,
            'type' => 'source',
        ]);

        // $department = Department::create([
        //     'name' => 'المطبخ',
        //     'image' => 'assets/images/admin-image.jpg',
        //     'code' => 456,
        //     'phone'=> 456,
        //     'type' => 'both'
        // ]);

        // // $user = User::create([
        // //     'name' => 'الادمن',
        // //     'username' => 'admin',
        // //     'password' => bcrypt('admin123'),
        // //     'phone' => '123',
        // //     'department_id' => $masterDepartment->id
        // // ]);

        $user = User::create([
            'name' => 'أمين المخازن',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'phone' => '123',
            'department_id' => $department1->id,
        ]);

        // $user = User::create([
        //     'name' => 'الشيف',
        //     'username' => 'admin123',
        //     'password' => bcrypt('admin123'),
        //     'phone' => '123',
        //     'department_id' => $department->id
        // ]);
        //     $user = User::where('username', 'admin')->first();
        // $user = User::where('name', 'Administrator')->first();

        // $this->call(PermissionSeeder::class);

        // $role = Role::create([
        //     'name' => 'admin',
        //     'guard_name' => 'api'
        // ]);

        // $permissions[] = Permission::create(['name' => 'view units', 'guard_name' => 'api' , 'display_name' => 'عرض الوحدات']);

        $role = Role::where('name', 'admin')->first();
        $user->assignRole($role);
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        // $this->call(CategorySeeder::class);
        // $this->call(SubCategorySeeder::class);
        $this->call(DepartmentSeeder::class);
        // $this->call(RecipeCategorySeederParent::class);
        // $this->call(RecipeCategorySeeder::class);
        // $this->call(UnitSeeder::class);
        // $this->call(RecipeSeeder::class);
        // $this->call(SupplierSeeder::class);
        // $this->call(InvoiceSeeder::class);
        // $this->call(ProductSeeder::class);
        // $this->call(MenuSeeder::class);
        // $this->call(OrderSeeder::class);
        // $this->call(PayableSeeder::class);
        // $this->call(PriceSeeder::class);
        // $this->call(TransactionSeeder::class);
        // $this->call(UserSeeder::class);
        // $this->call(RequestSeeder::class);
        $this->call(AdditionalServiceSeeder::class);
    }
}

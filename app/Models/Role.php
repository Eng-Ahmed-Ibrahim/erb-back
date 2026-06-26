<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    use HasUuids;

    const ADMIN_ROLE_ID = '9c10d2ae-3790-4b10-bdf8-25cdadbe69f2';

    const STOCK_ROLE_ID = '9c10dbee-c27d-47c1-b408-fedca0b51163';

    const KITCHEN_ROLE_ID = '9c10dcd3-fd6a-4705-b454-eb10ac96a54f';

    const CASHIER_ROLE_ID = '9c10dd38-9229-4b5f-b48b-37aab38e9af7';

    const FOOD_AND_BEVERAGE_MANAGER_ROLE_ID = '9c10de53-14b1-4ae9-89d6-665ce7c0ccc5';

    const ACCOUNTS_ROLE_ID = '9c10deda-c41a-4c2c-9e5e-eb48322e038c';

    const COST_CONTROLLER_ROLE_ID = '9c10e432-011b-4fef-b672-620daa46b35d';

    const CHEMICAL_STOCK_ROLE_ID = '9d72735b-c904-4b4b-a613-f5f16ba8ad98';

    const MAINTAINANCE_STOCK_ROLE_ID = '9d727355-cad2-48b4-9671-aebbcfdc6771';

    const SUPPLY_ROLE_ID = '9de5c9c9-08e5-424b-86c5-11e71206f9f8';

    const EXTERNAL_ORDERS_CASHIER_ROLE_ID = '9db56bb2-7a34-4aad-8fbd-3f9b26a93e35';

    const EXTERNAL_ORDERS_CHIEF_ROLE_ID = '9c1102e-985-4b10-bdf8-25c8469f2';

    const ACTIVITIES_CASHIER = 'a077a0fc-b377-4802-a44a-5590994c680d';
}

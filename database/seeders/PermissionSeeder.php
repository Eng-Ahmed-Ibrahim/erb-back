<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    protected $guard_name = 'api';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // all display name is arabic
        $permissions[] = Permission::create(['name' => 'view recipe_category_parents', 'guard_name' => 'api', 'display_name' => 'عرض الأقسام الرئيسية للوصفات']);
        $permissions[] = Permission::create(['name' => 'create recipe_category_parent', 'guard_name' => 'api', 'display_name' => 'إضافة قسم رئيسي للوصفات']);
        $permissions[] = Permission::create(['name' => 'edit recipe_category_parent', 'guard_name' => 'api', 'display_name' => 'تعديل قسم رئيسي للوصفات']);
        $permissions[] = Permission::create(['name' => 'delete recipe_category_parent', 'guard_name' => 'api', 'display_name' => 'حذف قسم رئيسي للوصفات']);

        $permissions[] = Permission::create(['name' => 'view recipe_categories', 'guard_name' => 'api', 'display_name' => 'عرض الأقسام الفرعية للوصفات']);
        $permissions[] = Permission::create(['name' => 'add recipe_category', 'guard_name' => 'api', 'display_name' => 'إضافة قسم فرعي للوصفات']);
        $permissions[] = Permission::create(['name' => 'edit recipe_category', 'guard_name' => 'api', 'display_name' => 'تعديل قسم فرعي للوصفات']);
        $permissions[] = Permission::create(['name' => 'delete recipe_category', 'guard_name' => 'api', 'display_name' => 'حذف قسم فرعي للوصفات']);

        $permissions[] = Permission::create(['name' => 'view recipes', 'guard_name' => 'api', 'display_name' => 'عرض الوصفات']);
        $permissions[] = Permission::create(['name' => 'add recipe', 'guard_name' => 'api', 'display_name' => 'إضافة وصفة']);
        $permissions[] = Permission::create(['name' => 'edit recipe', 'guard_name' => 'api', 'display_name' => 'تعديل وصفة']);
        $permissions[] = Permission::create(['name' => 'delete recipe', 'guard_name' => 'api', 'display_name' => 'حذف وصفة']);
        $permissions[] = Permission::create(['name' => 'safe limit', 'guard_name' => 'api', 'display_name' => 'حد السلامة']);
        $permissions[] = Permission::create(['name' => 'expire_date limit', 'guard_name' => 'api', 'display_name' => 'حد تاريخ الانتهاء']);
        $permissions[] = Permission::create(['name' => 'total stores', 'guard_name' => 'api', 'display_name' => 'إجمالي المخازن']);

        $permissions[] = Permission::create(['name' => 'view suppliers', 'guard_name' => 'api', 'display_name' => 'عرض الموردين']);
        $permissions[] = Permission::create(['name' => 'add supplier', 'guard_name' => 'api', 'display_name' => 'إضافة مورد']);
        $permissions[] = Permission::create(['name' => 'edit supplier', 'guard_name' => 'api', 'display_name' => 'تعديل مورد']);
        $permissions[] = Permission::create(['name' => 'delete supplier', 'guard_name' => 'api', 'display_name' => 'حذف مورد']);
        $permissions[] = Permission::create(['name' => 'show supplier invoices', 'guard_name' => 'api', 'display_name' => 'عرض فواتير المورد']);

        $permissions[] = Permission::create(['name' => 'view categories', 'guard_name' => 'api', 'display_name' => 'عرض الأقسام']);
        $permissions[] = Permission::create(['name' => 'add category', 'guard_name' => 'api', 'display_name' => 'إضافة قسم']);
        $permissions[] = Permission::create(['name' => 'edit category', 'guard_name' => 'api', 'display_name' => 'تعديل قسم']);
        $permissions[] = Permission::create(['name' => 'delete category', 'guard_name' => 'api', 'display_name' => 'حذف قسم']);

        $permissions[] = Permission::create(['name' => 'view sub_categories', 'guard_name' => 'api', 'display_name' => 'عرض الأقسام الفرعية']);
        $permissions[] = Permission::create(['name' => 'add sub_category', 'guard_name' => 'api', 'display_name' => 'إضافة قسم فرعي']);
        $permissions[] = Permission::create(['name' => 'edit sub_category', 'guard_name' => 'api', 'display_name' => 'تعديل قسم فرعي']);
        $permissions[] = Permission::create(['name' => 'delete sub_category', 'guard_name' => 'api', 'display_name' => 'حذف قسم فرعي']);

        $permissions[] = Permission::create(['name' => 'add role', 'guard_name' => 'api', 'display_name' => 'إضافة دور']);
        $permissions[] = Permission::create(['name' => 'edit role', 'guard_name' => 'api', 'display_name' => 'تعديل دور']);
        $permissions[] = Permission::create(['name' => 'delete role', 'guard_name' => 'api', 'display_name' => 'حذف دور']);

        $permissions[] = Permission::create(['name' => 'view users', 'guard_name' => 'api', 'display_name' => 'عرض المستخدمين']);
        $permissions[] = Permission::create(['name' => 'add user', 'guard_name' => 'api', 'display_name' => 'إضافة مستخدم']);
        $permissions[] = Permission::create(['name' => 'edit user', 'guard_name' => 'api', 'display_name' => 'تعديل مستخدم']);
        $permissions[] = Permission::create(['name' => 'delete user', 'guard_name' => 'api', 'display_name' => 'حذف مستخدم']);
        $permissions[] = Permission::create(['name' => 'edit user role', 'guard_name' => 'api', 'display_name' => 'تعديل دور المستخدم']);
        $permissions[] = Permission::create(['name' => 'add other role', 'guard_name' => 'api', 'display_name' => 'إضافة دور آخر']);

        $permissions[] = Permission::create(['name' => 'add unit', 'guard_name' => 'api', 'display_name' => 'إضافة وحدة']);
        $permissions[] = Permission::create(['name' => 'edit unit', 'guard_name' => 'api', 'display_name' => 'تعديل وحدة']);
        $permissions[] = Permission::create(['name' => 'delete unit', 'guard_name' => 'api', 'display_name' => 'حذف وحدة']);

        $permissions[] = Permission::create(['name' => 'view requests', 'guard_name' => 'api', 'display_name' => 'عرض الطلبات']);
        $permissions[] = Permission::create(['name' => 'add request', 'guard_name' => 'api', 'display_name' => 'إضافة طلب']);
        $permissions[] = Permission::create(['name' => 'edit request', 'guard_name' => 'api', 'display_name' => 'تعديل طلب']);
        $permissions[] = Permission::create(['name' => 'delete request', 'guard_name' => 'api', 'display_name' => 'حذف طلب']);
        $permissions[] = Permission::create(['name' => 'change request status', 'guard_name' => 'api', 'display_name' => 'تغيير حالة الطلب']);

        $permissions[] = Permission::create(['name' => 'view departments', 'guard_name' => 'api', 'display_name' => 'عرض الأقسام']);
        $permissions[] = Permission::create(['name' => 'create department', 'guard_name' => 'api', 'display_name' => 'إضافة قسم']);
        $permissions[] = Permission::create(['name' => 'edit department', 'guard_name' => 'api', 'display_name' => 'تعديل قسم']);
        $permissions[] = Permission::create(['name' => 'delete department', 'guard_name' => 'api', 'display_name' => 'حذف قسم']);
        $permissions[] = Permission::create(['name' => 'department users', 'guard_name' => 'api', 'display_name' => 'مستخدمي القسم']);

        $permissions[] = Permission::create(['name' => 'view invoices', 'guard_name' => 'api', 'display_name' => 'عرض الفواتير']);
        $permissions[] = Permission::create(['name' => 'create invoice', 'guard_name' => 'api', 'display_name' => 'إضافة فاتورة']);
        $permissions[] = Permission::create(['name' => 'edit invoice', 'guard_name' => 'api', 'display_name' => 'تعديل فاتورة']);
        $permissions[] = Permission::create(['name' => 'change invoice status', 'guard_name' => 'api', 'display_name' => 'تغيير حالة الفاتورة']);

        $permissions[] = Permission::create(['name' => 'view tainted', 'guard_name' => 'api', 'display_name' => 'عرض الهالك']);
        $permissions[] = Permission::create(['name' => 'add tainted', 'guard_name' => 'api', 'display_name' => 'إضافة هالك']);
        $permissions[] = Permission::create(['name' => 'edit tainted', 'guard_name' => 'api', 'display_name' => 'تعديل هالك']);
        $permissions[] = Permission::create(['name' => 'delete tainted', 'guard_name' => 'api', 'display_name' => 'حذف هالك']);

        $permissions[] = Permission::create(['name' => 'view inventory blind counts', 'guard_name' => 'api', 'display_name' => 'عرض جرد المخزون الأعمى']);
        $permissions[] = Permission::create(['name' => 'create inventory blind count', 'guard_name' => 'api', 'display_name' => 'تسجيل جرد المخزون الأعمى']);
        $permissions[] = Permission::create(['name' => 'view inventory blind count reports', 'guard_name' => 'api', 'display_name' => 'عرض تقارير الجرد الأعمى']);

        $permissions[] = Permission::create(['name' => 'view reports', 'guard_name' => 'api', 'display_name' => 'عرض التقارير']);

        $permissions[] = Permission::create(['name' => 'view payable', 'guard_name' => 'api', 'display_name' => 'عرض المدفوعات']);
        $permissions[] = Permission::create(['name' => 'add payable', 'guard_name' => 'api', 'display_name' => 'إضافة مدفوعة']);
        $permissions[] = Permission::create(['name' => 'edit payable', 'guard_name' => 'api', 'display_name' => 'تعديل مدفوعة']);
        $permissions[] = Permission::create(['name' => 'delete payable', 'guard_name' => 'api', 'display_name' => 'حذف مدفوعة']);

        // permission product
        $permissions[] = Permission::create(['name' => 'view products', 'guard_name' => 'api', 'display_name' => 'عرض المنتجات']);
        $permissions[] = Permission::create(['name' => 'edit product', 'guard_name' => 'api', 'display_name' => 'تعديل منتج']);
        $permissions[] = Permission::create(['name' => 'add products to department', 'guard_name' => 'api', 'display_name' => 'إضافة منتجات لقسم']);
        $permissions[] = Permission::create(['name' => 'delete product from department', 'guard_name' => 'api', 'display_name' => 'حذف منتج من القسم']);
        $permissions[] = Permission::create(['name' => 'remove recipe from product', 'guard_name' => 'api', 'display_name' => 'حذف وصفة من المنتج']);
        $permissions[] = Permission::create(['name' => 'add product', 'guard_name' => 'api', 'display_name' => 'إضافة منتج']);
        $permissions[] = Permission::create(['name' => 'delete product', 'guard_name' => 'api', 'display_name' => 'حذف منتج']);
        $permissions[] = Permission::create(['name' => 'get products in department', 'guard_name' => 'api', 'display_name' => 'عرض المنتجات في القسم']);
        $permissions[] = Permission::create(['name' => 'add recipes to product', 'guard_name' => 'api', 'display_name' => 'إضافة وصفات للمنتج']);

        // permission order
        $permissions[] = Permission::create(['name' => 'view orders', 'guard_name' => 'api', 'display_name' => 'عرض الاوردرات']);
        $permissions[] = Permission::create(['name' => 'add order', 'guard_name' => 'api', 'display_name' => 'إضافة اوردر']);
        $permissions[] = Permission::create(['name' => 'edit order', 'guard_name' => 'api', 'display_name' => 'تعديل اوردر']);
        $permissions[] = Permission::create(['name' => 'delete order', 'guard_name' => 'api', 'display_name' => 'حذف اوردر']);
        $permissions[] = Permission::create(['name' => 'change order status cashier', 'guard_name' => 'api', 'display_name' => 'تغيير حالة الاوردر كاشير']);
        $permissions[] = Permission::create(['name' => 'change order status kitchen', 'guard_name' => 'api', 'display_name' => 'تغيير حالة الاوردر مطبخ']);
        $permissions[] = Permission::create(['name' => 'get orders in department', 'guard_name' => 'api', 'display_name' => 'عرض الاوردرات في القسم']);
        $permissions[] = Permission::create(['name' => 'execute order', 'guard_name' => 'api', 'display_name' => 'تنفيذ الاوردر']);

        // client permission
        $permissions[] = Permission::create(['name' => 'view clients', 'guard_name' => 'api', 'display_name' => 'عرض العملاء']);
        $permissions[] = Permission::create(['name' => 'add client', 'guard_name' => 'api', 'display_name' => 'إضافة عميل']);
        $permissions[] = Permission::create(['name' => 'edit client', 'guard_name' => 'api', 'display_name' => 'تعديل عميل']);
        $permissions[] = Permission::create(['name' => 'delete client', 'guard_name' => 'api', 'display_name' => 'حذف عميل']);

        // permission client type
        $permissions[] = Permission::create(['name' => 'view client types', 'guard_name' => 'api', 'display_name' => 'عرض أنواع العملاء']);
        $permissions[] = Permission::create(['name' => 'add client type', 'guard_name' => 'api', 'display_name' => 'إضافة نوع عميل']);
        $permissions[] = Permission::create(['name' => 'edit client type', 'guard_name' => 'api', 'display_name' => 'تعديل نوع عميل']);
        $permissions[] = Permission::create(['name' => 'delete client type', 'guard_name' => 'api', 'display_name' => 'حذف نوع عميل']);

        // discount reason
        $permissions[] = Permission::create(['name' => 'view discount reasons', 'guard_name' => 'api', 'display_name' => 'عرض أسباب الخصم']);
        $permissions[] = Permission::create(['name' => 'add discount reason', 'guard_name' => 'api', 'display_name' => 'إضافة سبب خصم']);
        $permissions[] = Permission::create(['name' => 'edit discount reason', 'guard_name' => 'api', 'display_name' => 'تعديل سبب خصم']);
        $permissions[] = Permission::create(['name' => 'delete discount reason', 'guard_name' => 'api', 'display_name' => 'حذف سبب خصم']);

        // payment methods
        $permissions[] = Permission::create(['name' => 'view payment methods', 'guard_name' => 'api', 'display_name' => 'عرض طرق الدفع']);
        $permissions[] = Permission::create(['name' => 'add payment method', 'guard_name' => 'api', 'display_name' => 'إضافة طريقة دفع']);
        $permissions[] = Permission::create(['name' => 'edit payment method', 'guard_name' => 'api', 'display_name' => 'تعديل طريقة دفع']);
        $permissions[] = Permission::create(['name' => 'delete payment method', 'guard_name' => 'api', 'display_name' => 'حذف طريقة دفع']);

        // Membership Cards Permissions
        // Officers
        $permissions[] = Permission::create(['name' => 'create membership card officer', 'guard_name' => 'api', 'display_name' => 'إضافة ضابط في بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'edit membership card officer', 'guard_name' => 'api', 'display_name' => 'تعديل ضابط في بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'delete membership card officer', 'guard_name' => 'api', 'display_name' => 'حذف ضابط من بطاقات العضوية']);

        // Beneficiaries
        $permissions[] = Permission::create(['name' => 'create membership card beneficiary', 'guard_name' => 'api', 'display_name' => 'إضافة مستفيد في بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'edit membership card beneficiary', 'guard_name' => 'api', 'display_name' => 'تعديل مستفيد في بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'delete membership card beneficiary', 'guard_name' => 'api', 'display_name' => 'حذف مستفيد من بطاقات العضوية']);

        // Subscriptions
        $permissions[] = Permission::create(['name' => 'create membership card subscription', 'guard_name' => 'api', 'display_name' => 'إنشاء اشتراك في بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'delete membership card subscription', 'guard_name' => 'api', 'display_name' => 'حذف اشتراك من بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'renew membership card subscription', 'guard_name' => 'api', 'display_name' => 'تجديد اشتراك في بطاقات العضوية']);

        // Cards
        $permissions[] = Permission::create(['name' => 'issue membership card', 'guard_name' => 'api', 'display_name' => 'إصدار بطاقة عضوية']);
        $permissions[] = Permission::create(['name' => 'issue replacement membership card', 'guard_name' => 'api', 'display_name' => 'إصدار بطاقة عضوية بديلة']);
        $permissions[] = Permission::create(['name' => 'print membership card', 'guard_name' => 'api', 'display_name' => 'طباعة بطاقة عضوية']);
        $permissions[] = Permission::create(['name' => 'encode membership card', 'guard_name' => 'api', 'display_name' => 'تشفير بطاقة عضوية']);
        $permissions[] = Permission::create(['name' => 'revoke membership card', 'guard_name' => 'api', 'display_name' => 'إلغاء بطاقة عضوية']);

        // Fee Plans
        $permissions[] = Permission::create(['name' => 'create membership card fee plan', 'guard_name' => 'api', 'display_name' => 'إنشاء خطة رسوم لبطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'edit membership card fee plan', 'guard_name' => 'api', 'display_name' => 'تعديل خطة رسوم لبطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'delete membership card fee plan', 'guard_name' => 'api', 'display_name' => 'حذف خطة رسوم من بطاقات العضوية']);
        $permissions[] = Permission::create(['name' => 'manage membership card replacement fee', 'guard_name' => 'api', 'display_name' => 'إدارة رسوم البطاقات البديلة']);

    }
}

<?php

namespace App\Providers;

use App\Models\Apartment;
use App\Models\Attachment;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Category;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\Department;
use App\Models\DiscountReason;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payable;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeParentCategory;
use App\Models\RecipeQuantity;
use App\Models\Request;
use App\Models\Role;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Waiter;
use App\Repositories\Apartment\Eloquent\EloquentApartmentRepository;
use App\Repositories\Apartment\ApartmentRepository;
use App\Repositories\Attachment\Eloquent\EloquentAttachmentRepository;
use App\Repositories\Attachment\AttachmentRepository;
use App\Repositories\Booking\Eloquent\EloquentBookingRepository;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Building\Eloquent\EloquentBuildingRepository;
use App\Repositories\Building\BuildingRepository;
use App\Repositories\Category\Eloquent\EloquentCategoryRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Client\Eloquent\EloquentClientRepository;
use App\Repositories\Client\ClientRepository;
use App\Repositories\ClientType\Eloquent\EloquentClientTypeRepository;
use App\Repositories\ClientType\ClientTypeRepository;
use App\Repositories\Department\Eloquent\EloquentDepartmentRepository;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\DiscountReason\Eloquent\EloquentDiscountReasonRepository;
use App\Repositories\DiscountReason\DiscountReasonRepository;
use App\Repositories\Invoice\Eloquent\EloquentInvoiceRepository;
use App\Repositories\Invoice\InvoiceRepository;
use App\Repositories\Order\Eloquent\EloquentOrderRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Payable\Eloquent\EloquentPaybleRepository;
use App\Repositories\Payable\PayableRepository;
use App\Repositories\PaymentMethod\Eloquent\EloquentPaymentMethodRepository;
use App\Repositories\PaymentMethod\PaymentMethodRepository;
use App\Repositories\Permission\Eloquent\EloquentPermissionRepository;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Product\Eloquent\EloquentProductRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Recipe\Eloquent\EloquentRecipeRepository;
use App\Repositories\Recipe\RecipeRepository;
use App\Repositories\RecipeCategory\Eloquent\EloquentRecipeCategoryRepository;
use App\Repositories\RecipeCategory\RecipeCategoryRepository;
use App\Repositories\RecipeCategoryParent\Eloquent\EloquentRecipeCategoryParentRepository;
use App\Repositories\RecipeCategoryParent\RecipeCategoryParentRepository;
use App\Repositories\RecipeQuantity\Eloquent\EloquentRecipeQuantityRepository;
use App\Repositories\RecipeQuantity\RecipeQuantityRepository;
use App\Repositories\Request\Eloquent\EloquentRequestRepository;
use App\Repositories\Request\RequestRepository;
use App\Repositories\Role\Eloquent\EloquentRoleRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\SubCategory\Eloquent\EloquentSubCategoryRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Repositories\Supplier\Eloquent\EloquentSupplierRepository;
use App\Repositories\Supplier\SupplierRepository;
use App\Repositories\Transaction\Eloquent\EloquentTransactionRepository;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\Unit\Eloquent\EloquentUnitRepository;
use App\Repositories\Unit\UnitRepository;
use App\Repositories\User\Eloquent\EloquentUserRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Visitor\Eloquent\EloquentVisitorRepository;
use App\Repositories\Visitor\VisitorRepository;
use App\Repositories\Waiter\Eloquent\EloquentWaiterRepository;
use App\Repositories\Waiter\WaiterRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(ClientRepository::class, function () {
            return new EloquentClientRepository(new Client);
        });
        $this->app->bind(ClientTypeRepository::class, function () {
            return new EloquentClientTypeRepository(new ClientType);
        });

        $this->app->bind(DiscountReasonRepository::class, function () {
            return new EloquentDiscountReasonRepository(new DiscountReason);
        });
        $this->app->bind(PaymentMethodRepository::class, function () {
            return new EloquentPaymentMethodRepository(new PaymentMethod);
        });

        $this->app->bind(UserRepository::class, function () {
            return new EloquentUserRepository(new User);
        });

        $this->app->bind(CategoryRepository::class, function () {
            return new EloquentCategoryRepository(new Category);
        });

        $this->app->bind(SubCategoryRepository::class, function () {
            return new EloquentSubCategoryRepository(new SubCategory);
        });

        $this->app->bind(ProductRepository::class, function () {
            return new EloquentProductRepository(new Product);
        });

        $this->app->bind(RecipeCategoryParentRepository::class, function () {
            return new EloquentRecipeCategoryParentRepository(new RecipeParentCategory);
        });

        $this->app->bind(RecipeCategoryRepository::class, function () {
            return new EloquentRecipeCategoryRepository(new RecipeCategory);
        });

        $this->app->bind(RecipeRepository::class, function () {
            return new EloquentRecipeRepository(new Recipe);
        });

        $this->app->bind(SupplierRepository::class, function () {
            return new EloquentSupplierRepository(new Supplier);
        });

        $this->app->bind(UnitRepository::class, function () {
            return new EloquentUnitRepository(new Unit);
        });

        $this->app->bind(DepartmentRepository::class, function () {
            return new EloquentDepartmentRepository(new Department);
        });

        $this->app->bind(WaiterRepository::class, function () {
            return new EloquentWaiterRepository(new Waiter);
        });

        $this->app->bind(InvoiceRepository::class, function () {
            return new EloquentInvoiceRepository(new Invoice);
        });

        $this->app->bind(RequestRepository::class, function () {
            return new EloquentRequestRepository(new Request);
        });

        $this->app->bind(PayableRepository::class, function () {
            return new EloquentPaybleRepository(new Payable);
        });

        $this->app->bind(TransactionRepository::class, function () {
            return new EloquentTransactionRepository(new Transaction);
        });

        $this->app->bind(OrderRepository::class, function () {
            return new EloquentOrderRepository(new Order);
        });

        $this->app->bind(RoleRepository::class, function () {
            return new EloquentRoleRepository(new Role);
        });
        $this->app->bind(PermissionRepository::class, function () {
            return new EloquentPermissionRepository(new Permission);
        });

        $this->app->bind(RecipeQuantityRepository::class, function () {
            return new EloquentRecipeQuantityRepository(new RecipeQuantity);
        });
        // Reception System Repositories
        $this->app->bind(VisitorRepository::class, function () {
            return new EloquentVisitorRepository(new Visitor);
        });

        $this->app->bind(BuildingRepository::class, function () {
            return new EloquentBuildingRepository(new Building);
        });

        $this->app->bind(ApartmentRepository::class, function () {
            return new EloquentApartmentRepository(new Apartment);
        });

        $this->app->bind(BookingRepository::class, function () {
            return new EloquentBookingRepository(new Booking);
        });

        $this->app->bind(AttachmentRepository::class, function () {
            return new EloquentAttachmentRepository(new Attachment);
        });
    }
}

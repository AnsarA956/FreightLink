<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipmentCancelController;
use App\Http\Controllers\ShipmentLocationController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PricingSettingController;
use App\Http\Controllers\Admin\ServiceTypeController;
use App\Http\Controllers\Admin\CargoTypeController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\ShipmentController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\StripePaymentController;
use App\Http\Controllers\Driver\ShipmentController as DriverShipmentController;
use App\Http\Controllers\Driver\RouteController as DriverRouteController;
use App\Http\Controllers\Staff\WalkInBookingController;

Route::get('/', function () {
    return view('intro');
});

// Public tracking pages (no login needed). Limited to 30 requests per minute per visitor.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/track', [TrackingController::class, 'index'])->name('track.index');
    Route::get('/track/{trackingId}', [TrackingController::class, 'show'])
        ->where('trackingId', '[A-Za-z0-9\-]+')
        ->name('track.show');
});

// Pages only for people who are NOT logged in
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Pages only for logged-in people (a deactivated account is logged out automatically)
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile page (every role)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // PDF invoice (the controller checks who may download it)
    Route::get('/invoices/{shipment}', [InvoiceController::class, 'download'])->name('invoice.download');

    // Cancel a booking (the controller checks who may cancel it)
    Route::post('/shipments/{shipment}/cancel', ShipmentCancelController::class)->name('shipments.cancel');

    // The driver's live position (the controller checks who may see it)
    Route::get('/shipments/{shipment}/location', [ShipmentLocationController::class, 'show'])
        ->middleware('throttle:60,1')->name('shipments.location');

    Route::get('/customer/dashboard', [DashboardController::class, 'customer'])
        ->middleware('role:customer')->name('customer.dashboard');

    Route::get('/staff/dashboard', [DashboardController::class, 'staff'])
        ->middleware('role:staff')->name('staff.dashboard');

    // Customer-only screens
    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/book', [BookingController::class, 'create'])->name('book.create');
        Route::post('/book/estimate', [BookingController::class, 'estimate'])->middleware('throttle:20,1')->name('book.estimate');
        Route::get('/book/review', [BookingController::class, 'review'])->name('book.review');
        Route::post('/book', [BookingController::class, 'store'])->name('book.store');

        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/{shipment}/review', [ReviewController::class, 'store'])->middleware('throttle:20,1')->name('shipments.review');
        Route::get('/shipments/{shipment}/pay', [PaymentController::class, 'create'])->name('shipments.pay');
        Route::post('/shipments/{shipment}/pay', [PaymentController::class, 'store'])->middleware('throttle:10,1')->name('shipments.pay.store');
                Route::post('/shipments/{shipment}/stripe', [StripePaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('shipments.stripe');
        Route::get('/shipments/{shipment}/stripe/return', [StripePaymentController::class, 'success'])->middleware('throttle:20,1')->name('shipments.stripe.success');
        Route::get('/shipments/{shipment}/stripe/cancel', [StripePaymentController::class, 'cancel'])->name('shipments.stripe.cancel');
    });

    // Staff-only screens: walk-in bookings
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/walk-in', [WalkInBookingController::class, 'create'])->name('walkin.create');
        Route::post('/walk-in/estimate', [WalkInBookingController::class, 'estimate'])->middleware('throttle:20,1')->name('walkin.estimate');
        Route::get('/walk-in/review', [WalkInBookingController::class, 'review'])->name('walkin.review');
        Route::post('/walk-in', [WalkInBookingController::class, 'store'])->name('walkin.store');
    });

    // Driver-only screens
    Route::middleware('role:driver')->prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', [DriverShipmentController::class, 'index'])->name('dashboard');
        Route::get('/shipments/{shipment}', [DriverShipmentController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/{shipment}/status', [DriverShipmentController::class, 'updateStatus'])->name('shipments.status');
        Route::post('/shipments/{shipment}/location', [DriverShipmentController::class, 'updateLocation'])
            ->middleware('throttle:30,1')->name('shipments.location');
                    Route::get('/route', [DriverRouteController::class, 'show'])
            ->middleware('throttle:30,1')->name('route');
    });

    // Shipment list and details: admin and staff
    Route::middleware('role:admin,staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('shipments', [AdminShipmentController::class, 'index'])->name('shipments.index');
        Route::get('shipments/{shipment}', [AdminShipmentController::class, 'show'])->name('shipments.show');
    });

    // Admin-only screens
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/pricing', [PricingSettingController::class, 'edit'])->name('pricing.edit');
        Route::put('/pricing', [PricingSettingController::class, 'update'])->name('pricing.update');

        Route::resource('service-types', ServiceTypeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('cargo-types', CargoTypeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('shipments/{shipment}/assign', [AdminShipmentController::class, 'assign'])->name('shipments.assign');

        Route::resource('discount-codes', DiscountCodeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('notifications', [NotificationLogController::class, 'index'])->name('notifications.index');
        Route::get('automation', [AutomationController::class, 'index'])->name('automation.index');
        Route::post('automation/run', [AutomationController::class, 'run'])->middleware('throttle:10,1')->name('automation.run');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/pdf', [ReportController::class, 'pdf'])->middleware('throttle:20,1')->name('reports.pdf');
        Route::get('reports/excel', [ReportController::class, 'excel'])->middleware('throttle:20,1')->name('reports.excel');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');

        Route::resource('vehicles', VehicleController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('drivers', DriverController::class)
            ->only(['index', 'store', 'edit', 'update']);
        Route::patch('drivers/{driver}/toggle', [DriverController::class, 'toggle'])->name('drivers.toggle');

        Route::resource('users', UserController::class)
            ->only(['index', 'store', 'edit', 'update']);
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });
});
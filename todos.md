composer require filament/filament:"^4.0" -W
php artisan filament:install --panels
php artisan make:filament-user

composer require bezhansalleh/filament-shield
php artisan shield:install



php artisan queue:table
php artisan queue:failed-table
php artisan migrate

composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

composer require spatie/laravel-sluggable
composer require spatie/laravel-sitemap

composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
php artisan migrate

composer require barryvdh/laravel-debugbar --dev

composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

<!-- optional -->
composer require "spatie/laravel-medialibrary"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
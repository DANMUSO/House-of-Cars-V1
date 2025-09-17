<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
            // Global model listeners
    Model::created(function ($model) {
        if (auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log("Created " . class_basename($model));
        }
    });

    Model::updated(function ($model) {
        if (auth()->check() && !empty($model->getChanges())) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->withProperties(['changes' => $model->getChanges()])
                ->log("Updated " . class_basename($model));
        }
    });

    Model::deleted(function ($model) {
        if (auth()->check()) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($model)
                ->log("Deleted " . class_basename($model));
        }
    });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

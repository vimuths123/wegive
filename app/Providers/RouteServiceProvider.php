<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\Bank;
use App\Models\Campaign;
use App\Models\Card;
use App\Models\Category;
use App\Models\Checkout;
use App\Models\CustomEmailAddress;
use App\Models\CustomEmailDomain;
use App\Models\DomainAlias;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\Fund;
use App\Models\Fundraiser;
use App\Models\Givelist;
use App\Models\Household;
use App\Models\ImpactNumber;
use App\Models\Interest;
use App\Models\Login;
use App\Models\MessageTemplate;
use App\Models\Organization;
use App\Models\ScheduledDonation;
use App\Models\Sms;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        Route::bind('organization', function ($value) {
            return Organization::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });

        Route::bind('category', function ($value) {
            return Category::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });

        Route::bind('givelist', function ($value) {
            return Givelist::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });

        Route::bind('user', function ($value) {
            return User::where('handle', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });

        Route::bind('card', function ($value) {
            return Card::where('id', $value)->firstOrFail();
        });

        Route::bind('scheduled_donation', function ($value) {
            return ScheduledDonation::where('id', $value)->firstOrFail();
        });

        Route::bind('bank', function ($value) {
            return Bank::where('id', $value)->firstOrFail();
        });

        Route::bind('impactNumber', function ($value) {
            return ImpactNumber::where('id', $value)->firstOrFail();
        });

        Route::bind('interest', function ($value) {
            return Interest::where('id', $value)->firstOrFail();
        });

        Route::bind('transaction', function ($value) {
            return Transaction::where('id', $value)->firstOrFail();
        });
        Route::bind('checkout', function ($value) {
            return Checkout::where('id', $value)->firstOrFail();
        });

        Route::bind('address', function ($value) {
            return Address::where('id', $value)->firstOrFail();
        });

        Route::bind('donor', function ($value) {
            return Donor::where('id', $value)->firstOrFail();
        });

        Route::bind('household', function ($value) {
            return Household::where('id', $value)->firstOrFail();
        });

        Route::bind('media', function ($value) {
            return Media::where('id', $value)->firstOrFail();
        });

        Route::bind('campaign', function ($value) {
            return Campaign::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });


        Route::bind('element', function ($value) {
            return Element::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });

        Route::bind('fundraiser', function ($value) {
            return Fundraiser::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });


        Route::bind('message_templates', function ($value) {
            return MessageTemplate::where('id', $value)->firstOrFail();
        });

        Route::bind('fund', function ($value) {
            return Fund::where('id', $value)->firstOrFail();
        });

        Route::bind('domain_alias', function ($value) {
            return DomainAlias::where('id', $value)->firstOrFail();
        });

        Route::bind('login', function ($value) {
            return Login::where('id', $value)->firstOrFail();
        });

        Route::bind('custom_email_domain', function ($value) {
            return CustomEmailDomain::where('id', $value)->firstOrFail();
        });

        Route::bind('custom_email_address', function ($value) {
            return CustomEmailAddress::where('id', $value)->firstOrFail();
        });

        Route::bind('email_template', function ($value) {
            return EmailTemplate::where('slug', $value)->orWhere(function ($query) use ($value) {
                if (is_numeric($value)) {
                    $query->where('id', $value);
                }
            })->firstOrFail();
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
            });

            RateLimiter::for('money', function (Request $request) {
                return [
                    Limit::perMinute(6)->by(optional($request->user())->id ?: $request->ip()),
                    Limit::perDay(200)->by(optional($request->user())->id ?: $request->ip())
                ];
            });



            return;
        }



        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('money', function (Request $request) {
            return [
                Limit::perMinute(6)->by(optional($request->user())->id ?: $request->ip()),
                Limit::perDay(10)->by(optional($request->user())->id ?: $request->ip())
            ];
        });
    }
}

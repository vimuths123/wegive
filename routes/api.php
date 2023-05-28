<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomEmailDomainController;
use App\Http\Controllers\DomainAliasController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FundraiserController;
use App\Http\Controllers\GivelistController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ImpactNumberController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\NeonIntegrationController;
use App\Http\Controllers\NpoDashboardController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OtherUserController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SalesforceIntegrationController;
use App\Http\Controllers\ScheduledDonationController;
use App\Http\Controllers\TilledController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ZapierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('auth/token', [AuthController::class, 'issueToken']);
Route::delete('auth/token', [AuthController::class, 'revokeToken']);
Route::post('auth/register', [AuthController::class, 'createUser']);

Route::prefix('invites')->middleware('auth:sanctum')->group(
    function () {
        Route::post('', [InviteController::class, 'createInvite']);
        Route::post('{token}', [InviteController::class, 'acceptInvite']);
    }
);

Route::prefix('message_templates')->middleware('auth:sanctum')->group(
    function () {
        Route::post('', [MessageTemplateController::class, 'createTemplate']);
        Route::post('/test', [MessageTemplateController::class, 'sendPreviewTemplate']);
        Route::post('{message_templates}/test', [MessageTemplateController::class, 'sendTestTemplate']);
        Route::get('', [MessageTemplateController::class, 'getTemplates']);
        Route::get('{message_templates}', [MessageTemplateController::class, 'getTemplate']);
        Route::put('{message_templates}', [MessageTemplateController::class, 'updateTemplate']);
        Route::delete('{message_templates}', [MessageTemplateController::class, 'deleteTemplate']);
    }
);

Route::prefix('neon-integrations')->group(
    function () {
        Route::post('create-donor', [NeonIntegrationController::class, 'donorCreated']);
        Route::post('update-donor', [NeonIntegrationController::class, 'donorUpdated']);
        Route::post('create-donation', [NeonIntegrationController::class, 'donationCreated']);
        Route::post('update-donation', [NeonIntegrationController::class, 'donationUpdated']);
    }
);

Route::prefix('salesforce')->group(
    function () {
        Route::post('account', [SalesforceIntegrationController::class, 'handleAccountTrigger']);
        Route::post('contact', [SalesforceIntegrationController::class, 'handleContactTrigger']);
        Route::post('opportunity', [SalesforceIntegrationController::class, 'handleOpportunityTrigger']);

        Route::post('npe01__OppPayment__c', [SalesforceIntegrationController::class, 'handlePaymentTrigger']);
        Route::post('campaign', [SalesforceIntegrationController::class, 'handleCampaignTrigger']);

        Route::post('npsp__General_Accounting_Unit__c', [SalesforceIntegrationController::class, 'handleGAUTrigger']);
        Route::post('npsp__Allocation__c', [SalesforceIntegrationController::class, 'handleGAUAllocationTrigger']);
    }
);

Route::prefix('zapier')->group(
    function () {
        Route::get('test', [ZapierController::class, 'testConnection']);

        Route::prefix('new_donation')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'newDonationSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'newDonationUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donationSample']);
            }
        );

        Route::prefix('new_or_updated_donation')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'newOrUpdatedDonationSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'newOrUpdatedDonationUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donationSample']);
            }
        );

        Route::prefix('updated_donation')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'updatedDonationSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'updatedDonationUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donationSample']);
            }
        );

        Route::prefix('new_donor')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'newDonorSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'newDonorUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donorSample']);
            }
        );

        Route::prefix('updated_donor')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'updatedDonorSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'updatedDonorUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donorSample']);
            }
        );

        Route::prefix('new_or_updated_donor')->group(
            function () {
                Route::post('subscribe', [ZapierController::class, 'newOrUpdatedDonorSubscribe']);
                Route::delete('unsubscribe', [ZapierController::class, 'newOrUpdatedDonorUnsubscribe']);
                Route::get('sample', [ZapierController::class, 'donorSample']);
            }
        );
    }
);

Route::prefix('verification')->group(function () {
    Route::post('send-verification', [VerificationController::class, 'sendVerificationCode']);
    Route::post('validate-email', [VerificationController::class, 'validateEmail']);
    Route::post('validate-phone', [VerificationController::class, 'validatePhone']);
    Route::post('', [VerificationController::class, 'signInWithCode']);
});

Route::prefix('password')->group(function () {
    Route::post('forgot', [PasswordResetController::class, 'create']);
    Route::get('find/{token}', [PasswordResetController::class, 'find']);
    Route::post('reset', [PasswordResetController::class, 'reset']);
});

Route::prefix('webhooks')->group(function () {
    Route::post('tilled_payment_succeeded', [TilledController::class, 'paymentSucceeded']);
    Route::post('tilled_payment_failed', [TilledController::class, 'paymentFailed']);
});

Route::get('all-feed', [UserController::class, 'allFeed']);

Route::prefix('interests')->middleware('auth:sanctum')->group(function () {
    Route::post('', [InterestController::class, 'store']);
    Route::delete('{interest}', [InterestController::class, 'destroy']);
});

Route::prefix('households')->group(function () {
    Route::put('{household}', [HouseholdController::class, 'updateHousehold']);
    Route::post('', [HouseholdController::class, 'createHousehold']);
});

# User Related Routes
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('', [UserController::class, 'index']);

    # Current User Routes
    Route::prefix('me')->group(function () {
        Route::put('login', [UserController::class, 'updateCurrentLogin']);
        Route::put('logins/{login}', [UserController::class, 'updateLogin']);
        Route::put('current-login', [UserController::class, 'setCurrentLogin']);
        Route::post('answers', [UserController::class, 'answerCustomQuestions']);
        Route::post('transactions/{transaction}/attach-payment-method', [UserController::class, 'attachPaymentMethodFromTransaction']);

        Route::prefix('addresses')->group(function () {
            Route::post('', [UserController::class, 'createAddress']);
            Route::put('{address}', [UserController::class, 'updateAddress'])->middleware('can:update,address');
            Route::delete('{address}/delete', [UserController::class, 'deleteAddress'])->middleware('can:delete,address');
        });

        Route::prefix('households')->group(function () {
            Route::get('{household}', [UserController::class, 'getHousehold']);
        });

        Route::get('total-fundraised', [UserController::class, 'totalFundraisedGraph']);

        Route::get('total-fundraised', [UserController::class, 'totalFundraisedGraph']);
        Route::get('total-given', [UserController::class, 'totalGivenGraph']);
        Route::get('tax-documents', [UserController::class, 'taxDocuments']);
        Route::get('giving-history-document', [UserController::class, 'givingHistoryDocument']);

        Route::get('following-activity', [UserController::class, 'followingActivity']);
        Route::get('following-feed', [UserController::class, 'followingFeed']);

        Route::post('wegive-premium', [UserController::class, 'contactMe']);
        Route::post('manage-charity', [UserController::class, 'manageCharity']);
        Route::post('onboarding/{organization}', [UserController::class, 'applyForOnboarding']);

        Route::put('givelist-recurring-giving', [UserController::class, 'updateGivelistRecurringGiving']);

        Route::get('', [UserController::class, 'showCurrentUser']);
        Route::put('', [UserController::class, 'updateCurrentUser']);
        Route::put('wegive-config', [UserController::class, 'updateWegiveConfig']);

        Route::put('password', [UserController::class, 'updatePassword']);

        Route::get('accounts', [UserController::class, 'accounts']);
        Route::get('stats', [UserController::class, 'stats']);
        Route::get('actions', [UserController::class, 'actions']);

        Route::get('giving-by-category', [UserController::class, 'givingByCategory']);
        Route::get('fundraisers', [UserController::class, 'fundraisers']);
        Route::get('activity', [UserController::class, 'activity']);
        Route::get('donor-activity', [UserController::class, 'donorActivity']);
        Route::get('fundraiser-history', [UserController::class, 'fundraiserHistory']);

        Route::put('preferred-payment', [UserController::class, 'setPreferredPayment']);
        Route::delete('current-login', [UserController::class, 'removeCurrentLogin']);

        Route::put('avatar', [UserController::class, 'uploadAvatar']);

        Route::get('categories', [UserController::class, 'myCategories']);
        Route::put('categories', [UserController::class, 'setPreferredCategories']);
        Route::put('cards/sync', [UserController::class, 'syncCards']);
        Route::put('banks/sync', [UserController::class, 'syncBanks']);

        Route::get('impact', [UserController::class, 'impact']);

        Route::get('transactions', [UserController::class, 'myTransactions']);
        Route::get('fund-history', [UserController::class, 'fundHistory']);

        Route::get('transactions/organizations/{organization}', [UserController::class, 'myTransactionsToOrganization']);
        Route::get('transactions/givelists/{givelist}', [UserController::class, 'myTransactionsToGivelist']);
        Route::get('scheduled-donations', [UserController::class, 'scheduledDonations']);
        Route::put('scheduled-donations', [UserController::class, 'updateScheduledDonations']);
        Route::post('add-funds', [UserController::class, 'addFunds'])->middleware(['auth:sanctum', 'throttle:money']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::post('give', [UserController::class, 'giveToCharity'])->middleware(['auth:sanctum', 'throttle:money']);
        Route::post('give/multiple', [UserController::class, 'giveToMultipleCharities'])->middleware(['auth:sanctum', 'throttle:money']);

        Route::post('accept-request/{id}', [UserController::class, 'acceptRequest']);
        Route::post('deny-request/{id}', [UserController::class, 'denyRequest']);

        Route::delete('card/{card}', [CardController::class, 'destroy']);
        Route::delete('bank/{bank}', [BankController::class, 'destroy']);
    });

    # Other User Routes
    Route::prefix('{user}')->group(function () {
        Route::get('', [OtherUserController::class, 'show']);
        Route::post('give', [OtherUserController::class, 'give'])->middleware(['auth:sanctum', 'throttle:money']);
        Route::post('follow', [OtherUserController::class, 'follow']);
        Route::post('unfollow', [OtherUserController::class, 'unfollow']);
        Route::get('impact', [UserController::class, 'impactStories']);
    });
});

Route::post('users/me/card', [CardController::class, 'store']);
Route::put('users/me/card/{card}', [CardController::class, 'update'])->middleware('auth:sanctum');

Route::post('users/me/bank', [BankController::class, 'store']);;
Route::get('users/me/plaid-link', [UserController::class, 'getPlaidLink']);
Route::post('users/me/plaid-public-token', [UserController::class, 'convertPublicTokenToTilled']);

# Organization
Route::prefix('organizations')->group(function () {
    Route::get('', [OrganizationController::class, 'index']);
    Route::get('domain-aliases', [OrganizationController::class, 'getOrganizationByDomainAlias']);

    Route::prefix('{organization}')->group(function () {
        Route::post('logo', [OrganizationController::class, 'uploadAvatar'])->middleware(['auth:sanctum', 'can:update,organization']);

        Route::get('impact-graph', [OrganizationController::class, 'impactGraph'])->middleware('auth:sanctum');
        Route::get('campaigns/{campaign}/progress-bar', [OrganizationController::class, 'campaignProgressBar']);
        Route::get('campaigns/{campaign}', [OrganizationController::class, 'showCampaign']);
        Route::get('checkouts/{checkout}', [OrganizationController::class, 'showCheckout']);

        Route::get('', [OrganizationController::class, 'show']);
        Route::get('elements/{element}', [OrganizationController::class, 'getElement']);

        Route::get('stats', [OrganizationController::class, 'stats']);
        Route::get('my-activity', [OrganizationController::class, 'myActivity'])->middleware('auth:sanctum');

        Route::get('impact-numbers', [OrganizationController::class, 'impactNumbers']);
        Route::post('give-as-guest', [OrganizationController::class, 'giveAsGuest']);

        Route::get('elements', [OrganizationController::class, 'getElements'])->middleware(['auth:sanctum', 'can:update,organization']);;

        Route::get('fundraisers', [OrganizationController::class, 'fundraisers']);
        Route::post('fundraisers', [OrganizationController::class, 'createFundraiser'])->middleware('auth:sanctum');
        Route::post('fundraisers/{fundraiser}', [OrganizationController::class, 'updateFundraiser'])->middleware('auth:sanctum');

        Route::post('give', [OrganizationController::class, 'give'])->middleware(['auth:sanctum', 'throttle:money']);
        Route::post('add', [OrganizationController::class, 'addToGiving'])->middleware('auth:sanctum');
        Route::put('scheduled_donations/{scheduled_donation}', [OrganizationController::class, 'updateGiving'])->middleware('auth:sanctum');

        Route::delete('scheduled_donations/{scheduled_donation}', [OrganizationController::class, 'removeFromGiving'])->middleware('auth:sanctum');
    });
});

# NPO Dasboard
Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {

    Route::get('custom-email-domains/{custom_email_domain}', [CustomEmailDomainController::class, 'show']);
    Route::post('custom-email-domains/{custom_email_domain}/addresses', [CustomEmailDomainController::class, 'addAddress']);
    Route::delete('custom-email-domains/{custom_email_domain}/addresses/{custom_email_address}', [CustomEmailDomainController::class, 'deleteAddress']);
    Route::put('custom-email-domains/{custom_email_domain}/addresses/{custom_email_address}', [CustomEmailDomainController::class, 'updateAddress']);

    Route::get('custom-email-domains', [CustomEmailDomainController::class, 'index']);
    Route::post('custom-email-domains', [CustomEmailDomainController::class, 'createCustomDomain']);
    Route::get('domain-aliases', [DomainAliasController::class, 'index']);
    Route::post('domain-aliases', [DomainAliasController::class, 'store']);
    Route::post('domain-aliases/{domain_alias}', [DomainAliasController::class, 'renewSiteCertificate']);
    Route::put('scheduled_donations/{scheduled_donation}', [NpoDashboardController::class, 'updateRecurringPlan']);
    Route::delete('scheduled_donations/{scheduled_donation}', [NpoDashboardController::class, 'removeRecurringPlan']);

    Route::put('donor-portal', [OrganizationController::class, 'updateDonorPortal']);
    Route::put('checkout/{checkout}', [OrganizationController::class, 'updateCheckout']);
    Route::post('programs', [OrganizationController::class, 'createProgram']);
    Route::put('programs/{program}', [OrganizationController::class, 'updateProgram']);
    Route::delete('programs/{program}', [OrganizationController::class, 'deleteProgram']);
    Route::get('neon-integration', [OrganizationController::class, 'getNeonIntegration']);
    Route::put('neon-integration', [OrganizationController::class, 'updateNeonIntegration']);
    Route::get('salesforce-integration', [OrganizationController::class, 'getSalesforceIntegration']);
    Route::put('salesforce-integration', [OrganizationController::class, 'updateSalesforceIntegration']);
    Route::get('salesforce-integration/test', [OrganizationController::class, 'testSalesforceIntegration']);
    Route::delete('salesforce-integration', [OrganizationController::class, 'deleteSalesforceIntegration']);
    Route::get('donor-perfect-integration', [OrganizationController::class, 'getDonorPerfectIntegration']);

    Route::put('donor-perfect-integration', [OrganizationController::class, 'updateDonorPerfectIntegration']);

    Route::delete('donor-perfect-integration', [OrganizationController::class, 'deleteDonorPerfectIntegration']);


    Route::get('blackbaud-integration', [OrganizationController::class, 'getBlackbaudIntegration']);
    Route::put('blackbaud-integration', [OrganizationController::class, 'updateBlackbaudIntegration']);
    Route::get('blackbaud-integration/test', [OrganizationController::class, 'testBlackbaudIntegration']);
    Route::delete('blackbaud-integration', [OrganizationController::class, 'deleteBlackbaudIntegration']);

    Route::post('neon-mapping-rules', [OrganizationController::class, 'setNeonMappingRules']);
    Route::delete('invites/{invite}', [NpoDashboardController::class, 'deleteInvite']);
    Route::delete('members/{user}', [NpoDashboardController::class, 'deleteTeamMember']);
    Route::delete('custom_questions/{custom_question}', [NpoDashboardController::class, 'deleteCustomQuestion']);

    Route::prefix('campaigns')->group(function () {
        Route::get('', [NpoDashboardController::class, 'getCampaigns']);
        Route::post('', [NpoDashboardController::class, 'createCampaign']);
        Route::get('{campaign}', [NpoDashboardController::class, 'getCampaign']);
        Route::get('{campaign}/payments', [NpoDashboardController::class, 'getCampaignPayments']);
        Route::get('{campaign}/elements', [NpoDashboardController::class, 'getCampaignElements']);
        Route::post('{campaign}', [NpoDashboardController::class, 'updateCampaign']);
        Route::delete('{campaign}', [NpoDashboardController::class, 'deleteCampaign']);
    });

    Route::prefix('elements')->group(function () {
        Route::post('', [NpoDashboardController::class, 'createElement']);
        Route::get('{element}', [NpoDashboardController::class, 'getElement']);
        Route::get('{element}/payments', [NpoDashboardController::class, 'getElementPayments']);
    });

    Route::prefix('email-templates')->group(function () {
        Route::get('', [EmailTemplateController::class, 'index']);
        Route::post('', [EmailTemplateController::class, 'store']);
        Route::get('{email_template}', [EmailTemplateController::class, 'show']);
        Route::delete('{email_template}', [EmailTemplateController::class, 'destroy']);
        Route::put('{email_template}', [EmailTemplateController::class, 'update']);
    });

    Route::prefix('impact-numbers')->group(function () {
        Route::get('', [ImpactNumberController::class, 'index']);
        Route::post('', [ImpactNumberController::class, 'store']);
        Route::put('{impactNumber}', [ImpactNumberController::class, 'update']);
        Route::get('{impactNumber}', [NpoDashboardController::class, 'showImpactNumber']);
        Route::delete('{impactNumber}', [ImpactNumberController::class, 'destroy']);
        Route::post('{impactNumber}/tag', [NpoDashboardController::class, 'tagImpactNumberDonors']);
        Route::put('{impactNumber}/tag/toggle', [NpoDashboardController::class, 'toggleImpactNumberDonors']);
    });
    Route::post('/posts/{post}/tag', [NpoDashboardController::class, 'tagPostDonors']);
    Route::put('/posts/{post}/tag/toggle', [NpoDashboardController::class, 'togglePostDonors']);

    Route::get('donors', [NpoDashboardController::class, 'donors']);
    Route::get('donors/export', [NpoDashboardController::class, 'exportDonors']);

    Route::prefix('transactions')->group(function () {
        Route::get('export', [NpoDashboardController::class, 'exportTransactions']);
        Route::get('export-all', [NpoDashboardController::class, 'exportAllTransactions']);

        Route::get('{transaction}', [NpoDashboardController::class, 'getTransaction']);
        Route::get('{transaction}/receipt', [NpoDashboardController::class, 'downloadTransactionReceipt']);
        Route::post('{transaction}/refund', [NpoDashboardController::class, 'refundTransaction']);
    });

    Route::prefix('scheduled_donations')->group(function () {
        Route::get('', [NpoDashboardController::class, 'getScheduledDonations']);

        Route::get('{scheduled_donation}', [NpoDashboardController::class, 'getScheduledDonation']);
    });

    Route::put('wegive-config', [OrganizationController::class, 'updateWegiveConfig']);
    Route::post('avatar', [NpoDashboardController::class, 'uploadAvatar']);
    Route::post('add-admin', [NpoDashboardController::class, 'addAdmin']);

    Route::post('banner', [NpoDashboardController::class, 'uploadBanner']);
    Route::post('thumbnail', [NpoDashboardController::class, 'uploadThumbnail']);
    Route::post('fund', [NpoDashboardController::class, 'createFund']);
    Route::put('fund/{fund}', [NpoDashboardController::class, 'updateFund']);

    Route::get('', [NpoDashboardController::class, 'show']);
    Route::put('', [NpoDashboardController::class, 'update']);
    Route::get('recurring-donations-total', [NpoDashboardController::class, 'recurringDonationsTotal']);
    Route::get('gross-donation-volume', [NpoDashboardController::class, 'grossDonationVolume']);
    Route::get('gross-donation-volume-graph', [NpoDashboardController::class, 'grossDonationVolumeGraph']);
    Route::get('net-donation-volume', [NpoDashboardController::class, 'netDonationVolume']);
    Route::get('new-donors', [NpoDashboardController::class, 'newDonors']);
    Route::get('first-time-donors', [NpoDashboardController::class, 'firstTimeDonors']);
    Route::get('recurring-donors', [NpoDashboardController::class, 'recurringDonors']);
    Route::get('non-recurring-donors', [NpoDashboardController::class, 'nonRecurringDonors']);
    Route::get('returning-donors', [NpoDashboardController::class, 'returningDonors']);
    Route::get('recent-donations', [NpoDashboardController::class, 'recentDonations']);

    Route::get('payments', [NpoDashboardController::class, 'payments']);
    Route::get('payouts', [NpoDashboardController::class, 'payouts']);
    Route::get('payouts/{payoutId}', [NpoDashboardController::class, 'getPayout']);
    Route::get('payouts/{payoutId}/statement', [NpoDashboardController::class, 'getPayoutStatement']);
    Route::get('payouts/{payoutId}/balance-transactions', [NpoDashboardController::class, 'getPayoutBalanceTransactions']);

    Route::get('balances', [NpoDashboardController::class, 'balances']);

    Route::get('disputes', [NpoDashboardController::class, 'disputes']);
    Route::get('transactions', [NpoDashboardController::class, 'transactions']);

    Route::get('fundraisers', [NpoDashboardController::class, 'fundraisers']);
    Route::post('fundraisers', [NpoDashboardController::class, 'createFundraiser']);

    Route::get('posts', [NpoDashboardController::class, 'posts']);
    Route::get('balances', [NpoDashboardController::class, 'balances']);
    Route::prefix('fundraisers')->group(function () {

        Route::prefix('{fundraiser}')->group(function () {
            Route::get('products', [FundraiserController::class, 'products']);
            Route::get('purchases', [FundraiserController::class, 'purchases']);
            Route::get('', [FundraiserController::class, 'show']);
        });
    });

    Route::prefix('donor')->group(function () {
        Route::get('{donor}', [NpoDashboardController::class, 'showDonor']);
        Route::put('{donor}', [NpoDashboardController::class, 'updateDonor']);
        Route::post('{donor}/charge', [NpoDashboardController::class, 'chargeDonor']);
        Route::post('{donor}/record-donation', [NpoDashboardController::class, 'recordDonation']);
        Route::post('{donor}/addresses', [NpoDashboardController::class, 'createDonorAddress']);
        Route::put('{donor}/addresses/{address}', [NpoDashboardController::class, 'updateDonorAddress']);
        Route::delete('{donor}/addresses/{address}', [NpoDashboardController::class, 'deleteDonorAddress']);
        Route::post('{donor}/add-card', [NpoDashboardController::class, 'addCardToDonor']);
        Route::post('{donor}/add-bank', [NpoDashboardController::class, 'addBankToDonor']);
    });
});

Route::post('donors/avatar', [DonorController::class, 'uploadAvatar'])->middleware(['auth:sanctum']);

# Givelist
Route::prefix('givelists')->group(function () {
    Route::prefix('{givelist}')->group(function () {
        Route::get('fundraisers', [GivelistController::class, 'fundraisers']);
        Route::get('', [GivelistController::class, 'show']);
        Route::post('', [GivelistController::class, 'update'])->middleware('auth:sanctum')->middleware('can:update,givelist');
        Route::delete('', [GivelistController::class, 'destroy'])->middleware('can:delete,givelist');
        Route::post('give', [GivelistController::class, 'give'])->middleware(['auth:sanctum', 'throttle:money']);
        Route::post('add', [GivelistController::class, 'addToGiving'])->middleware('auth:sanctum');
        Route::put('update', [GivelistController::class, 'updateGiving'])->middleware('auth:sanctum');
        Route::delete('remove', [GivelistController::class, 'removeFromGiving'])->middleware('auth:sanctum');
    });

    Route::get('', [GivelistController::class, 'index']);
    Route::post('', [GivelistController::class, 'store'])->middleware('auth:sanctum');
    Route::get('burpeesforvets-fund-2021/feed', [RssFeedController::class, 'givelistFeed']);
});

# Category
Route::prefix('categories')->group(function () {
    Route::get('', [CategoryController::class, 'index']);
    Route::get('featured', [CategoryController::class, 'featuredIndex']);
    Route::get('grouped', [CategoryController::class, 'groupedCategories']);

    Route::prefix('{category}')->group(function () {
        Route::get('', [CategoryController::class, 'show']);
    });
});

# Checkout
Route::prefix('checkouts')->group(function () {
    Route::prefix('{checkout}')->group(function () {
        Route::post('banner', [CheckoutController::class, 'uploadBanner'])->middleware('auth:sanctum')->middleware('can:update,checkout');
    });
});

# Checkout
Route::prefix('transactions')->group(function () {
    Route::prefix('{transaction}')->group(function () {
        Route::post('tribute', [TransactionController::class, 'tribute'])->middleware('auth:sanctum');
    });
});

# Fundraisers

Route::prefix('fundraisers')->group(function () {
    Route::get('', [FundraiserController::class, 'index']);
    Route::post('', [FundraiserController::class, 'store'])->middleware(['auth:sanctum']);

    Route::prefix('{fundraiser}')->group(function () {

        Route::get('', [FundraiserController::class, 'show']);
        Route::post('give', [FundraiserController::class, 'give'])->middleware(['auth:sanctum', 'throttle:money']);
    });
});

Route::prefix('scheduled_donations')->group(function () {
    Route::prefix('{scheduled_donation}')->middleware('auth:sanctum')->group(function () {
        Route::put('', [ScheduledDonationController::class, 'update'])->middleware('can:update,scheduled_donation');;
    });
});

# Post
Route::prefix('posts')->group(function () {
    Route::get('', [PostController::class, 'index']);
    Route::post('', [PostController::class, 'store'])->middleware('auth:sanctum');
    Route::get('{post}', [PostController::class, 'show']);
    Route::post('{post}/comment', [PostController::class, 'comment'])->middleware('auth:sanctum');
    Route::delete('{post}/media/{media}', [PostController::class, 'removeMedia'])->middleware(['auth:sanctum', 'can:update,post']);

    Route::post('{post}', [PostController::class, 'update'])->middleware('auth:sanctum')->middleware('can:update,post');
    Route::delete('{post}', [PostController::class, 'destroy'])->middleware('auth:sanctum')->middleware('can:delete,post');
});

Route::get('auth/login/{provider}', [AuthController::class, 'socialRedirect'])
    ->where('provider', AuthController::SOCIAL_PROVIDER_REGEX)
    ->name('socialRedirect');

Route::get('auth/login/{provider}/callback', [AuthController::class, 'socialLogin'])
    ->where('provider', AuthController::SOCIAL_PROVIDER_REGEX)
    ->name('socialLogin');

Route::prefix('mobile')->group(function () {
});

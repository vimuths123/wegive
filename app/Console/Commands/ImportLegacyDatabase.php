<?php

namespace App\Console\Commands;

use App\Models\Bank;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\ScheduledDonation;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Organization;
use App\Models\Givelist;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImportLegacyDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy database into new backend';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    // Key: legacy -> rebuild
    public function handle()
    {
        $this->importUsers();
        $this->importOrganizations();
        $this->importGivelists();
        $this->importCategories();

        $this->importBanks();
        $this->importFollowers();
        $this->importComments();
        $this->importPosts();
        $this->importCompanies();
        $this->importPermissionsAndRoles();
        $this->importScheduledDonations();
        $this->importActionEvents();

        $this->relateCategoriesToOrganizations();
        $this->relateCategoriesToUsers();
        $this->relateOrganizationsToGivelist();
        $this->relateOrganizationsToUsers();
        $this->relateCompaniesToUsers();

        $this->importTransactions();
        $this->importMedia();
    }

    // comments -> comments
    public function importComments()
    {
        // SKIP - only test comments in production
    }

    // companies -> companies
    public function importCompanies()
    {
        // SKIP - no companies in production
    }

    // company_user -> company_user
    public function relateCompaniesToUsers()
    {
        // SKIP - no companies in production
    }

    // follower_user -> follower_user
    public function importFollowers()
    {
        $this->info("\nImporting Followers");

        $followables = DB::connection('mysql2')->select('select * from follower_user');
        $bar = $this->output->createProgressBar(count($followables));
        foreach ($followables as $follow) {
            // follower follows the following
            $follower = User::query()->where('uuid', Uuid::fromBytes($follow->follower_uuid)->toString())->first();
            $following = User::query()->with(['followers'])->where('uuid', Uuid::fromBytes($follow->user_uuid)->toString())->first();

            if ($follower && $following && ! $following->followers->contains($follower)) {
                $following->followers()->save($follower);
            }

            $bar->advance();
        }
        $bar->finish();
    }

    // media -> media
    public function importMedia()
    {
        $this->info("\nImporting Media");

        $map = array_flip(AppServiceProvider::MORPH_MAP);
        $map['App\Issue'] = 'category';

        $medias = Media::on('mysql2')->get();
        $bar = $this->output->createProgressBar(count($medias));
        foreach ($medias as $media) {
            $media->model_type = str_replace('\\', '\\Models\\', $media->model_type);
            $model = null;
            $newMedia = $media->replicate();
            $newMedia->model_type = $map[$media->model_type] ?? null;

            $existingMedia = Media::query()->where('uuid', Uuid::fromBytes($media->uuid))->first();
            if ($existingMedia) {
                $newMedia->id = $existingMedia->id;
                $newMedia->exists = true;
            }

            switch ($newMedia->model_type) {
                case 'comment':
                    $model = Comment::query()->where('uuid', Uuid::fromBytes($media->model_uuid))->first();
                    break;

                case 'givelist':
                    $model = Givelist::query()->where('uuid', Uuid::fromBytes($media->model_uuid))->first();
                    break;

                case 'organization':
                    $model = Organization::query()->where('uuid', Uuid::fromBytes($media->model_uuid))->first();
                    break;

                case 'post':
                    $model = Post::query()->where('uuid', Uuid::fromBytes($media->model_uuid))->first();
                    break;

                case 'user':
                    $model = User::query()->where('uuid', Uuid::fromBytes($media->model_uuid))->first();
                    break;

                default:
                    continue 2;
            }

            // Switch collections to new format
            $collectionNameMap = [
                'icon' => 'avatar',
                // 'featured_image' => '?????',
            ];

            if (in_array($newMedia->collection_name, array_keys($collectionNameMap))) {
                $newMedia->collection_name = $collectionNameMap[$newMedia->collection_name];
            }

            // Only save the media if there's a valid relationship
            if ($model) {
                unset($newMedia->model_uuid);
                $newMedia->uuid = Uuid::fromBytes($newMedia->uuid);
                $newMedia->generated_conversions = [];
                $newMedia->model_id = $model->id;
                $newMedia->saveQuietly();
            }

            $bar->advance();
        }
        $bar->finish();
    }

    // App\Console\Commands\ImportLegacyDatabase::importIssueMedia()
    // issues.media -> categories.media
    public static function importIssueMedia($onlySlugs = [])
    {
        $medias = Media::on('mysql2')
            ->where('model_type', 'App\Issue')
            ->select(['*'])
            ->selectRaw("LOWER(CONCAT( SUBSTR(HEX(media.uuid), 1, 8), '-', SUBSTR(HEX(media.uuid), 9, 4), '-', SUBSTR(HEX(media.uuid), 13, 4), '-', SUBSTR(HEX(media.uuid), 17, 4), '-', SUBSTR(HEX(media.uuid), 21))) AS uuid_string")
            ->join('issues', 'media.model_uuid', '=', 'issues.uuid')
            ->when(! empty($only), function ($query) use ($onlySlugs) {
                $query->whereIn('issues.slug', $onlySlugs);
            })
            ->get();

        $count = count($medias);
        echo "\n Found $count number of issue medias.";

        foreach ($medias as $media) {
            $newMedia = $media->replicate();
            $newMedia->model_type = 'category';
            $slug = $media->slug;
            $uuid = $media->uuid_string;

            // One time fix
            if ($slug === 'women-children') {
                $slug = 'children';
            }

            $category = Category::query()->whereNull('parent_id')->where('slug', $slug)->first();
            if (! $category) {
                $category = Category::query()->where('slug', $slug)->first();
            }

            if (! $category) {
                echo "\n Skipping $slug slug because there's no category that matches it.";
                continue;
            }

            $existingMedia = Media::query()->where('uuid', $uuid)->first();
            if ($existingMedia) {
                $newMedia->id = $existingMedia->id;
                $newMedia->exists = true;
            }

            unset($newMedia->model_uuid, $newMedia->description, $newMedia->metadata, $newMedia->primary_color, $newMedia->is_active, $newMedia->slug, $newMedia->generated_conversions, $newMedia->model_id, $newMedia->created_at, $newMedia->updated_at, $newMedia->uuid_string);

            $newMedia->uuid = $uuid;
            $newMedia->generated_conversions = [];
            $newMedia->model_id = $category->id;
            $newMedia->saveQuietly();
        }
    }

    // organizations_users -> organization_user
    public function relateOrganizationsToUsers()
    {
        // SKIP - only one record in production
    }

    // posts -> posts
    public function importPosts()
    {
        $this->info("\nImporting Posts");
        $posts = Post::on('mysql2')
            ->join('organizations_posts', 'post_uuid', '=', 'posts.uuid')
            ->get(['posts.*', 'organizations_posts.organization_uuid as organization_uuid']);

        $bar = $this->output->createProgressBar(count($posts));
        foreach ($posts as $post) {
            $org = null;
            $newPost = $post->replicate();

            $existingPost = Post::query()->where('content', $post->content)->first();
            if ($existingPost) {
                $newPost->exists = true;
                $newPost->id = $existingPost->id;
            }

            if ($post->created_by) {
                $user = User::query()->where('uuid', Uuid::fromBytes($newPost->created_by)->toString())->first();
                $newPost->user()->associate($user);
            }

            if (!$user) {
                // SKIP - User has to exist
                $bar->advance();
                continue;
            }

            if ($post->organization_uuid) {
                $org = Organization::query()->where('uuid', Uuid::fromBytes($newPost->organization_uuid)->toString())->first();
                $newPost->organization()->associate($org);
            }

            if (!$org) {
                // SKIP - Organization is required!
                $bar->advance();
                continue;
            }

            $newPost->uuid = Uuid::fromBytes($newPost->uuid);
            unset($newPost->created_by, $newPost->post_date, $newPost->organization_uuid);
            $newPost->save();
            $bar->advance();
        }
        $bar->finish();
    }

    // user_givings -> scheduled_donations
    public function importScheduledDonations()
    {
        $this->info("\nImporting Scheduled Donations");
        $donations = ScheduledDonation::on('mysql2')->from('user_givings')->withoutGlobalScopes()->get();

        $bar = $this->output->createProgressBar(count($donations));
        foreach ($donations as $donation) {
            $model = null;
            /** @var ScheduledDonation $newDonation */
            $newDonation = $donation->replicate();
            $newDonation->uuid = Uuid::fromBytes($newDonation->uuid)->toString();

            if ($donation->user_uuid) {
                $user = User::query()->where('uuid', Uuid::fromBytes($newDonation->user_uuid)->toString())->first();
                $newDonation->source()->associate($user);
            }

            if ($donation->givelist_uuid) {
                $model = Givelist::query()->where('uuid', Uuid::fromBytes($newDonation->givelist_uuid)->toString())->first();
                $newDonation->destination()->associate($model);
            }

            if ($donation->organization_uuid) {
                $model = Organization::query()->where('uuid', Uuid::fromBytes($newDonation->organization_uuid)->toString())->first();
                $newDonation->destination()->associate($model);
            }

            if (!$model) {
                // SKIP - Must belong to either a Givelist or an Organization
                $bar->advance();
                continue;
            }

            $existingDonation = ScheduledDonation::query()
                ->where('amount', $donation->giving_amount)
                ->where('source_id', $user->id)
                ->where('destination_id', $model->id)
                ->first();
            if ($existingDonation) {
                $newDonation->exists = true;
                $newDonation->id = $existingDonation->id;
            }

            $newDonation->amount = $donation->giving_amount;
            $newDonation->locked = !!!$donation->unlocked;

            unset($newDonation->user_uuid, $newDonation->uuid, $newDonation->givelist_uuid, $newDonation->organization_uuid, $newDonation->giving_amount, $newDonation->unlocked, $newDonation->aggregated_by, $newDonation->is_active);

            $newDonation->save();

            $bar->advance();
        }
        $bar->finish();
    }

    // actions -> action_events
    public function importActionEvents()
    {
        // SKIP - not worth the effort
    }

    // categories -> categories
    public function importCategories()
    {
        $this->info("\nImporting Categories");
        $categories = Category::on('mysql2')->get();
        $bar = $this->output->createProgressBar(count($categories));
        foreach ($categories as $category) {
            $cat = $category->replicate();

            $existingCategory = Category::query()->find($category->id);
            if ($existingCategory) {
                $cat->id = $existingCategory->id;
                $cat->exists = true;
            }

            $cat->save();
            $bar->advance();
        }
        $bar->finish();
    }

    // category_organization -> category_organization
    public function relateCategoriesToOrganizations()
    {
        $this->info("\nRelating Categories to Organizations");

        $uuids = $this->orgUuids();
        $uuids = array_values($uuids);

        $relations = DB::connection('mysql2')->table('category_organization')->whereIn('organization_uuid', $uuids)->get();
        $bar = $this->output->createProgressBar(count($relations));
        foreach ($relations as $relation) {
            $organization = Organization::query()->where('uuid', Uuid::fromBytes($relation->organization_uuid)->toString())->first();
            $category = Category::query()->with('organizations')->find($relation->category_id);

            if ($organization && $category && ! $category->organizations->contains($organization)) {
                $category->organizations()->save($organization);
            }

            $bar->advance();
        }
        $bar->finish();
    }

    // category_user -> category_user
    public function relateCategoriesToUsers()
    {
        // These should be user preferences
        // SKIP - too much effort to relate old issues to old categories and then to new categories
    }

    // bank_accounts -> banks
    public function importBanks()
    {
        $this->info("\nImporting Bank Accounts");
        $banks = Bank::on('mysql2')->withoutGlobalScopes()->from('bank_accounts')->get();

        $bar = $this->output->createProgressBar(count($banks));
        foreach ($banks as $legacyBank) {
            $bank = $legacyBank->replicate();
            $user = User::query()->where('uuid', Uuid::fromBytes($bank->user_uuid)->toString())->first();
            $bank->owner()->associate($user);
            $bar->advance();

            if (!$user) {
                // SKIP - this is required
                continue;
            }

            $existingBank = Bank::query()->withoutGlobalScopes()->where('stripe_id', $bank->stripe_id)->first();
            if ($existingBank) {
                $bank->exists = true;
                $bank->id = $existingBank->id;
            }

            $bank->name = $bank->bank_name;
            $bank->last_four = $bank->last_four_digits;
            $bank->user_agreed = $bank->user_agreed ? now()->startOfCentury() : null;

            unset($bank->uuid, $bank->user_uuid, $bank->bank_name, $bank->last_four_digits);
            $bank->save();
        }
    }

    // permission_role -> ?????
    // permissions -> ?????
    // role_user -> ?????
    // roles -> ?????
    public function importPermissionsAndRoles()
    {
        // SKIP - Not using the old permissions, see users.type
    }

    // givelists_income -> transactions
    // givelists_incomes_transactions -> transactions
    // transactions -> transactions
    // user_events -> transactions
    // user_events_user_organization_transfers -> transactions
    // user_organization_transfers -> transactions
    // user_organization_transfers_transactions -> transactions
    public function importTransactions()
    {
        // This is just a summary table of the others: select('select * from user_events');
        // This is a relationship from the summary table: select('select * from user_events_user_organization_transfers');

        // Only refers to bank account transactions, ie. bank -> wallet
        $transactions = DB::connection('mysql2')->select('select * from transactions');
        $this->info("\nImporting bank account Transactions");
        $bar = $this->output->createProgressBar(count($transactions));
        foreach ($transactions as $transaction) {
            $t = new Transaction();
            $user = User::query()->where('uuid', Uuid::fromBytes($transaction->user_uuid)->toString())->first();
            $banks = $user->banks()->orderBy('primary')->get();

            $existingTransaction = Transaction::query()
                ->where('user_id', $user->id)
                ->where('created_at', $transaction->created_at)
                ->where('destination_id', $user->id)
                ->first();
            if ($existingTransaction) {
                $t = $existingTransaction;
            }

            $t->user()->associate($user);
            $t->owner()->associate($user);
            $t->source()->associate($banks->first() ?? $user);
            $t->destination()->associate($user);
            $t->created_at = $transaction->created_at;
            $t->updated_at = $transaction->updated_at;
            $t->amount = $transaction->giving_amount;
            $t->fee = $transaction->fee;
            $t->description = $transaction->description ?? '';
            $t->status = Transaction::STATUS_SUCCESS;
            $t->save();

            $bar->advance();

            // $table->string('correlation_id')->nullable()->index();
        }
        $bar->finish();

        // Records every time a user gives to an organization, ie. wallet -> org
        $userOrganizationTransfers = DB::connection('mysql2')->select('select * from user_organization_transfers');
        // Useless table // $userOrganizationTransfersTransactions = DB::connection('mysql2')->select('select * from user_organization_transfers_transactions');
        $this->info("\nImporting organization transactions");
        $bar = $this->output->createProgressBar(count($userOrganizationTransfers));
        foreach ($userOrganizationTransfers as $userOrgTransfer) {
            if ($userOrgTransfer->user_uuid === null || $userOrgTransfer->organization_uuid === null) {
                // SKIP - These are required
                $bar->advance();
                continue;
            }

            $t = new Transaction();
            $user = User::query()->where('uuid', Uuid::fromBytes($userOrgTransfer->user_uuid)->toString())->first();
            $org = Organization::query()->where('uuid', Uuid::fromBytes($userOrgTransfer->organization_uuid)->toString())->first();

            $givelist = null;
            if ($userOrgTransfer->givelist_uuid) {
                $givelist = Givelist::query()->where('uuid', Uuid::fromBytes($userOrgTransfer->givelist_uuid)->toString())->first();
            }

            if (!$org) {
                // SKIP - This is required
                $bar->advance();
                continue;
            }

            $existingTransaction = Transaction::query()
                ->where('user_id', $user->id)
                ->where('created_at', $userOrgTransfer->created_at)
                ->where('destination_id', $org->id)
                ->first();
            if ($existingTransaction) {
                $t = $existingTransaction;
            }

            $t->user()->associate($user);
            $t->owner()->associate($user);
            $t->source()->associate($user);
            $t->destination()->associate($org);
            $t->givelist()->associate($givelist);
            $t->created_at = $userOrgTransfer->created_at;
            $t->updated_at = $userOrgTransfer->updated_at;
            $t->amount = $userOrgTransfer->amount;
            $t->fee = $userOrgTransfer->fee;
            $t->description = $userOrgTransfer->description ?? '';
            $t->status = Transaction::STATUS_SUCCESS;
            $t->save();
        }
        $bar->finish();

        // I think these are already covered in the above givelist association
        // $givelistIncome = DB::connection('mysql2')->select('select * from givelists_income');
        // $givelistIncomesTransactions = DB::connection('mysql2')->select('select * from givelists_incomes_transactions');
    }

    // organizations_givelists -> givelist_organization
    public function relateOrganizationsToGivelist()
    {
        $this->info("\nRelating Organizations to Givelists");
        $relations =  DB::connection('mysql2')->select('select * from organizations_givelists');

        $bar = $this->output->createProgressBar(count($relations));
        foreach ($relations as $relation) {
            /** @var Givelist $givelist */
            $givelist = Givelist::query()->with('organizations')->where('uuid', Uuid::fromBytes($relation->givelist_uuid)->toString())->first();

            /** @var Organization $organization */
            $organization = Organization::query()->where('uuid', Uuid::fromBytes($relation->organization_uuid)->toString())->first();

            if (!$givelist || !$organization) {
                // SKIP - Both are required!
                $bar->advance();
                continue;
            }

            if (! $givelist->organizations->contains($organization)) {
                $givelist->organizations()->save($organization);
            }

            $bar->advance();
        }
        $bar->finish();
    }

    // givelists -> givelists
    public function importGivelists()
    {
        $this->info("\nImporting Givelists");
        $oldGivelists = Givelist::on('mysql2')->withoutGlobalScopes()->get();

        $bar = $this->output->createProgressBar(count($oldGivelists));
        foreach ($oldGivelists as $oldGivelist) {
            $newGivelist = $oldGivelist->replicate();
            $newGivelist->uuid = Uuid::fromBytes($newGivelist->uuid)->toString();
            unset($newGivelist->is_active);

            $existingGivelist = Givelist::query()->where('uuid', $newGivelist->uuid)->withoutGlobalScopes()->first();
            if ($existingGivelist) {
                $newGivelist->exists = true;
                $newGivelist->id = $existingGivelist->id;
            }

            if ($oldGivelist->user_uuid) {
                $user = User::query()->where('uuid', Uuid::fromBytes($newGivelist->user_uuid)->toString())->first();
                $newGivelist->user()->associate($user);
            }
            unset($newGivelist->user_uuid);
            $newGivelist->save();

            $bar->advance();
        }
        $bar->finish();
    }

    private function orgUuids()
    {
        $organizationRelatedTables = [
            'organizations_givelists',
            'organizations_posts',
            'organizations_users',
            'user_givings',
            'user_organization_transfers',
        ];

        $uuids = [];
        foreach ($organizationRelatedTables as $table) {
            $orgIds = DB::connection('mysql2')->select("select organization_uuid from $table group by organization_uuid");
            foreach ($orgIds as $row) {
                $uuids[$row->organization_uuid] = $row->organization_uuid;
            }
        }

        return $uuids;
    }

    // organizations -> organizations
    public function importOrganizations()
    {
        $this->info("\nImporting Organizations");
        Organization::disableSearchSyncing();

        $uuids = $this->orgUuids();
        $this->info("Found total orgs being used: " . count($uuids));

        $legacyOrgs = Organization::on('mysql2')->whereIn('uuid', $uuids)->get();
        $bar = $this->output->createProgressBar(count($legacyOrgs));

        /** @var Organization $oldOrg */
        foreach ($legacyOrgs as $oldOrg) {
            $newOrg = $oldOrg->replicate();
            $newOrg->uuid = Uuid::fromBytes($newOrg->uuid)->toString();
            unset($newOrg->ein_old, $newOrg->is_active);

            /** @var Organization $newOrg */
            $rebuildOrg = Organization::query()->where('ein', $oldOrg->ein)->first();
            if ($rebuildOrg) {
                $newOrg->id = $rebuildOrg->id;
                $newOrg->exists = true;
            }

            $newOrg->save();
            $bar->advance();
        }
        $bar->finish();
    }

    // users -> users
    public function importUsers()
    {
        $this->info('Importing Users');

        $frequencyMap = ['MONTHLY' => 1, 'WEEKLY' => 2, null => null];

        $oldUsers = User::on('mysql2')->get();

        $bar = $this->output->createProgressBar(count($oldUsers));
        foreach ($oldUsers as $oldUser) {
            $newUser = new User();


            $existingUser = User::query()->where('email', $oldUser->email)->first();
            if ($existingUser) {
                $newUser->id = $existingUser->id;
                $newUser->exists = true;
            }

            $newUser->first_name = $oldUser->first_name;
            $newUser->last_name = $oldUser->last_name;
            $newUser->email = $oldUser->email;
            $newUser->email_verified_at = $oldUser->email_verified_at;
            $newUser->password = $oldUser->password;
            $newUser->active = $oldUser->active || 1;
            $newUser->address1 = $oldUser->address1;
            $newUser->address2 = $oldUser->address2;
            $newUser->city = $oldUser->city;
            $newUser->state = $oldUser->state;
            $newUser->zip = $oldUser->zip;
            $newUser->phone = $oldUser->phone;
            $newUser->scheduled_donation_amount = $oldUser->monthly_payment;
            $newUser->scheduled_donation_frequency = $frequencyMap[$oldUser->giving_frequency];
            $newUser->next_scheduled_donation_at = $oldUser->next_giving_date;
            $newUser->is_public = $oldUser->is_public;

            $newUser->uuid = Uuid::fromBytes($oldUser->uuid)->toString();
            $newUser->remember_token = $oldUser->remember_token;
            $newUser->created_at = $oldUser->created_at;
            $newUser->updated_at = $oldUser->updated_at;
            $randomNumber = rand(1, 10);
            $newUser->handle = "{$oldUser->first_name}{$oldUser->last_name}{$randomNumber}";
            $newUser->save();


            $bar->advance();
        }
        $bar->finish();
    }
}

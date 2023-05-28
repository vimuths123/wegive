<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\NeonIntegration;
use App\Models\Organization;
use Illuminate\Console\Command;

class ImportNeonCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-neon-campaigns {organization_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $neonIntegration = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->argument('organization_id')) return 0;

        $this->neonIntegration = NeonIntegration::where('organization_id', $this->argument('organization_id'))->firstOrFail();
        $campaignRequest = $this->neonIntegration->get('campaigns', null);

        if ($campaignRequest->failed()) return;

        $campaigns = $campaignRequest->json();

        foreach($campaigns as $c) {
            if ($c['status'] == 'INACTIVE') continue;
            $campaign = new Campaign();
            $campaign->name = $c['name'];
            $campaign->organization()->associate(Organization::find($this->argument('organization_id')));
            $campaign->neon_id = $c['id'];
            $campaign->save();
        }
    }


}

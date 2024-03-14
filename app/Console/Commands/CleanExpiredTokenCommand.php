<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очищаем expired tokens';

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
        $accessDate = date("Y-m-d H:i:s", time() - (config('sanctum.expiration') * 60));
        $refreshDate = date("Y-m-d H:i:s", time() - (config('sanctum.rt_expiration') * 60));

        DB::table('ok_edus_access_tokens')
            ->where('name', 'LIKE', 'access')
            ->where('created_at', '<', $accessDate)
            ->delete();

        DB::table('ok_edus_access_tokens')
            ->where('name', 'LIKE', 'refresh')
            ->where('created_at', '<', $refreshDate)
            ->delete();
    }
}

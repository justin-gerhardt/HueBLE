<?php

namespace App\Console\Commands;

use App\Models\AuthToken;
use Illuminate\Console\Command;

class ListTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists API auth tokens';

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
       $tokens = AuthToken::all(["name", "token", "last_used_at"])->makeVisible(["token"])->toArray();
       $this->table(["name", "token", "last_used"], $tokens);
        return 0;
    }
}

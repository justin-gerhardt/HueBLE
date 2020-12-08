<?php

namespace App\Console\Commands;

use App\Models\AuthToken;
use Illuminate\Console\Command;

class DeleteToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:delete {id : A token name or value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an API auth token';

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
        $id = $this->argument('id');
        $tokens = AuthToken::where("token", $id)->orWhere("name", $id);
        if ($tokens->count() === 0) {
            $this->error("That token doesn't exist");
            return 1;
        }
        $tokens->delete();
        return 0;
    }
}

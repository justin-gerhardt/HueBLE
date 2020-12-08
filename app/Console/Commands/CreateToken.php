<?php

namespace App\Console\Commands;

use App\Models\AuthToken;
use Illuminate\Console\Command;

class CreateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:create {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API auth token';

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
        $name = $this->argument('name');
        if ($name !== null && AuthToken::where("name", $name)->exists()) {
            $this->error("A token with that name already exists");
            return 1;
        }
        $token = AuthToken::create(["name" => $name]);
        $this->info("Created token: \"" . $token->token . "\"");
        return 0;
    }
}

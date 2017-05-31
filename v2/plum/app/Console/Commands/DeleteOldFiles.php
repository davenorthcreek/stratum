<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Storage;
use Log;

class DeleteOldFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:deleteOldFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old WorldApp Upload files and old PDFs.';

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
     * @return mixed
     */
    public function handle()
    {
        $now = Carbon::now();
        $threeMonthsAgo = $now->subMinutes(3);
        //if form returned timestamp is lower than 3 months ago timestamp
        $old_prospects = \App\Prospect::where("form_returned", "<", $threeMonthsAgo)->get();
        foreach ($old_prospects as $old) {
            $ref_num = $old->reference_number;
            Log::debug("found old prospect $ref_num");
            if (Storage::disk("local")->exists($ref_num.".txt")) {
                Log::debug("Removing ".$ref_num.".txt");
                //Storage::disk("local")->delete($ref_num.".txt");
            }
            if (Storage::disk("local")->exists($ref_num.".pdf")) {
                Log::debug("Removing ".$ref_num.".pdf");
                //Storage::disk("local")->delete($ref_num.".pdf");
            }
        }
    }
}

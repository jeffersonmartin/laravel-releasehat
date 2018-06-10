<?php

namespace Jeffersonmartin\Releasehat\Commands;

use Illuminate\Console\Command;

class EnvoyDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releasehat:envoy-deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use Envoy to Deploy to All Environments';

    protected $client;

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

        if(config('releasehat.local_root_dir') == null) {

            // Return error and do not allow command to be run
            $this->error('You have not configured the root directory of your development environment in the `.env` file yet.');
            $this->error('Ex. RELEASEHAT_LOCAL_ROOT_DIR = /Users/jefferson/Sites');

        } else {

            $this->line('');
            $this->info('Use Envoy to Deploy to All Environments');
            $this->line('The list of packages can be configured in `config/releasehat.php`.');
            $this->info('');

            $this->line("<fg=yellow>To avoid merge conflicts, you can only run this command if you don't have any uncommitted files.</>");
            if($this->confirm('Are all of your files committed?')) {

                $this->info('Performing a `git pull` to get the latest copy of all packages');

                // Create a new progress bar for looping through git pull on each package
                $progress_bar = $this->output->createProgressBar(count(config('releasehat.github_package_repo_names'))+1);

                //
                // Git Pull for the Application Repository
                //

                $progress_bar->advance();
                $this->line(" <fg=red>".config('releasehat.github_application_repo_name')."</>");

                if(config('releasehat.local_path_nested') == true) {
                    exec('cd '.config('releasehat.local_root_dir').'/'.config('releasehat.github_application_repo_name').' && git pull');
                } else {
                    exec('cd '.config('releasehat.local_root_dir').'/'.trim(end(explode('/', config('releasehat.github_application_repo_name')))).' && git pull');
                }

                //
                // Loop through packages that are configured in config/releasehat.php
                //

                foreach(config('releasehat.github_package_repo_names') as $package_name) {

                    $progress_bar->advance();
                    $this->line(" <fg=red>".$package_name."</>");

                    if(config('releasehat.local_path_nested') == true) {
                        exec('cd '.config('releasehat.local_root_dir').'/'.$package_name.' && git pull');
                    } else {
                        exec('cd '.config('releasehat.local_root_dir').'/'.trim(end(explode('/', $package_name))).' && git pull');
                    }

                }

                $this->info('All repositories are up-to-date.');
                $this->line('');

                foreach(config('releasehat.envoy_environments') as $environment) {

                    if($this->confirm('Do you want to deploy to the <fg=red>'.$environment.'</> environment?')) {

                        $this->info("Deploying to <fg=red>".$environment."</>");

                        if(config('releasehat.local_path_nested') == true) {
                            exec('cd '.config('releasehat.local_root_dir').'/'.config('releasehat.github_application_repo_name').' && envoy deploy --env='.$environment);
                        } else {
                            exec('cd '.config('releasehat.local_root_dir').'/'.trim(end(explode('/', config('releasehat.github_application_repo_name')))).' && envoy deploy --env='.$environment);
                        }

                    }

                }

                $this->info('');
                $this->line("<fg=red>All Done! The application has been deployed.</>");
                $this->line("<fg=yellow>It's always a good idea to make sure you can reach each site after deployment.</>");
                $this->info('');

            }
        }
    } // end of handle method
}

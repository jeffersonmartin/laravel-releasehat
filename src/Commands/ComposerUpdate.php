<?php

namespace Jeffersonmartin\Releasehat\Commands;

use Illuminate\Console\Command;

class ComposerUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releasehat:composer-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Composer Lock Files with Latest Tagged Releases';

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
            $this->error('You have not configured the root directory of your local environment in the `.env` file yet with the RELEASEHAT_LOCAL_ROOT_DIR variable.');

        } else {

            // Informational Messages
            $this->line('');
            $this->info('Update Composer Lock Files with Latest Tagged Releases');
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

                //
                // Update composer files
                //

                if(config('releasehat.local_path_nested') == true) {
                    $app_repository_path = config('releasehat.local_root_dir').'/'.config('releasehat.github_application_repo_name');
                } else {
                    $app_repository_path = config('releasehat.local_root_dir').'/'.trim(end(explode('/', config('releasehat.github_application_repo_name'))));
                }

                foreach(config('releasehat.envoy_environments') as $environment) {

                    $this->info("Updating <fg=red>composer/composer-".$environment.".lock</> with latest commits.");
                    exec('cd '.$app_repository_path.' && rm composer.json');
                    exec('cd '.$app_repository_path.' && rm composer.lock');
                    exec('cd '.$app_repository_path.' && ln -s composer/composer-'.$environment.'.json composer.json');
                    exec('cd '.$app_repository_path.' && ln -s composer/composer-'.$environment.'.lock composer.lock');
                    exec('cd '.$app_repository_path.' && composer update');

                    exec('cd '.$app_repository_path.' && git add composer/composer-'.$environment.'.json');
                    exec('cd '.$app_repository_path.' && git add composer/composer-'.$environment.'.lock');

                }

                //
                // Set composer back to the local environment
                //

                exec('cd '.$app_repository_path.' && rm composer.json');
                exec('cd '.$app_repository_path.' && rm composer.lock');
                exec('cd '.$app_repository_path.' && ln -s composer/composer-'.config('releasehat.local_env').'.json composer.json');
                exec('cd '.$app_repository_path.' && ln -s composer/composer-'.config('releasehat.local_env').'.lock composer.lock');
                exec('cd '.$app_repository_path.' && composer install');

                //
                // Confirmation Questions
                //

                if($this->confirm('You can perform a git commit manually if needed. Do you want to have the updated composer json/lock files automatically committed?')) {

                    $this->line('');
                    $this->info('Performing a `git commit` for the Updated Composer Files');
                    exec('git commit -m "Update composer json/lock environment files"');
                    exec('git push');

                    $this->info('');
                    $this->info("Success! The composer files have been updated with the latest releases and committed to GitHub.");

                }

                if($this->confirm('You can manually deploy to all environments if needed. Do you want to start the automatic envoy deployment to all environments?')) {

                    $this->info('Executing command `php artisan releasehat:envoy-deploy`. If there is an error, please run this command directly and don\'t try to re-run the composer command.');

                    $this->call('releasehat:envoy-deploy');

                } else {

                    $this->info('');
                    $this->line("<fg=red>All Done! The composer files have been updated with the latest releases.</>");
                    $this->line("<fg=yellow>Note: The composer files have not been committed yet.</>");

                }
            }
        }
    } // end of handle method
}

<?php

namespace Jeffersonmartin\Releasehat\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class GitRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'releasehat:git-release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Github Tag Release (all packages)';

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

        $this->line('');
        $this->info('Create Github Tagged Release');
        $this->line('The list of packages can be configured in `config/releasehat.php`.');
        $this->info('');

        if(config('releasehat.github_access_token') == null) {

            // Return error and do not allow release to be created
            $this->error('You have not configured a Github Personal Access Token in the `.env` file yet. Please generate a personal access token.');
            $this->error('Ex. RELEASEHAT_GITHUB_ACCESS_TOKEN=XXXXXXXXXXXXXXXXXXXXXXX');

        } else {

            $apiClient = new Client([
                'base_uri' => 'https://api.github.com/'
            ]);

            $this->info('GitHub Latest Version Releases');
            $this->line('Just a moment while we connect to the GitHub API to get the latest versions.');

            $progress_bar = $this->output->createProgressBar(count(config('releasehat.github_package_repo_names')));

            $old_version_table_rows = array();

            // Loop through packages that are configured in config/releasehat.php
            foreach(config('releasehat.github_package_repo_names') as $package_name) {

                $apiClient = new Client([
                    'base_uri' => 'https://api.github.com/'
                ]);

                // Connect to Github API and get latest release version number
                $latest_release_response = $apiClient->request('GET', 'repos/'.$package_name.'/releases/latest', [
                    'headers' => [
                        'Authorization' => 'token '.config('releasehat.github_access_token')
                    ]
                ]);

                // If API call was unsuccessful
                if($latest_release_response->getStatusCode() != 200) {

                    $this->error('There was an error getting the latest release for the `'.$package_name.'` package.');

                } else {

                    $progress_bar->clear();
                    $progress_bar->advance();

                    // Parse the output and return info message with latest release details
                    $output = json_decode($latest_release_response->getBody(), true);

                    // Add package version to array for table output below
                    $old_version_table_rows[] = [
                        'package_name' => $package_name,
                        'tag_name' => "<fg=red>".$output['tag_name']."</>",
                        'created_at' => $output['created_at'],
                        'author' => $output['author']['login']
                    ];

                }

            }

            $progress_bar->clear();
            $this->line("<fg=yellow>API Calls Completed</>");
            $this->line('');
            $this->table(['Package Name','Version #','Created At','Created By'],$old_version_table_rows);

            $this->line('');
            if($this->confirm('Would you like to tag a release for all packages? Answer no if you want to tag a specific package.')) {

                // Prompt developer for release version number that will be used for tag number and release name
                $this->line('');
                $release_version = $this->ask('What version number should this release be (including `v`)?');
                $this->line('');
                $this->line('Just a moment while we connect to the GitHub API to create the new release version for each package.');

                $new_progress_bar = $this->output->createProgressBar(count(config('releasehat.github_package_repo_names')));
                $new_version_table_rows = array();

                // Loop through packages that are configured in config/releasehat.php
                foreach(config('releasehat.github_package_repo_names') as $package_name) {

                    $apiClient = new Client([
                        'base_uri' => 'https://api.github.com/'
                    ]);

                    // Connect to Github API and create release
                    $new_release_response = $apiClient->request('POST', 'repos/'.$package_name.'/releases', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/vnd.github.v3+json',
                            'Authorization' => 'token '.config('releasehat.github_access_token')
                        ],
                        'body' => json_encode([
                            'tag_name' => $release_version,
                            'target_commitish' => config('releasehat.origin_branch'),
                            'name' => $release_version,
                            'body' => 'Automated Release Build Using Laravel Artisan Console',
                            'draft' => false,
                            'prerelease' => false
                        ])
                    ]);

                    $new_progress_bar->clear();
                    $new_progress_bar->advance();

                    // Add package version to array for table output below
                    $new_version_table_rows[] = [
                        'package_name' => $package_name,
                        'tag_name' => "<fg=red>".$output['tag_name']."</>",
                        'created_at' => $output['created_at'],
                        'author' => $output['author']['login']
                    ];

                }

                $progress_bar->clear();
                $this->line("<fg=yellow>API Calls Completed</>");
                $this->line('');
                $this->table(['Package Name','Version #','Created At','Created By'],$new_version_table_rows);
                $this->line('');
                $this->info('<fg=red>Success!</> The new release versions have been created for all packages.');
                $this->info('<fg=yellow>Be sure to run the composer update workflow to get the latest releases.</>');
                $this->line('');

            } else {

                $package_name = $this->choice('Which package would you like to release', config('github_package_repo_names'));

                // Prompt developer for release version number that will be used for tag number and release name
                $this->line('');
                $release_version = $this->ask('What version number should this release be (including `v`)?');
                $this->line('');
                $this->line('Just a moment while we connect to the GitHub API to create the new release version for the '.$package_name.' package.');

                $new_version_table_rows = array();

                $apiClient = new Client([
                    'base_uri' => 'https://api.github.com/'
                ]);

                // Connect to Github API and create release
                $new_release_response = $apiClient->request('POST', 'repos/'.$package_name.'/releases', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/vnd.github.v3+json',
                        'Authorization' => 'token '.config('releasehat.github_access_token')
                    ],
                    'body' => json_encode([
                        'tag_name' => $release_version,
                        'target_commitish' => config('releasehat.origin_branch'),
                        'name' => $release_version,
                        'body' => 'Automated Release Build Using Laravel Artisan Console',
                        'draft' => false,
                        'prerelease' => false
                    ])
                ]);

                // Add package version to array for table output below
                $new_version_table_rows[] = [
                    'package_name' => $package_name,
                    'tag_name' => "<fg=red>".$output['tag_name']."</>",
                    'created_at' => $output['created_at'],
                    'author' => $output['author']['login']
                ];

                $progress_bar->clear();
                $this->line("<fg=yellow>API Calls Completed</>");
                $this->line('');
                $this->table(['Package Name','Version #','Created At','Created By'],$new_version_table_rows);
                $this->line('');
                $this->info('<fg=red>Success!</> The new release version has been created.');
                $this->info('<fg=yellow>Be sure to run the composer update workflow to get the latest releases.</>');
                $this->line('');

            }

            if($this->confirm('You can manually update the composer files if needed. Do you want to have the updated composer json/lock files automatically updated?')) {

                $this->info('Executing command `php artisan releasehat:composer-update`. If there is an error, please run this command directly and don\'t try to re-create the release.');

                $this->call('releasehat:composer-update');

            } else {

                $this->info('');
                $this->line("<fg=red>All Done! The package release(s) have been created on Github.</>");
                $this->line("<fg=yellow>Note: The composer files have not been updated yet.</>");

            }

        }

    } // end of handle method
}

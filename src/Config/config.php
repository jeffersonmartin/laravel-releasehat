<?php

return [

    // The top level directory where you clone your Github repositories without
    // a trailing slash
    // Ex. /Users/jefferson/Sites
    'local_root_dir' => env('RELEASEHAT_LOCAL_ROOT_DIR', null),

    // If you clone your Github repositories into a subfolder with the Github
    // account name, then set this to true.
    // Ex. /Users/jefferson/Sites/jeffersonmartin/laravel-releasehat
    // If you clone the repository into the top level directory where you clone
    // your Github repositories, then set this to false (or delete .env line).
    // Ex. /Users/jefferson/Sites/laravel-releasehat
    // If false, the scripts will strip out the account name and slash defined
    // in the arrays below when accessing the local path of each repository.
    'local_path_nested' => env('RELEASEHAT_LOCAL_PATH_NESTED', false),

    // Array of all your deployment environments. Ensure that you have created
    // the composer/composer-{env}.json and composer/composer-{env}.lock files.
    'envoy_environments' => [
        'dev',
        'test',
        'prod'
    ],

    // Get the current environment. Usually this will be set to `dev` in your
    // local .env file. If it's not set in the .env file, we'll assume `prod`
    'local_env' => env('RELEASEHAT_LOCAL_ENV', 'prod'),

    // Set the Github branch name that releases should be tagged from
    'origin_branch' => 'dev',

    // Your GitHub Personal Access Token for accessing private git repositories
    // Note: It's important that you only store this in your `.env` file and not
    // committed in your code repository for security reasons.
    // https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line/
    'github_access_token' => env('RELEASEHAT_GITHUB_ACCESS_TOKEN', null),

    // Set the Github repository name of your Laravel application
    'github_application_repo_name' => 'jeffersonmartin/subscription-app',

    // Array of all of your packages that can be tagged for release
    // Note: Do not include other vendor packages that you don't have ownership
    // for in this array
    // Ex. jeffersonmartin/package-name
    'github_package_repo_names' => [
        'jeffersonmartin/account-package',
        'riverbedlab/subscription-package',
    ],

    // Optional: If you want to set a message of the day banner to override
    // any specific environment messages, you can set it in your .env file.
    'motd' => env('RELEASEHAT_MOTD', null),

];

@servers([
    'app1' => 'envoy@app1.myapplication.net'
])

@setup
    $env = isset($env) ? $env : "dev";

    if($env == 'dev') {
        $servers = ['app1'];
        $domain = "dev.myapplication.net";
        $branch = isset($branch) ? $branch : "dev";
    } elseif ($env == 'test') {
        $servers = ['app1'];
        $domain = "test.myapplication.net";
        $branch = isset($branch) ? $branch : "dev";
    } elseif ($env == 'prod') {
        $servers = ['app1'];
        $domain = "www.myapplication.com";
        $branch = isset($branch) ? $branch : "master";
    }

    $test = 'Connection Successful. Deploy Away!';
    $title = 'My Application';
    $name = 'jeffersonmartin/subscription-app';

    $repo = 'git@github.com:'.$name.'.git';
    $root_dir = '/srv/www';
    $app_dir = $root_dir . '/' . $domain;
    $storage_dir = $app_dir . '/storage';
    $framework_dir = $app_dir . '/storage/framework';
    $release_dir = $app_dir . '/releases';
    $app_symlink = $app_dir . '/current';
    $rollback_symlink = $app_dir . '/rollback';
    $release = 'release_' . date('YmdHis');
@endsetup

{{--
@after
    $hook = 'https://hooks.slack.com/services/xxx/yyy/zzz';
    $channel = '#notifications';
    $options = [
        'username' => 'envoy',
        'icon_emoji' => ':passenger_ship:',
        "attachments" => [
            [
                "title" => $title." ({$domain})",
                "title_link" => "https://{$domain}/",
                "fields" => [
                    [
                        "title" => "Env",
                        "value" => $env,
                        "short" => true
                    ],
                    [
                        "title" => "Branch",
                        "value" => $branch,
                        "short" => true
                    ],
                ],
                "footer" => $name,
                "footer_icon" => "https://www.myapplication.com/assets/img/favicon.png",
                "ts" => time()
            ]
        ],
    ];

    @slack($hook, $channel, null, $options)
@endafter
--}}

@task('test', ['on' => $servers, 'parallel' => true])
    #test
    echo {{ $test }}
@endtask

@task('rollback', ['on' => $servers, 'parallel' => true])
    #rollback
    if [[ -L {{ $rollback_symlink }} && -d {{ $rollback_symlink }} ]]
    then
        ln -nfs $(readlink {{ $rollback_symlink }}) {{ $app_symlink }};
        rm {{ $rollback_symlink }};
    fi

    sudo service php7.0-fpm restart;
    sleep 1;
    php {{ $app_symlink }}/artisan queue:restart;
@endtask

@task('deploy', ['on' => $servers, 'parallel' => true])
    #remove-rollback
    if [[ -L {{ $rollback_symlink }} && -d {{ $rollback_symlink }} ]]
    then
        rm -rf $(readlink {{ $rollback_symlink }});
        rm {{ $rollback_symlink }};
    fi

    #fetch-repo
    if [[ ! -d {{ $app_dir }} ]]
    then
        mkdir {{ $app_dir }};
        chmod ug+rwx {{ $app_dir }};
    fi

    if [[ ! -d {{ $storage_dir }} ]]
    then
        mkdir {{ $storage_dir }};
        chmod ug+rwx {{ $storage_dir }};
    fi

    if [[ ! -d {{ $storage_dir }}/app ]]
    then
        mkdir {{ $storage_dir }}/app;
        chmod ug+rwx {{ $storage_dir }}/app;
    fi

    if [[ ! -d {{ $framework_dir }} ]]
    then
        mkdir {{ $framework_dir }};
        chmod ug+rwx {{ $framework_dir }};
    fi

    if [[ ! -d {{ $framework_dir }}/cache ]]
    then
        mkdir {{ $framework_dir }}/cache;
        chmod ug+rwx {{ $framework_dir }}/cache;
    fi

    if [[ ! -d {{ $framework_dir }}/sessions ]]
    then
        mkdir {{ $framework_dir }}/sessions;
        chmod ug+rwx {{ $framework_dir }}/sessions;
    fi

    if [[ ! -d {{ $framework_dir }}/views ]]
    then
        mkdir {{ $framework_dir }}/views;
        chmod ug+rwx {{ $framework_dir }}/views;
    fi

    if [[ ! -d {{ $storage_dir }}/logs ]]
    then
        mkdir {{ $storage_dir }}/logs;
        chmod ug+rwx {{ $storage_dir }}/logs;
    fi

    if [[ ! -d {{ $release_dir }} ]]
    then
        mkdir {{ $release_dir }};
        chmod ug+rwx {{ $release_dir }};
    fi

    cd {{ $release_dir }};
    git clone -b {{ $branch }} --depth=1 {{ $repo }} {{ $release }};

    #add-storage-symlink
    rm -rf {{ $release_dir }}/{{ $release }}/storage;
    cd {{ $release_dir }}/{{ $release }};
    ln -nfs {{ $storage_dir }} storage;

    #add-env-symlink
    cd {{ $release_dir }}/{{ $release }};
    ln -nfs {{ $app_dir }}/.env .env;

    #run-composer
    cd {{ $release_dir }}/{{ $release }};

    ln -nfs {{ $root_dir }}/auth.json auth.json;

    ln -nfs composer/composer-{{ $env }}.json composer.json;
    ln -nfs composer/composer-{{ $env }}.lock composer.lock;

    composer install --prefer-dist --no-scripts --no-suggest;
    php artisan clear-compiled --env=production;
    #php artisan route:cache --env=production;

    #update-permissions
    cd {{ $release_dir }};
    chmod -R ug+rwx {{ $release }};

    #update-app-symlinks
    if [[ -L {{ $app_symlink }} ]]
    then
        cd $app_dir;
        ln -nfs $(readlink {{ $app_symlink }}) {{ $rollback_symlink }};
    fi

    ln -nfs {{ $release_dir }}/{{ $release }} {{ $app_symlink }};
@endtask

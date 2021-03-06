<?php

namespace CodexShaper\WP;

use CodexShaper\App\User;
use CodexShaper\Database\Database;
use CodexShaper\Database\Facades\DB;
use CodexShaper\WP\Support\Facades\Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

class Application
{
    /**
     * @var app
     */
    protected $app = null;

    /**
     * @var config
     */
    protected $config;

    /**
     * @var db
     */
    protected $db;

    /**
     * @var options
     */
    protected $options;

    /**
     * @var root
     */
    protected $root;

    public function __construct($options = [], ContainerInterface $container = null)
    {
        $this->options = $options;
        
        $this->app = $container;

        if (is_null($this->app)) {
            $this->app = new Container();
            Facade::setFacadeApplication($this->app);
            $this->app->instance(ContainerInterface::class, $this->app);
        }

        $this->app['app'] = $this->app;

        $this->root = __DIR__ . '/../../../../';

        if (! empty($this->options) && isset($this->options['paths']['root'])) {
            $this->root = rtrim($this->options['paths']['root'], "/") . '/';
        }

        if (!isset($this->app['root'])) {
            $this->app['root'] = $this->root;
        }

        $this->config = new Config($this->options);

        $this->setupEnv();
        $this->registerConfig();
        $this->setupDatabase();
        $this->registerProviders();
        $this->registerRequest();
        $this->registerRouter();
        $this->loadRoutes();
    }

    public function getInstance()
    {
        if (!$this->app) {
            return new self();
        }

        return $this->app;
    }

    protected function setupEnv()
    {
        $this->app['env'] = $this->config->get('app.env');
    }

    protected function registerConfig()
    {
        $this->app->bind('config', function () {
            return [
                'app'           => $this->config->get('app'),
                'view.paths'    => $this->config->get('view.paths'),
                'view.compiled' => $this->config->get('view.compiled'),
            ];
        }, true);
    }

    protected function setupDatabase()
    {
        global $wpdb;

        $this->db = new Database([
            'driver'            => 'mysql',
            'host'               => $wpdb->dbhost,
            'database'        => $wpdb->dbname,
            'username'        => $wpdb->dbuser,
            'password'        => $wpdb->dbpassword,
            'prefix'          => $wpdb->prefix,
            'charset'            => $wpdb->charset,
            'collation'     => $wpdb->collate,
        ]);

        $this->db->run();

        $this->app->singleton('db', function () {
            return $this->db;
        });
    }

    protected function registerProviders()
    {
        $providers = $this->config->get('app.providers');

        if( $providers && count($providers) > 0) {
            foreach ($providers as $provider) {
                with(new $provider($this->app))->register();
            }
        }
    }

    protected function registerRequest()
    {
        $this->app->bind(Request::class, function ($app) {
            $request = Request::capture();

            if ($wp_user = wp_get_current_user()) {
                $user = User::find($wp_user->ID);
                $request->merge(['user' => $user]);
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });
            }

            return $request;
        });
    }

    protected function registerRouter()
    {
        if(isset($this->app['router'])) {
            $this->app->instance(\Illuminate\Routing\Router::class, $this->app['router']);
        }  
        $this->app->alias('Route', \CodexShaper\WP\Support\Facades\Route::class);
    }

    protected function loadRoutes($dir = null)
    {
        foreach ( get_option('active_plugins') as $activate_plugin) {
            $dir = $this->root.'../'.dirname($activate_plugin);
           if(is_dir($dir.'/routes')) {
                foreach (glob($dir.'/routes/*.php') as $route) {
                    require_once $route;
                }
            }
        }
    //     if (!$dir) {
    //         $dir = $this->root . 'routes/';
    //     }

    //     if(isset($this->app['router'])) {
            
    //         // $app['router']->group(['middleware' => ['web']], function(){
    //         require $dir.'web.php';
    //         // });

    //         $this->app['router']->group(['prefix' => 'api'], function () use ($dir) {
    //             require $dir.'api.php';
    //         });

    //         $this->app['router']->group(['prefix' => 'wp-admin'], function () use ($dir) {
    //             require $dir.'admin.php';
    //         });
    //     }
    }
}

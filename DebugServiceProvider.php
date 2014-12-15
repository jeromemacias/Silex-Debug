<?php

namespace Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Twig\Extension\DumpExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\DataCollector\DumpDataCollector;
use Symfony\Component\HttpKernel\EventListener\DumpListener;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;

class DebugServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['var_dumper.cloner'] = $app->share(function ($app) {
            $cloner = new VarCloner();

            if (isset($app['debug.max_items'])) {
                $cloner->setMaxItems($app['debug.max_items']);
            }

            if (isset($app['debug.max_string_length'])) {
                $cloner->setMaxString($app['debug.max_string_length']);
            }

            return $cloner;
        });

        $app['data_collector.templates'] = array_merge(
            $app['data_collector.templates'],
            array(array('dump', '@Debug/Profiler/dump.html.twig'))
        );

        $app['data_collector.dump'] = $app->share(function ($app) {
            return new DumpDataCollector($app['stopwatch'], $app['code.file_link_format']);
        });

        $app['data_collectors'] = $app->share($app->extend('data_collectors', function ($collectors, $app) {
            $collectors['dump'] = $app->share(function ($app) {
                return $app['data_collector.dump'];
            });

            return $collectors;
        }));

        $app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
            if (class_exists('\Symfony\Bridge\Twig\Extension\DumpExtension')) {
                $twig->addExtension(new DumpExtension($app['var_dumper.cloner']));
            }

            return $twig;
        }));

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['debug.templates_path'], 'Debug');

            return $loader;
        }));

        $app['debug.templates_path'] = function () {
            $r = new \ReflectionClass('Symfony\Bundle\DebugBundle\DependencyInjection\Configuration');

            return dirname(dirname($r->getFileName())).'/Resources/views';
        };
    }

    public function boot(Application $app)
    {
        // This code is here to lazy load the dump stack. This default
        // configuration for CLI mode is overridden in HTTP mode on
        // 'kernel.request' event
        VarDumper::setHandler(function ($var) use ($app) {
            $dumper = new CliDumper();
            $dumper->dump($app['var_dumper.cloner']->cloneVar($var));
        });

        $app['dispatcher']->addSubscriber(new DumpListener($app['var_dumper.cloner'], $app['data_collector.dump']));
    }
}

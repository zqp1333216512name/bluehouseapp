<?php

namespace Blackhouseapp\Bundle\BluehouseappBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\Definition\Processor;
use Blackhouseapp\Bundle\BluehouseappBundle\DependencyInjection\Driver\DatabaseDriverFactory;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BlackhouseappBluehouseappExtension extends Extension
{
    protected $applicationName = 'bluehouseapp';
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('twig.xml');

        $config = $this->process($config, $container);
        $this->loadDatabaseDriver($config, $loader, $container);
        $classes = isset($config['classes']) ? $config['classes'] : array();
            $this->mapClassParameters($classes, $container);
        $this->mapClassFormType($classes, $container);

        $container->setParameter('bluehouseapp.resource.settings', $config['settings']);
    }
    protected function process(array $config, ContainerBuilder $container)
    {
        // Override if needed.
        return $config;
    }

    protected function loadDatabaseDriver(array $config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        foreach ($config['classes'] as $model => $classes) {
            if (array_key_exists('model', $classes)) {
                DatabaseDriverFactory::get(
                    'orm',
                    $container,
                    $this->applicationName,
                    $model,
                    isset($config['object_manager']) ? $config['object_manager'] : 'default',
                    isset($config['templates'][$model]) ? $config['templates'][$model] : ''
                )->load($classes);
            }
        }




        if ($container->hasParameter('bluehouseapp.config.classes')) {
            $classes = array_merge($config['classes'] , $container->getParameter('bluehouseapp.config.classes'));
        }

        $container->setParameter('bluehouseapp.config.classes', $config['classes'] );

        return array($config, $loader);

    }

    protected function mapClassParameters(array $classes, ContainerBuilder $container)
    {
        foreach ($classes as $model => $serviceClasses) {
            foreach ($serviceClasses as $service => $class) {
                $container->setParameter(
                    sprintf(
                        '%s.%s.%s.class',
                        $this->applicationName,
                        $service === 'form' ? 'form.type' : $service,
                        $model
                    ),
                    $class
                );
            }
        }
    }
    protected function mapClassFormType(array $classes, ContainerBuilder $container)
    {
        foreach ($classes as $model => $serviceClasses) {
            if (array_key_exists('model', $serviceClasses)) {
                if (array_key_exists('form', $serviceClasses)) {

                    $definition = new Definition( $serviceClasses['form']);
                    $definition
                        ->setArguments(array($serviceClasses['model'], array()))
                        ->addTag('form.type', array('alias' => 'bluehouseapp_'.$model));
                    $container->setDefinition('bluehouseapp.form.type.'.$model, $definition);
                }
            }
        }
    }


}

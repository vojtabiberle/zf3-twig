<?php

namespace ZendTwig\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Exception;
use ZendTwig\Module;
use ZendTwig\View\HelperPluginManager;

class TwigHelperPluginManagerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return HelperPluginManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config     = $container->get('Configuration');
        $name       = Module::MODULE_NAME;
        $options    = $envOptions = empty($config[$name]) ? [] : $config[$name];
        $helpers    = empty($options['helpers']) ? [] : $options['helpers'];
        $configs    = empty($helpers['configs']) ? [] : $helpers['configs'];
        $viewHelper = new HelperPluginManager($container, $configs);

        foreach ($configs as $configDefinition) {
            $config = null;
            if (is_string($configDefinition) && class_exists($configDefinition)) {
                /** @var ConfigInterface $config */
                $config = new $configDefinition;

                if (!$config instanceof ConfigInterface) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            'Invalid service manager configuration class provided; received "%s",
                                expected class implementing %s',
                            $configDefinition,
                            ConfigInterface::class
                        )
                    );
                }
            } elseif (is_string($configDefinition) && $container->has($configDefinition)) {
                $config = $container->get($configDefinition);
            } elseif (is_array($configDefinition)) {
                $config = new Config($configDefinition);
            }
            if ($config === null) {
                throw new Exception\RuntimeException(
                    sprintf(
                        'Unable to resolve provided configuration to valid instance of %s',
                        ConfigInterface::class
                    )
                );
            }
            $config->configureServiceManager($viewHelper);
        }

        return $viewHelper;
    }
}

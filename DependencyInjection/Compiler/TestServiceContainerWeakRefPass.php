<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class TestServiceContainerWeakRefPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('test.service_container')) {
            return;
        }

        $privateServices = array();
        $definitions = $container->getDefinitions();

        foreach ($definitions as $id => $definition) {
            if ((!$definition->isPublic() || $definition->isPrivate()) && !$definition->getErrors() && !$definition->isAbstract()) {
                $privateServices[$id] = new ServiceClosureArgument(new Reference($id, ContainerBuilder::IGNORE_ON_UNINITIALIZED_REFERENCE));
            }
        }

        $aliases = $container->getAliases();

        foreach ($aliases as $id => $alias) {
            if (!$alias->isPublic() || $alias->isPrivate()) {
                while (isset($aliases[$target = (string) $alias])) {
                    $alias = $aliases[$target];
                }
                if (isset($definitions[$target]) && !$definitions[$target]->getErrors() && !$definitions[$target]->isAbstract()) {
                    $privateServices[$id] = new ServiceClosureArgument(new Reference($target, ContainerBuilder::IGNORE_ON_UNINITIALIZED_REFERENCE));
                }
            }
        }

        if ($privateServices) {
            $definitions[(string) $definitions['test.service_container']->getArgument(2)]->replaceArgument(0, $privateServices);
        }
    }
}

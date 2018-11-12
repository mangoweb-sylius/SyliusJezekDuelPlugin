<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('mango_sylius_jezek_duel');
		assert($rootNode instanceof ArrayNodeDefinition);

		$rootNode
			->children()
			->scalarNode('server_url')
			->isRequired()
			->cannotBeEmpty()
			->end()
			->integerNode('server_port')
			->defaultValue(21)
			->end()
			->booleanNode('server_ssl')
			->defaultFalse()
			->end()
			->scalarNode('username')
			->isRequired()
			->cannotBeEmpty()
			->end()
			->scalarNode('password')
			->isRequired()
			->cannotBeEmpty()
			->end()
			->scalarNode('folder_for_export_order')
			->defaultValue('out')
			->cannotBeEmpty()
			->end()
			->scalarNode('folder_for_update_products')
			->defaultValue('in')
			->cannotBeEmpty()
			->end()
			->end();

		return $treeBuilder;
	}
}

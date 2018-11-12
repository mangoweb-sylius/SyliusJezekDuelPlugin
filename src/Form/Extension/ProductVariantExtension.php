<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Form\Extension;

use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductVariantExtension extends AbstractTypeExtension
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('jezekIds', TextareaType::class, [
				'label' => 'mango-sylius.form.jezek_ids',
				'required' => false,
			]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExtendedType(): string
	{
		return ProductVariantType::class;
	}
}

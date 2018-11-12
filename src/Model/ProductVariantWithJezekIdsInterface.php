<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Model;

interface ProductVariantWithJezekIdsInterface
{
	public function getJezekIds(): ?string;

	public function setJezekIds(?string $jezekIds): void;
}

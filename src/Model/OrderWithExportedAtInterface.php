<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Model;

interface OrderWithExportedAtInterface
{
	public function getMangoSyliusJezekDuelExportedAt(): ?\DateTime;

	public function setMangoSyliusJezekDuelExportedAt(?\DateTime $mangoSyliusJezekDuelExportedAt): void;
}

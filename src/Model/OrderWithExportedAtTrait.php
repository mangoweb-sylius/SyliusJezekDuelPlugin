<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait OrderWithExportedAtTrait
{
	/**
	 * @var \DateTime|null
	 * @ORM\Column(name="mango_sylius_jezek_duel_exported_at", type="datetime", nullable=true)
	 */
	private $mangoSyliusJezekDuelExportedAt;

	public function getMangoSyliusJezekDuelExportedAt(): ?\DateTime
	{
		return $this->mangoSyliusJezekDuelExportedAt;
	}

	public function setMangoSyliusJezekDuelExportedAt(?\DateTime $mangoSyliusJezekDuelExportedAt): void
	{
		$this->mangoSyliusJezekDuelExportedAt = $mangoSyliusJezekDuelExportedAt;
	}
}

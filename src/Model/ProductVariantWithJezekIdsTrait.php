<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait ProductVariantWithJezekIdsTrait
{
	/**
	 * @var string|null
	 * @Assert\Regex(
	 *     match=true,
	 *     pattern="/^[a-zA-Z0-9,-]*$/",
	 *     groups={"sylius"}
	 * )
	 * @ORM\Column(name="jezek_ids", type="text", nullable=true)
	 */
	private $jezekIds;

	public function getJezekIds(): ?string
	{
		return $this->jezekIds;
	}

	public function setJezekIds(?string $jezekIds): void
	{
		$this->jezekIds = $jezekIds;
	}
}

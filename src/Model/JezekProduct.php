<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Model;

class JezekProduct
{
	/** @var string */
	protected $id;

	/** @var string */
	protected $ean;

	/** @var int */
	protected $price;

	/** @var int */
	protected $stockCount;

	/** @var int */
	protected $vat;

	public function __construct(\SimpleXMLElement $xmlData, $isWithVat = true)
	{
		// Subtr remove { & } wrapping
		$this->id = \substr($xmlData->ID->__toString(), 1, -1);
		$this->ean = $xmlData->EAN->__toString();
		$this->price = (int) (100 * ((float) $xmlData->PRICE));
		$this->vat = (int) ($xmlData->VAT);
		$this->stockCount = (int) ($xmlData->OTHER_STOCKS_COUNT->OTHER_STOCK->STOCK_COUNT);

		if (!$isWithVat) {
			$this->price = (int) ($this->price * ($this->vat / 100 + 1));
		}
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): void
	{
		$this->id = $id;
	}

	public function getEan(): string
	{
		return $this->ean;
	}

	public function setEan(string $ean): void
	{
		$this->ean = $ean;
	}

	public function getPrice(): int
	{
		return $this->price;
	}

	public function setPrice(int $price): void
	{
		$this->price = $price;
	}

	public function getStockCount(): int
	{
		return $this->stockCount;
	}

	public function setStockCount(int $stockCount): void
	{
		$this->stockCount = $stockCount;
	}

	public function getVat(): int
	{
		return $this->vat;
	}

	public function setVat(int $vat): void
	{
		$this->vat = $vat;
	}

	/**
	 * Get price without vat, rounded down to 2 decimal;
	 *
	 * @return float
	 */
	public function getPriceExVat()
	{
		return (int) ($this->price * (100 / ($this->vat + 100)));
	}
}

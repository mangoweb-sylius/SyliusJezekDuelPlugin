<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use MangoSylius\JezekDuelPlugin\Model\JezekProduct;
use MangoSylius\JezekDuelPlugin\Model\ProductVariantWithJezekIdsInterface;
use MangoSylius\JezekDuelPlugin\Service\FtpService;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Repository\ProductVariantRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
	/**
	 * @var FtpService
	 */
	private $ftpService;

	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ProductVariantRepositoryInterface
	 */
	private $productVariantRepository;

	public function __construct(
		FtpService $ftpService,
		EntityManagerInterface $entityManager,
		LoggerInterface $logger,
		ProductVariantRepositoryInterface $productVariantRepository
	) {
		$this->ftpService = $ftpService;
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->productVariantRepository = $productVariantRepository;

		parent::__construct();
	}

	protected function configure()
	{
		$this
			->setName('mango:jezek:update')
			->setDescription('Update products details from FTP.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Jezek product update started at ' . date('Y-m-d H:i:s'));

		$jezekProducts = $this->loadProducts($io);

		$productsVariants = $this->productVariantRepository->findAll();

		$productsVariantsCount = count($productsVariants);
		if ($productsVariantsCount > 0) {
			$io->text($productsVariantsCount . ' products will be updated.');
			$io->newLine(1);
			$io->progressStart($productsVariantsCount);

			foreach ($productsVariants as $productVariant) {
				assert($productVariant instanceof ProductVariantInterface && $productVariant instanceof ProductVariantWithJezekIdsInterface);
				$this->updateProduct($productVariant, $jezekProducts, $io);
				$io->progressAdvance();
			}
			$io->progressFinish();

			$this->entityManager->flush();
			$io->success(
				'Jezek product update finished at ' . date('Y-m-d H:i:s')
				. '. ' . $productsVariantsCount . ' items have been updated.'
			);
		} else {
			$io->success('Nothing update. None product has jezekIds');
		}
	}

	public function updateProduct(ProductVariantInterface $productVariant, array $jezekProducts, SymfonyStyle $io): void
	{
		assert($productVariant instanceof ProductVariantInterface && $productVariant instanceof ProductVariantWithJezekIdsInterface);
		$productVariant->setTracked(true);

		$jezekIds = $productVariant->getJezekIds() ? explode(',', $productVariant->getJezekIds()) : [$productVariant->getCode()];
		$onHandCounts = [];

		foreach ($jezekIds as $jezekId) {
			if (array_key_exists($jezekId, $jezekProducts)) {
				$onHandCounts[] = $jezekProducts[$jezekId]->getStockCount();
			} else {
				$onHandCounts[] = 0;
				$productVariant->setTracked(false);
			}
		}

		$productVariant->setOnHand(min($onHandCounts));
		if (min($onHandCounts) < 0) {
			$productVariant->setTracked(false);
		}

		$this->entityManager->persist($productVariant);
	}

	private function loadProducts(SymfonyStyle $io): array
	{
		$io->text('Start download XML file.');
		$io->newLine();

		$xmlUrl = $this->ftpService->downloadFile();
		$xml = \simplexml_load_file($xmlUrl);
		if (!$xml) {
			$io->error('XMl file not found on FTP.');

			throw new \ErrorException('XMl file not found on FTP.');
		}
		$io->text('XML file downloaded.');
		$io->newLine();

		$isWithVat = (bool) $xml->PRICES_WITH_VAT;

		$products = [];
		foreach ($xml as $tagName => $tagContent) {
			\assert($tagContent instanceof \SimpleXMLElement);
			if ($tagName !== 'ITEM_PRODUCT') {
				continue;
			}

			/** @var JezekProduct $product */
			$product = new JezekProduct($tagContent, $isWithVat);
			$products[$product->getId()] = $product;
		}

		return $products;
	}
}

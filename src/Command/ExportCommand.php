<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Command;

use Doctrine\ORM\EntityManagerInterface;
use MangoSylius\JezekDuelPlugin\Model\OrderWithExportedAtInterface;
use MangoSylius\JezekDuelPlugin\Model\ProductVariantWithJezekIdsInterface;
use MangoSylius\JezekDuelPlugin\Service\FtpService;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Sylius\Component\Taxation\Model\TaxableInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportCommand extends Command
{
	/** @var FtpService */
	private $ftpService;

	/** @var EntityManagerInterface */
	private $entityManager;

	/** @var LoggerInterface */
	private $logger;

	/** @var TaxRateResolver */
	private $rateResolver;

	/**
	 * @var OrderRepositoryInterface
	 */
	private $orderRepository;

	public function __construct(
		FtpService $ftpService,
		EntityManagerInterface $entityManager,
		LoggerInterface $logger,
		TaxRateResolver $rateResolver,
		OrderRepositoryInterface $orderRepository
	) {
		$this->rateResolver = $rateResolver;
		$this->ftpService = $ftpService;
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->orderRepository = $orderRepository;

		parent::__construct();
	}

	protected function configure()
	{
		$this
			->setName('mango:jezek:export')
			->setDescription('Export orders to FTP.');
	}

	protected function getOrdersToExport(): array
	{
		return $this->orderRepository->findBy(
			[
				'checkoutState' => 'completed',
				'mangoSyliusJezekDuelExportedAt' => null,
			],
			['checkoutCompletedAt' => 'ASC']
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Jezek export started at ' . date('Y-m-d H:i:s'));

		$orders = $this->getOrdersToExport();
		$orderCount = count($orders);
		if ($orderCount > 0) {
			$io->text($orderCount . ' orders will be exported.');
			$io->newLine(1);
			$io->progressStart($orderCount);

			$badExport = [];

			foreach ($orders as $order) {
				assert($order instanceof OrderInterface && $order instanceof OrderWithExportedAtInterface);
				$fileNameOnFtp = $order->getNumber() . '.xml';

				$errorMsg = "Error when uploading file $fileNameOnFtp to FTP.";

				try {
					$tempFile = $this->createXml($order);
					$success = $this->ftpService->uploadFile($tempFile, $fileNameOnFtp);
					if ($success) {
						$order->setMangoSyliusJezekDuelExportedAt(new \DateTime());
						$this->entityManager->persist($order);
						$io->progressAdvance();
					} else {
						$this->logger->error($errorMsg);
						$badExport[] = $order->getNumber();
					}
				} catch (\Throwable $e) {
					$this->logger->error($errorMsg, ['exception' => $e]);
					$badExport[] = $order->getNumber();
				}
			}

			$this->entityManager->flush();

			$successfullyExported = $orderCount - count($badExport);
			$errorText = '';
			if (count($badExport) > 0) {
				$errorText = 'Error export ' . count($badExport) . '. Error orders: ' . implode(',', $badExport) . '.';
			}

			$io->newLine(3);
			$io->success(
				'Jezek export finished at ' . date('Y-m-d H:i:s')
				. ' Successfully exported ' . $successfullyExported . ' orders. '
				. $errorText
			);
		} else {
			$io->success('Nothing to export.');
		}
	}

	private function mapOrderToArray(OrderInterface $order): array
	{
		$payment = $order->getPayments()->first();
		assert($payment instanceof PaymentInterface);

		$delivery = $order->getShipments()->first();
		assert($delivery instanceof ShipmentInterface);

		return [
			'ID' => $order->getNumber(),
			'USER_ID' => $order->getCustomer()->getId(),
			'PRICEWITHVAT' => 0,
			'ORDERDATE' => $order->getCheckoutCompletedAt()->format('Y-m-d H:i:s'),
			'ORDERNR' => $order->getNumber(),
			'BILLCOMPANY' => $order->getBillingAddress()->getCompany(),
			'BILLFNAME' => $order->getBillingAddress()->getFirstName(),
			'BILLLNAME' => $order->getBillingAddress()->getLastName(),
			'BILLEMAIL' => $order->getCustomer()->getEmail(),
			'BILLSTREET' => $order->getBillingAddress()->getStreet(),
			'BILLSTREETNR' => null,
			'BILLADDINFO' => null,
			'BILLCITY' => $order->getBillingAddress()->getCity(),
			'BILLCOUNTRY' => $order->getBillingAddress()->getCountryCode(),
			'BILLZIP' => $order->getBillingAddress()->getPostcode(),
			'BILLIC' => null,
			'BILLDIC' => null,
			'BILLPHONE' => $order->getBillingAddress()->getPhoneNumber(),
			'BILLMOBIL' => null,
			'BILLFAX' => null,
			'DELCOMPANY' => $order->getShippingAddress()->getCompany(),
			'DELFNAME' => $order->getShippingAddress()->getFirstName(),
			'DELLNAME' => $order->getShippingAddress()->getLastName(),
			'DELEMAIL' => $order->getCustomer()->getEmail(),
			'DELSTREET' => $order->getShippingAddress()->getStreet(),
			'DELSTREETNR' => null,
			'DELADDINFO' => null,
			'DELCITY' => $order->getShippingAddress()->getCity(),
			'DELCOUNTRY' => $order->getShippingAddress()->getCountryCode(),
			'DELZIP' => $order->getShippingAddress()->getPostcode(),
			'DELPHONE' => $order->getShippingAddress()->getPhoneNumber(),
			'DELFAX' => null,
			'PAYMENTTYPE' => $payment->getMethod()->getName(),
			'DELTYPE' => $delivery->getMethod()->getName(),
			'TRACKCODE' => null,
			'REMARK' => null,
			'CURRENCY' => $order->getCurrencyCode(),
			'PAID' => null,
			'STORNO' => 0,
			'TOTALNETSUM' => ($order->getTotal() - $order->getTaxTotal()) / 100,
			'TOTALBRUTSUM' => $order->getTotal() / 100,
		];
	}

	private function mapOrderItemsToArray(OrderInterface $order): array
	{
		$items = [];

		foreach ($order->getItems() as $item) {
			assert($item instanceof OrderItemInterface);

			$productVariant = $item->getVariant();
			assert($productVariant instanceof ProductVariantWithJezekIdsInterface);

			$tax = 0;
			$taxRate = $this->rateResolver->resolve($productVariant);
			if ($taxRate !== null) {
				$tax = $taxRate->getAmountAsPercentage();
			}

			$jezekIds = $productVariant->getJezekIds() ? explode(',', $productVariant->getJezekIds()) : null;
			if ($jezekIds === null) {
				$jezekIds = [$productVariant->getCode()];
			}

			for ($i = 0; $i < count($jezekIds); ++$i) {
				$uuid = '{' . $jezekIds[$i] . '}';
				if ($i === 0) {
					$items[] = [
						'ID' => $uuid,
						'AMOUNT' => $item->getQuantity(),
						'ARTNUM' => $uuid,
						'ARTID' => $uuid,
						'TITLE' => $item->getVariantName() ?? $item->getProductName(),
						'VAT' => $tax,
						'NPRICE' => (int) round(($item->getTotal() - $item->getTaxTotal()) / $item->getQuantity()) / 100,
						'BPRICE' => (int) ($item->getUnitPrice() / 100),
						'NETPRICE' => ($item->getTotal() - $item->getTaxTotal()) / 100,
						'BRUTPRICE' => $item->getTotal() / 100,
						'UNIT' => 'ks',
					];
				} else {
					$items[] = [
						'ID' => $uuid,
						'AMOUNT' => $item->getQuantity(),
						'ARTNUM' => $uuid,
						'ARTID' => $uuid,
						'TITLE' => $item->getVariantName() ?? $item->getProductName(),
						'VAT' => 0,
						'NPRICE' => 0,
						'BPRICE' => 0,
						'NETPRICE' => 0,
						'BRUTPRICE' => 0,
						'UNIT' => 'ks',
					];
				}
			}
		}

		return $items;
	}

	private function countTaxItems(OrderInterface $order): int
	{
		$taxItems = 0;
		foreach ($order->getItems() as $item) {
			assert($item instanceof OrderItemInterface);
			$taxItems += $item->getTaxTotal();
		}

		return $taxItems;
	}

	private function mapShippingToArray(OrderInterface $order): array
	{
		$shipment = $order->getShipments()->first();
		assert($shipment instanceof ShipmentInterface);

		$shippingMethod = $shipment->getMethod();
		assert($shippingMethod instanceof ShippingMethodInterface);

		$taxItems = $this->countTaxItems($order);
		$taxAmount = $order->getTaxTotal() - $taxItems - $this->paymentTax($order);
		$netPrice = ($order->getShippingTotal() - $taxAmount) / 100;
		$brutPrice = $order->getShippingTotal() / 100;

		$tax = $this->countTax($brutPrice, $netPrice);

		return [
			'ID' => $shippingMethod->getCode(),
			'AMOUNT' => 1,
			'ARTNUM' => $shippingMethod->getCode(),
			'ARTID' => $shippingMethod->getCode(),
			'TITLE' => $shippingMethod->getName(),
			'VAT' => $tax,
			'NPRICE' => $netPrice,
			'BPRICE' => $brutPrice,
			'NETPRICE' => $netPrice,
			'BRUTPRICE' => $brutPrice,
			'UNIT' => 'ks',
		];
	}

	private function countTax($brutPrice, $netPrice): int
	{
		if ($netPrice === 0) {
			return 0;
		}
		$tax = (($brutPrice / $netPrice) * 100) - 100;

		return (int) round($tax);
	}

	private function paymentTax(OrderInterface $order): int
	{
		$payment = $order->getPayments()->first();
		assert($payment instanceof PaymentInterface);

		$paymentMethod = $payment->getMethod();
		assert($paymentMethod instanceof PaymentMethodInterface);

		if ($paymentMethod instanceof TaxableInterface) {
			$taxRate = $this->rateResolver->resolve($paymentMethod);
			if ($taxRate !== null) {
				$price = $this->getOrderPaymentFee($order);
				$tax = $taxRate->getAmountAsPercentage();

				return (int) round($price * ($tax / (100 + $tax)));
			}
		}

		return 0;
	}

	private function mapPaymentToArray(OrderInterface $order): array
	{
		$payment = $order->getPayments()->first();
		assert($payment instanceof PaymentInterface);

		$paymentMethod = $payment->getMethod();
		assert($paymentMethod instanceof PaymentMethodInterface);

		$price = $this->getOrderPaymentFee($order);

		$tax = 0;
		$netPrice = $price / 100;
		$brutPrice = $price / 100;

		if ($paymentMethod instanceof TaxableInterface) {
			$paymentTax = $this->paymentTax($order);
			$netPrice = ($price - $paymentTax) / 100;
			$brutPrice = $price / 100;
			$tax = $this->countTax($brutPrice, $netPrice);
		}

		return [
			'ID' => $payment->getMethod()->getCode(),
			'AMOUNT' => 1,
			'ARTNUM' => $payment->getMethod()->getCode(),
			'ARTID' => $payment->getMethod()->getCode(),
			'TITLE' => $payment->getMethod()->getName(),
			'VAT' => $tax,
			'NPRICE' => $netPrice,
			'BPRICE' => $brutPrice,
			'NETPRICE' => $netPrice,
			'BRUTPRICE' => $brutPrice,
			'UNIT' => 'ks',
		];
	}

	private function getOrderPaymentFee(OrderInterface $order): int
	{
		$paymentFees = $order->getAdjustmentsRecursively('payment');
		if (!$paymentFees->count()) {
			return 0;
		}

		$paymentFee = $paymentFees->first();
		assert($paymentFee instanceof AdjustmentInterface);

		return $paymentFee->getAmount();
	}

	private function createXml(OrderInterface $order)
	{
		$orderArray = $this->mapOrderToArray($order);
		$orderItems = $this->mapOrderItemsToArray($order);

		if ($order->getShippingTotal() > 0) {
			$orderItems[] = $this->mapShippingToArray($order);
		}

		if ($this->getOrderPaymentFee($order)) {
			$orderItems[] = $this->mapPaymentToArray($order);
		}

		$rootElem = new \SimpleXMLElement('<ITEM/>');
		foreach ($orderArray as $key => $value) {
			$string = str_replace('<', '', (string) $value);
			$string = str_replace('>', '', $string);
			$rootElem->addChild($key, $string);
		}

		$orderRowsElem = $rootElem->addChild('ORDERROWS');
		foreach ($orderItems as $orderItem) {
			$itemElem = $orderRowsElem->addChild('ROW');
			foreach ($orderItem as $key => $value) {
				$string = str_replace('<', '', (string) $value);
				$string = str_replace('>', '', $string);
				$itemElem->addChild($key, $string);
			}
		}

		$temp = tmpfile();

		$asXml = $rootElem->asXML();
		if (!is_string($asXml)) {
			throw new \ErrorException('XML error');
		}

		$xmlString = mb_convert_encoding($asXml, 'UTF-8', 'HTML-ENTITIES');
		if (!is_string($xmlString)) {
			throw new \ErrorException('$rootElem->asXML() cannot be NULL');
		}

		fwrite($temp, $xmlString);
		fseek($temp, 0);

		return $temp;
	}
}

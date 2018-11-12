<?php

declare(strict_types=1);

namespace MangoSylius\JezekDuelPlugin\Service;

final class FtpService
{
	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var string
	 */
	private $projectDir;

	public function __construct(
		array $config,
		string $projectDir
	) {
		$this->config = $config;
		$this->projectDir = $projectDir;
	}

	private function getFtpConnection()
	{
		$ftp_server = $this->config['server_url'];
		$ftp_port = $this->config['server_port'];

		if ($this->config['server_ssl']) {
			$ftp_conn = ftp_ssl_connect($ftp_server, $ftp_port);
		} else {
			$ftp_conn = ftp_connect($ftp_server, $ftp_port);
		}

		if ($ftp_conn === false) {
			throw new \ErrorException("Could not connect to $ftp_server");
		}

		ftp_login($ftp_conn, $this->config['username'], $this->config['password']);
		ftp_pasv($ftp_conn, true) or die('Cannot switch to passive mode');

		return $ftp_conn;
	}

	private function closeFtpConnection($ftp_conn): void
	{
		ftp_close($ftp_conn);
	}

	public function downloadFile(): ?string
	{
		$ftp_conn = $this->getFtpConnection();
		$localFileUrl = $this->projectDir . '/var/full_PRODUCT.xml';

		$result = ftp_get(
			$ftp_conn,
			$localFileUrl,
			$this->config['folder_for_update_products'] . '/full_PRODUCT.xml',
			\FTP_BINARY
		);

		$this->closeFtpConnection($ftp_conn);

		return $result ? $localFileUrl : null;
	}

	public function uploadFile($file, $fileNameOnFtp): bool
	{
		$ftp_conn = $this->getFtpConnection();
		$success = ftp_fput($ftp_conn, $this->config['folder_for_export_order'] . '/' . $fileNameOnFtp, $file, FTP_ASCII);
		$this->closeFtpConnection($ftp_conn);

		return $success;
	}
}

<p align="center">
    <a href="https://www.mangoweb.cz/en/" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/38423357?s=200&v=4"/>
    </a>
</p>
<h1 align="center">Ježek Duel Plugin</h1>

## Features

Provides integration with [Duel](https://www.jezeksw.cz/duel/) accounting software.

* Downloads Sylius orders into Duel
* Updates product data in Sylius
* You can update any product data, by default the plugin updates stock level only. For details see `updateProduct()` method of the `mango:jezek:update` command.
* In combination with the [Payment Fee module](https://github.com/mangoweb-sylius/SyliusPaymentFeePlugin), it can handle cash on delivery fees (known as "Dobírka" in Czechia and Slovakia) incl. taxes.

<p align="center">
	<img src="https://raw.githubusercontent.com/mangoweb-sylius/SyliusJezekDuelPlugin/master/doc/duel-logo.png"/>
</p>

## Requirements

* Based on how integrations in Ježek Duel work, you will need an FTP account as a storage for XML files which are used for transferring data.

<p align="center">
	<img src="https://raw.githubusercontent.com/mangoweb-sylius/SyliusJezekDuelPlugin/master/doc/setttings.png"/>
</p>

## Installation

1. Run `$ composer require mangoweb-sylius/sylius-jezek-duel-plugin`.
2. Register `\MangoSylius\JezekDuelPlugin\MangoSyliusJezekDuelPlugin` in your Kernel.
3. Add ftp parameters into config.yml
```
    mango_sylius_jezek_duel:
        server_url: 'server_url'
        username:   'username'
        password:   'password'
```
4. Your Entity `Order` has to implement `\MangoSylius\JezekDuelPlugin\Model\OrderWithExportedAtInterface`. You can use Trait `MangoSylius\JezekDuelPlugin\Model\OrderWithExportedAtTrait`.
5. Your Entity `ProductVariant` has to implement `\MangoSylius\JezekDuelPlugin\Model\ProductVariantWithJezekIdsInterface`. You can use Trait `MangoSylius\JezekDuelPlugin\Model\ProductVariantWithJezekIdsTrait`.
6. Include template `Resources/views/Channel/extendedChannelForm.html.twig` in `@SyliusAdmin/Channel/_form.html.twig`.

For guide to use your own entity see [Sylius docs - Customizing Models](https://docs.sylius.com/en/1.3/customization/model.html)

<p align="center">
	<img src="https://raw.githubusercontent.com/mangoweb-sylius/SyliusJezekDuelPlugin/master/doc/jezekid.png"/>
</p>

## Usage

* <b>Set up the eshop in Duel</b><br>Use "Webový servis" or "eBrána" type. Click "Založit e-shop" in "Agenda" tab (id asked for folder names, keep defaults - "in" and "out").
* <b>Link products</b><br>Use Ježek product UID codes as code for products in Sylius or if you use another codes in your Sylius eshop, put Duel's product UID in dedicated product parameter "Ježek ID". You can put mode UIDs (one per line) to connect more Duel products to one Sylius product when selling product bundles. The plugin first checks "Ježek ID" field, if empty, it checks product code to match the product by Ježek UID. For configurable products, there is one "Ježek ID" per variant. You can use the same "Ježek ID" for multiple variants as well as products. 
* <b>Data handling by Sylius</b><br>Setup commands in cron as specified below. Recommended frequency is 5 mins for orders (can be less for busy shops) and one hour for products (if you update products less frequently, use daily sync).
* <b>Invoice inbound sync</b> (orders from eshop)<br>Click "Stažení objednávek z e-shopu" in "Agenda" tab.
* <b>Invoice outbound sync</b> (product data to eshop)<br>Click "Aktualizace dat v e-shopu" in "Agenda" tab.

<p align="center">
	<img src="https://raw.githubusercontent.com/mangoweb-sylius/SyliusJezekDuelPlugin/master/doc/ribbon.png"/>
</p>

### Commands
* Export orders to FTP.

  ```bash
  mango:jezek:export
  ```

* Update products details from FTP.

   ```bash
   mango:jezek:update
   ```
## Development

### Usage

- Create symlink from .env.dist to .env or create your own .env file
- Develop your plugin in `/src`
- See `bin/` for useful commands

### Testing

After your changes you must ensure that the tests are still passing.
* Easy Coding Standard
  ```bash
  bin/ecs.sh
  ```
* PHPStan
  ```bash
  bin/phpstan.sh
  ```
License
-------
This library is under the MIT license.

Credits
-------
Developed by [manGoweb](https://www.mangoweb.eu/).

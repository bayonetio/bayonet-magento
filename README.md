# Bayonet Anti-Fraud Module for Magento 2

This module will make you able to use the technology of Bayonet in your Magento 2 store to prevent online fraud. In this way, your store will obtain a win/win performance for you and your customers; which means that you will know when a suspicious order by a suspicious customer is trying to be processed to then decline it automatically, that will lead to lower your fraud rate and reduce chargebacks; all this while your store gains a reputation of being a secure place to buy.

*Read this in other languages: [Español](README.es.md).*

## Magento 2 Version Compatibility

The module has been tested in the following Magento 2 versions:
- 2.1.x
- 2.2.x
- 2.3.x
- 2.4.x

## Payment Gateways Compatibility

The module currently supports the following payment gateways:

- Conekta (credit cards)
- Openpay (credit cards)
- Stripe (credit cards)
- Braintree (credit cards)
- Mercado Pago
- PayPal

## Installation

### Installation Using the Magento Marketplace

You can follow Magento's instruction provided at
[https://devdocs.magento.com/extensions/install/](https://devdocs.magento.com/extensions/install/)

### Installation Using Composer

#### Composer

How to get Composer: 
Please follow instructions on [https://getcomposer.org/download/](https://getcomposer.org/download/)

How to update your Composer version: 
Please follow instructions on [https://getcomposer.org/doc/03-cli.md#self-update-selfupdate-](https://getcomposer.org/doc/03-cli.md#self-update-selfupdate-)

#### Installation

You should go to your Magento 2 root directory, and once you are there, run the following commands:

```bash
composer require bayonetio/bayonet-magento  # (*)
php bin/magento module:enable Bayonet_BayonetAntiFraud --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy  # (**)
php bin/magento cache:clean
```

(\*) After executing this command, Magento might ask you for your authentication keys (https://devdocs.magento.com/guides/v2.3/install-gde/prereq/connect-auth.html). Where:

- username = Public Key
- password = Private Key

(\*\*) If you are not running on production mode, use the --force option. Otherwise the command will fail.

```bash
php bin/magento setup:static-content:deploy --force # --force # if you are not running on production mode
```

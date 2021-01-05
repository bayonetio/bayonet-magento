# Módulo Bayonet Anti-Fraud para Magento 2

Este módulo te permitirá usar la tecnología de Bayonet en tu tienda de Magento 2 para prevenir el fraude en línea. De esta manera, tu tienda obtendrá un desempeño de ganar/ganar para ti y tus clientes; lo que significa que sabrás cuando un orden sospechosa de un cliente sospechoso está tratando de ser procesada para después declinarla automáticamente, esto hará que tu tasa de fraude sea menor y se reduzcan los contracargos; todo esto mientras tu tienda gana una reputación de ser un lugar seguro para comprar.

*Leer esto en otros idiomas: [English](README.md).*

## Compatibilidad con Versiones de Magento 2

El módulo ha sido probado en las siguientes versiones de Magento 2:

- 2.1.x
- 2.2.x
- 2.3.x
- 2.4.x

## Compatibilidad con Pasarelas de Pago

El módulo actualmente soporta las siguientes pasarelas de pago:

- Conekta (tarjetas de crédito)
- Openpay (tarjetas de crédito)
- Stripe (tarjetas de crédito)
- Braintree (tarjetas de crédito)
- Mercado Pago
- PayPal

## Instalación

### Instalación Usando el Marketplace de Magento

Puedes seguir las instrucciones que se encuentran en [https://devdocs.magento.com/guides/v2.3/comp-mgr/extens-man/extensman-main-pg.html](https://devdocs.magento.com/guides/v2.3/comp-mgr/extens-man/extensman-main-pg.html)

### Instalación Usando Composer

#### Composer

Cómo obtener Composer: 
Por favor sigue las instrucciones que se encuentran en [https://getcomposer.org/download/](https://getcomposer.org/download/)

Cómo actualizar tu versión de Composer: 
Por favor sigue las instrucciones que se encuentran en [https://getcomposer.org/doc/03-cli.md#self-update-selfupdate-](https://getcomposer.org/doc/03-cli.md#self-update-selfupdate-)

#### Instalación

Deberás ir a la raíz de tu directorio de Magento 2, una vez que estés ahí, ejecuta los siguientes comandos:

```bash
composer require bayonetio/bayonet-magento  # (*)
php bin/magento module:enable Bayonet_BayonetAntiFraud --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy  # (**)
php bin/magento cache:clean
```

(\*) Después de ejecutar este comando, Magento puede te pida tus llaves de autenticación (https://devdocs.magento.com/guides/v2.3/install-gde/prereq/connect-auth.html). Donde:

- username = Llave Pública
- password = Llave Privada

(\*\*) Si no te encuentras en modo de producción, usa la opción --force ya que de lo contrario el comando fallará.

```bash
php bin/magento setup:static-content:deploy --force # --force # si no está en modo de producción
```

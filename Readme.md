# Synerise integration module for Magento 2

## Requirements
 * PHP 7.1+
 * Magento 2.3+ 

## Installation

> It is highly recommended to install the extension over test environment first.

Module is available as a composer package:

`composer require synerise/magento2-integration`

After installing the module make sure to also update the database schema:

`php bin/magento setup:upgrade`

For more information regarding module management please refer with [Magento 2 documentation](https://devdocs.magento.com/cloud/howtos/install-components.html).

## Upgrading to version 2.x
Latest module upgrade includes a general overhaul of its config. One important change is an introduction of Workspace management as a convenient way to setup multistore integration. 

Upon upgrading please refer to our documentation page to configure your Workspaces. 

## Setup
Setup guide is available at [Synerise help page](https://help.synerise.com/docs/settings/tool/magento-integration-multistore-support/). 

Please make sure to follow it carefully through all the steps to integrate your application properly.
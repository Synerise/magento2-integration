# Synerise integration module for Magento 2.x platform

## Installation

> It is highly recommended to install the extension over test environment first.

Module is available as a composer package:

`composer require synerise/magento2-integration`

After installing the module make sure to also update the database schema:

`php bin/magento setup:upgrade`

For more information regarding module managment please refer with [Magento 2 doumentation](https://devdocs.magento.com/cloud/howtos/install-components.html).

## Setup

Module configuration can be accessed via admin panel, under:
> Stores > Configuration > Synerise > Integration

Each section covers specific functionalities.

### Api

Synerise Api key of profile scope needs to be provided. 

> Synerise API will be used to send page events, as well as historical customer and trnsaction data, which was collected prior to integration.

Api keys can be managed via [Synersie panel](https://app.synerise.com). 

1. Login to your Synerise account and select the business profile.
2. Navigate to [Settings > API Keys](https://app.synerise.com/spa/modules/settings/apikeys/list).
3. Switch to `Business Profile` tab.
4. Create an API key by clicking `Add API Key` button. Provide a name, select group & save.
5. Switch to `Business Profile` tab & set permissions by clicking `permissions` on the selected API Key row.
> Check `EVENTS` branch & click `Apply settings`.
6. Switch to `Business Profile` tab & Find your API Key on the list.
7. Click `...` and then the last option to `Copy API key`.
8. Navigate back to your magento admin panel, paste the copied key & save.

### Page Tracking

Tracking script is mainly responsible for collecting `page.visit` events. 

> Other `js sdk` features include i.a. push notifications & and display of dynamic contact. 
> 
> Please refer to Synerise documentation to learn more about those functionalities.

Api keys can be managed via [Synersie panel](https://app.synerise.com).


1. Login to your Synerise account and select the business profile.
2. Navigate to [Settings > Tracking Codes](https://app.synerise.com/spa/modules/settings/profile/tracking-codes).
3. Create Tracking Code by clicking `Add tracking code` button. Provide a name, domain, click `Copy to clipboard` & save.
4. Navigate back to your magento admin panel, paste the copied Tracking Code in `Script` field.
5. Make sure `Enabled` field under `Page Tracking` section is set to `Yes` & save.

### Events Tracking
In this section you can manage the events you wish to track.

By default all events should be tracked, but any event can be excluded, by deselecting it one the list (`ctrl/cmd + click`).

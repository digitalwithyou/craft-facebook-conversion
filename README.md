# Facebook Conversion plugin for Craft CMS 3, 4 & 5

## Requirements

- Craft CMS 3.x, 4.x or 5.x
- Works best with Craft Commerce
- Facebook Pixel (See [configuration](#pixel) how to obtain the Facebook Pixel)
- Facebook Business Manager (See [Facebook Business](https://business.facebook.com/))
- Facebook Access Token (See [configuration](#access-token) how to obtain the Access Token)

## Installation

The plugin can be installed via the Plugin Store in the Craft Control Panel.

Or, open your terminal, go the Craft project and install the plugin via Composer:

```
composer require dwy/facebook-conversion
./craft plugin/install facebook-conversion
```

## Configuration

### Pixel

1. Open [Facebook Business Manager](https://business.facebook.com)
2. Select your business
3. Navigate to the Events Manager
4. Click the Add Data Source button in the left menu
5. Use "Web"
6. Choose "Conversion API" as method.
7. Fill in a Pixel name and website url
8. After completing the Pixel creation, you'll find the Pixel-ID in the Settings tab of the Data Source. Copy your Pixel-ID to the Settings page of the plugin. 

For more information, check the [Create a Facebook Pixel in Business Manager](https://www.facebook.com/business/help/314143995668266?id=1205376682832142) article in the official documention.


### Access Token

In the Events Manager:

1. Select the pixel you want to associate with your Conversions API
2. Navigate to the Settings tab
3. Click Create Access Token below the "Set up manually" section
4. Select the Facebook pixel you want your server events to be associated with
5. Click Generate Access Token
6. Copy your access token to the Settings page of the plugin

For more information, check the [Create an Access Token for Conversions API in Guided Setup](https://www.facebook.com/business/help/1341993546002479) article in the official documention.


### Test Event Code

Can be used to test the implementation.

1. Navigate to the Events Testing tab in the Events Manager
2. Copy the `test_event_code` to the Settings page of the plugin

Make sure an event that can be linked to a user by Facebook (i.e. Purchase event, by having a valid `fbclid` parameter, etc) is used when testing.


## Template implementation

The Facebook Pixel can be added to your template by adding the `facebook-conversion-head-tag` hook to the head of the page and the `facebook-conversion-body-tag` to the body. For example:

```html
<head>
    <meta charset="utf-8">

    <title>My Website</title>

    {% hook 'facebook-conversion-head-tag' %}
</head>
<body>
    {% hook 'facebook-conversion-body-tag' %}
```

Please note, when the Facebook Pixel is already added by another plugin (E.g. SEOmatic) don't add the template hook to avoid duplication.


Brought to you by [Digital With you](https://digitalwithyou.com), follow us on [Instagram](https://www.instagram.com/digitalwithyou/)

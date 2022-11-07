# Release Notes for Facebook Conversion

## 1.1.2 - 2022-11-07

- Fixed syntax error in Facebook library

## 1.1.1 - 2022-10-26

- Upgraded Facebook SDK
- Bugfix: Allowed `null` as valid parameter type in the `fbEvent` twig function

## 1.1.0 - 2022-09-02

- Craft 4 compatibility

## 1.0.17 - 2022-07-13

- Upgraded Facebook SDK
- Improved test event code by supporting as environment variable (Thanks @qrazi)

## 1.0.16 - 2022-04-08

- Bugfix: Web Console error on external id

## 1.0.15 - 2022-04-04

- Upgraded Facebook SDK

## 1.0.14 - 2021-10-04

- Handle Facebook being down

## 1.0.13 - 2021-09-16

- Upgraded Facebook SDK to support Facebook Ads API v12

## 1.0.12 - 2021-08-22

- Improved acceptance rate by Facebook: 
  - Added order id
  - Use ip address of client / order

## 1.0.11 - 2021-07-01

- Improved acceptance rate by Facebook.

## 1.0.10 - 2021-06-29

- Bugfix: Template hook to use Facebook Pixel couldn't be used without Craft Commerce

## 1.0.9 - 2021-06-20

- Improved parameters and Deduplication Keys
- Added support for Web Payments Plugin

## 1.0.8 - 2021-06-17

- Bugfix to support Craft Commerce 3.1.3 and lower

## 1.0.7 - 2021-06-13

- Upgraded Facebook SDK to support Facebook Ads API v11

## 1.0.6 - 2021-04-17

- Added Twig function to send Server Events (See the [documentation](https://facebook-conversion.dwy.be/documentation/manual-tracking.html) for more info).

## 1.0.5 - 2021-03-09

- Bugfix: Code of local debugging was still present 

## 1.0.4 - 2021-03-09

- Added logger to facilitate debugging (Access tokens are removed from the logs)

## 1.0.3 - 2021-03-07

- Added support for Facebook [Test Event Codes](https://www.facebook.com/business/help/2040882565969969).
- Upgraded Facebook SDK to support Facebook Ads API v10

## 1.0.2 - 2021-03-07

- Added support for the [Mollie Payments](https://plugins.craftcms.com/mollie-payments) Plugin

## 1.0.1 - 2021-01-29

- New Plugin Icon (Thanks Steve!)
- Updated instructions on the Settings

## 1.0.0 - 2021-01-25

- Initial release with support for:
  - Craft CMS search
  - Craft Commerce
    - Add to cart
    - Purchase

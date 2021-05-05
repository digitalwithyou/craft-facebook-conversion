# Auto-tracking

When the Plugin is installed it will automatically start tracking certain Server Events. No extra configuration is needed.

::: tip Pageview Event and Event Duplication
Note that no Pageview Events are sent by the server.

In our default setup we assume the client-side Facebook Pixel is used to track Pageviews, see [template implementation](/documentation/getting-started#template). By using the client to only track the Pageview events, and using the Server events to track others, we prevent [Event Duplication](https://developers.facebook.com/docs/marketing-api/conversions-api/deduplicate-pixel-and-server-events/).
:::

These Server Events which will be tracked by default:

- Craft CMS
    - [Search](#search)
- Craft Commerce
    - [Add to cart](#add-to-cart)
    - [Purchase](#craft-commerce-purchase)
- Mollie Payments
    - [Purchase](#mollie-payments-puchase)


## Search

The Search event is a [Facebook Standard Event](https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking/#standard-events), the search query and all available user data will get sent to Facebook.

The event will only be recognized by Facebook when it can be linked to a user. Therefore the visitor should be logged in, made a purchase in an earlier session, or have clicked a link in Facebook to your website.

::: warning
When the results of the search are cached, the event will only be triggered the first time a search occurs and [Manual Tracking](/documentation/manual-tracking) will be needed.
:::


## Add to cart

The Add to cart event is a [Facebook Standard Event](https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking/#standard-events), the cart content (Product name, SKU, value, currency, and order quantity) and all available user data will get sent to Facebook.

The event will only be recognized by Facebook when it can be linked to a user. Therefore the visitor should be logged in, made a purchase in an earlier session, or have clicked a link in Facebook to your website.


## Craft Commerce Purchase

The Purchase event is probably the most effective of all. Since the visitor needs to fill in his email address to make the purchase, Facebook can link it to a user and will always accept the event.

All cart content (Product name, SKU, value, currency, and quantity) and all available user data will get sent to Facebook.


## Mollie Payments Purchase

The Purchase event is probably the most effective of all. Since the visitor needs to fill in his email address to make the purchase, Facebook can link it to a user and will always accept the event.

By default Mollie Payments only requires an email address, so that will be the only user data that is sent to Facebook. Optionally, when "first name" or "last name" fields exist, they will be sent as well.

Next to the user data, the value and the currency will be sent.

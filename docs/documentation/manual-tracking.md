# Manual Tracking

When tracking Search, Add To Cart, and Purchases via Server Events is not enough, Events can be sent via a Twig function in your templates.

## Twig Function

Server Events can manually be sent by the `fbEvent` Twig function.

For example:

```twig
{{ fbEvent('Purchase', {
    'email': 'jack@mail.com',
    'first_name': 'Jack',
    'last_name': 'Timberlake'
}, {
    'currency': 'EUR',
    'value': 20,
},
'event_id-1') }}
```

- The first parameter is the Event Name. Both [Custom Events](https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking#custom-events) as [Standard Events](https://developers.facebook.com/docs/facebook-pixel/reference) are supported. (Required)
- The second parameter is Customer Information. By default, the already known User data (of an earlier purchase or fbclid) will be used. Only add the information when you're certain it contains the latest data. (Optional)
- The third parameter is the Custom Data. (Optional)
- The fourth parameter is the Event ID, used by Facebook to deduplicate the same event sent from both server and browser. (Optional)

All Customer Information and Custom Data parameters are supported, for a full list see the [Facebook Documentation](https://developers.facebook.com/docs/marketing-api/conversions-api/parameters). The Main Body and Server Event parameters are handled by the Plugin. Hashing is also handled by the Plugin and should not be done in the Twig function.


## Pageview example

When you would like to send the Pageview events by the Server, and not by the Client, use the simplest Twig function:

```twig
{{ fbEvent('Pageview') }}
```

The known User information will automatically be added and no custom data is needed for the Pageview Event.

::: warning
When tracking the Pageview Event via a Server event, make sure it is not sent via the client. Remove the Facebook Pixel (or Template Hook) from your template. Or use the same Event ID on both Client as Server events.
:::


## Cached pages

When the page is cached by Blitz, Nginx, Varnish, or a CDN, the Twig function would not be triggered. In this case, you would need to do a javascript request to a dynamic part of your website. One simple way to do so is by creating a `dynamic` folder in your templates and excluding it from your cache.

Create a file (e.g. `viewevent.twig`) in the excluded folder and paste the twig function that you would use into it:

```twig
{{ fbEvent('Pageview') }}
```

Now on every page the event should be triggered, request the not cached snippet. For example:

```html
<script type="text/javascript">
fetch('/dynamic/viewevent');
</script>
```

Note that in this example the javascript `fetch` is used, which is not supported by Internet Explorer, see the [MDN documentation](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch). The same can also be accomplished by Jquery's Ajax function or other methods.

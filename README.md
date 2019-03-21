# Twilio

Twilio integration for MODX CMS.

## What does it do?

The first beta release includes only a minimal set of features:

- Phone number lookup: validates and formats a phone number using [Twilio's Lookup API](https://www.twilio.com/docs/lookup)
- Send SMS: sends a message via SMS using Twilio's [Programmable SMS API](https://www.twilio.com/docs/sms)
- Optionally set a callback/verification page on your MODX site
    - Passing through arbitrary data
    - Run other processing Snippets or expose secret forms
    - Protected with a single-use nonce with configurable expiry

## Installation

Install via MODX Extras Installer. Or you can download from the [_packages](_packages) directory.

## Usage

### MODX System Settings

Before using the Twilio MODX Extra, you'll need to acquire API Keys from Twilio. See their [API documentation](https://www.twilio.com/docs/iam/keys/api-key-resource) for more details, or read about [Twilio credentials](https://www.twilio.com/docs/usage/your-request-to-twilio#credentials).

#### Area: Twilio

The following MODX System Settings must be set with your credentials:

- twilio.account_sid
- twilio.auth_token
- twilio.sms_sender (only if you want to send SMS messages)

### Snippet: twilio.lookup

Lookup a phone number. The following properties can be submitted via a [FormIt](https://modx.com/extras/package/formit) form, in which case you would call twilio.lookup as a FormIt hook.

* `&number` (string)             Phone number to lookup
* `&country` (string)            ISO country code per http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2. Default 'US'.
* `&type` (string)               Optional lookup type. Twilio supports carrier|caller-name. Default ''.
* `&errorTpl` (string)           Template Chunk for error. Default '@INLINE Number lookup failed.'
* `&successTpl` (string)         Template Chunk for success. Default 'twilio.lookup_result'.
* `&successPlaceholder` (string) Optional placeholder to which to send the output. Default 'twilio_output'.
* `&debug` (string) print|log    Enable debug output. Default ''.

Any `twilio.` namespaced properties of the FormIt call (if using as hook) or the `$scriptProperties` of the twilio.lookup Snippet call, will be set as placeholders in the output `errorTpl` Chunk. The `successTpl` Chunk gets the results of the Twilio Lookup API call.

### Snippet: twilio.send

Send an SMS. The following properties can be submitted via a [FormIt](https://modx.com/extras/package/formit) form, in which case you would call twilio.send as a FormIt hook.

* `&number` (string)             Phone number to lookup
* `&country` (string)            ISO country code per http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2. Default 'US'.
* `&message` (string)            Message to send. Default ''.
* `&type` (string)               Optional lookup type. Twilio supports carrier|caller-name. Default ''.

* `&callbackUrl` (string)        URL at which to render callbacks. Link will be created and appended to message. Default ''.
* `&callbackFields` (string)     Comma-separated list of properties to include in callback data. Default ''.
* `&callbackGetParam` (string)   Get parameter to look for callback ID. Default 'cbid'.

* `&callbackTpl` (string)        Template Chunk for rendering the callback. Default ''.
* `&callbackLinkTpl`             Template Chunk for link at end of message. Default '@INLINE [[+callback_link]]'.
* `&errorTpl` (string)           Template Chunk for error. Default '@INLINE Error sending SMS.'
* `&successTpl` (string)         Template Chunk for success. Default 'twilio.sent_result'.

* `&successPlaceholder` (string) Optional placeholder to which to send the output. Default 'twilio_output'.
* `&debug` (string) print|log    Enable debug output. Default ''.

Any `twilio.` namespaced properties of the FormIt call (if using as hook) or the `$scriptProperties` of the twilio.send Snippet call, will be set as placeholders in the output `errorTpl` Chunk. The `successTpl` Chunk gets the results of the Twilio Programmable SMS API call.

## Considerations

This Extra is a work-in-progress. The code is managed [on Github](https://github.com/sepiariver/twilio). Feel free to start [Issue threads](https://github.com/sepiariver/twilio) to discuss and contribute to the roadmap.


Thanks to you, for using MODX and the Twilio Extra :D

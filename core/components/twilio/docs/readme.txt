# Twilio

Twilio integration for MODX CMS.

## What does it do?

- Log in to MODX using any/all of the Identify Providers (IdPs) supported by Twilio, such as Google, Facebook, Twitter, Github, Microsoft, Dropbox, and [dozens of others](https://twilio.com/docs/identityproviders), including enterprise services.
- Synchronize MODX User records, User Groups, and User Settings across multiple MODX sites and the Twilio User database.
- Log in to MODX with a one-time-use JWT.
- Practically any of the [features and use cases](https://twilio.com/docs/getting-started/overview) that Twilio supports.

In its most basic implementation, the twilio.login Snippet redirects the User to your Twilio domain's login page, then calls the Twilio API to identify the User by their verified email address. The twilio.login Snippet attempts to verify the User against the MODX User records, and if successful, adds the MODX Context(s) specified in the Snippet properties to the User's session.

You can read the blog post [here](https://www.sepiariver.ca/blog/modx-web/twilio-for-modx-cms/).

## Installation

Install via MODX Extras Installer. Or you can download from the [_packages](_packages) directory.

## Usage

### Twilio Client Configuration

Go to [twilio.com](https://twilio.com) and register an account. A generous "free" plan is available.

Once signed-in to the dashboard at [https://manage.twilio.com/](https://manage.twilio.com/) click the "New Client" button.

![new client](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.25.37.png)

Give your Client App a name, select the option "Regular Web Applications" and click "Create".

![create client](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.27.32.png)

In the "Settings" tab of your new Client App, copy the "Domain", "Client ID" and "Client Secret" into the relevant System Settings in your MODX install. (See below under "MODX Setup")

![credentials](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.29.10.png)

Scroll down the Client App settings view to configure at least one "Allowed Callback URL". This is usually the URL of the Resource on which you call the "twilio.login" Snippet.

![url configs](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.36.10.png)

Other configs are optional. Scroll to the bottom and click "Save Changes".

Next, choose the "Connections" tab and ensure you have at least one identity provider selected.

![ID providers](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.38.01.png)

This should complete the Twilio Client App setup.

### Twilio Management API Setup

To enable the user profile sync features, you must authorize your Twilio Client App to access the Twilio Management API. Go to the APIs section in your [Twilio Dashboard](https://manage.twilio.com/). Edit the Twilio Management API settings, and in the "Non Interactive Clients" tab, authorize the Twilio Client App that you intend to integrate with MODX. Be sure to grant the Client App the following scopes: `read:users update:users read:users_app_metadata update:users_app_metadata`.

### MODX System Settings

NOTE: Settings for this Extra can only be set in System Settings. For security reasons, these values cannot be overridden per Context, User Group, or User. In general, to mitigate against privilege escalation, access to CRUD functions for all MODX Settings objects, "executable Elements" like Snippets and Plugins, the Extras Installer, all User management functions, and indeed Manager access (especially in the Administrator User Group) should only be granted on an as-needed basis, and with the highest level of scrutiny.

**Mis-configuration of these Settings can expose critical vulnerabilities in your MODX site.**

_This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details._

#### Area: Twilio

After installing the Twilio Extra, add the credentials from your Twilio Client App to the relevant System Settings: "client_id", "client_secret", and "domain". All System Settings will be under the namespace "twilio".

![system settings](https://www.sepiariver.ca/assets/uploads/images/Screenshot%202018-01-08%2018.33.17.png)

The "audience" setting will be your Twilio domain `/userinfo`. For example: `https://example.twilio.com/userinfo`

The "redirect_uri" setting will be the Resource on which you call the "twilio.login" Snippet, for example: `http://localhost:3000/login.html`

The "redirect_uri" must be web-accessible, as Twilio will redirect your Users there. The above is just an example.

Note the setting for "scope" includes `openid profile email address phone`. `openid` and `email` at the very least, are required for MODX to identify your User.

If the "jwt_key" setting is set, logging in via JSON Web Token is enabled, at the URL of any Resource where you call the twilio.JWTLogin Snippet. Please see the section below for **important information** about the use of twilio.JWTLogin.

The "metadata_email_key" is the key of the property in the app_metadata object provided by Twilio, that can serve as an email address "override", for some IdPs that do not supply a verified email. Using this feature would require an Admin of the Twilio domain, manually add the email address to the User record via the Twilio Dashboard, for those who log in with such IdPs. It's not scalable, but if you need to get someone access, pronto, and they can only use one of "those" IdPs, it's a functional workaround.

System Settings "persist_id_token", "persist_access_token", and "persist_refresh_token" simply expose the settings of the same name in the Twilio SDK, but are not implemented at this time. Recommended value for these settings is `false`.

#### Area: Synchronization

The synchronization features are useful for cases where the same User Permissions schemes should be synchronized across multiple MODX installs that are integrated with the same Twilio tenant domain. Coupled with the JWT login flow, you can deliver a seamless SSO experience for Users across such MODX sites.

The following boolean flags enable or disable synchronization features. **Be careful** when enabling these! It means you trust your Twilio domain's user registration logic completely, as well as that of any MODX site from which you are pushing data.

- "create_user" enables creation of new MODX User records from Twilio records.  Newly created Users will not be able to login via MODX, and **MUST** login via Twilio, due to the custom Twilio hash_class set for such Users. Without any of the following flags enabled, the User would not be added to any User Groups.
- "sync_user_groups" enables two-way syncing of User Group names between MODX and Twilio. Names of User Groups to sync, must be stored in the "user_groups" property of the User's "app_metadata" object in Twilio.
- "create_user_groups" enables the creation of User Groups in MODX from Twilio records, when such User Groups currently do not exist in MODX. The MODX User will be adjoined to these User Groups.
- "pull_profile" enables updating a MODX User record with data from the "profile" key of the Twilio User Record's "app_metadata" object.
- "push_profile" enables updating the "profile" key of the Twilio User record's "app_metadata" object, with data from the MODX User record.
- "pull_settings" enables updating MODX User's User Settings with data from the "user_settings" key of the Twilio User record's "app_metadata" object.
- "push_settings" enables updating the "user_settings" key of the Twilio User record's "app_metadata" object, with data from the MODX User's User Settings.
- "allowed_pull_setting_keys" is a comma-delimited list of User Setting keys to pull from Twilio.
- "allowed_push_setting_keys" is a comma-delimited list of User Setting keys to push to Twilio.

### Snippet: twilio.login

This Snippet has the following options:

- &loginResourceId -       (int) ID of Resource to redirect user on successful login. Default 0 (no redirect)
- &loginContexts -         (string) CSV of context keys, to login user (in addition to current context). Default ''
- &requireVerifiedEmail -  (bool) Require verified_email from ID provider. Default true
- &unverifiedEmailTpl -    (string) Chunk TPL to render when unverified email. Default '@INLINE ...'
- &userNotFoundTpl -       (string) Chunk TPL to render when no MODX user found. Default '@INLINE ...'
- &alreadyLoggedInTpl -    (string) Chunk TPL to render when MODX user already logged-in. Default '@INLINE ...'
- &successfulLoginTpl -    (string) Chunk TPL to render when Twilio login successful. Default '@INLINE ...'
- &logoutParam -           (string) Key of GET param to trigger logout. Default 'logout'
- &redirect_uri -          (string) Twilio redirect URI. Default {current Resource's URI}
- &debug -                 (bool) Enable debug output. Default false

### Snippet: twilio.loggedIn

Tests for logged-in state and provides options for what to render in each scenario:

- &forceLogin -    (bool) Enable/disable forwarding to Twilio for login if anonymous. &anonymousTpl will not be displayed if this is true. Default true
- &loggedInTpl -   (string) Chunk TPL to render when logged in. Default '@INLINE ...'
- &twilioUserTpl -  (string) Chunk TPL to render when logged into Twilio but not MODX. Default '@INLINE ...'
- &anonymousTpl -  (string) Chunk TPL to render when not logged in. Default '@INLINE ...'
- &debug -         (bool) Enable debug output. Default false

### Snippet: twilio.JWTLogin

Logs a user in with a JWT token. IMPORTANT: this Snippet "trusts" any JWT token signed with the value in the `twilio.jwt_key` System Setting. The Twilio class has a config option for the minimum length of this key. It should be as cryptographically strong as feasible, because the "trust" in the token it signs, is nearly complete.

The JWT payload must have the following claims:
- 'email' must be a valid email address, of an existing MODX User. If a MODX User doesn't exist and the `twilio.create_user` System Setting is enabled, it will create one with this email.
- 'sub' expects a `user_id` from Twilio, but it could be any string to be used as the `remote_key` for the MODX User
- 'exp' must be a valid UNIX timestamp, after which the token will be invalid
- 'aud' must equal the value of the `twilio.client_id` System Setting. This is the only other defense against a compromised `twilio.jwt_key`.

If the above conditions are met in the payload, the Snippet will process the contents of the payload as trusted.

- &loginContexts (string) CSV of context keys, to login user (in addition to current context). Default ''
- &continueTwilio (bool) enable redirecting to Twilio's "continue" endpoint. For use with [Twilio's Redirect from Rules](https://twilio.com/docs/rules/current/redirect) feature. Default true
- &errorTpl (string) Chunk name or '@INLINE' TPL to use when the Snippet encounters an error. Default ''.
- &successTpl (string) Chunk name or '@INLINE' TPL to use when the Snippet logs in successfully, and `continueTwilio` is false. Default ''.

### Plugin: twilio

Pushes user data to Twilio if the relevant System Settings are enabled.

## Considerations

This Extra is a work-in-progress. The code is managed [on Github](https://github.com/sepiariver/twilio). Feel free to start [Issue threads](https://github.com/sepiariver/twilio) to discuss and contribute to the roadmap.

Some things to consider:

1. Twilio has features that allow the use of arbitrary database stores for provision of identity. Is this a useful feature to integrate?

2. Twilio has premium features in their paid plans. Are any of those features important for this Extra to support?

Thanks to @theboxer for all his invaluable advice and guidance. This Extra wouldn't be possible without his input.

Thanks to you, for using MODX and the Twilio Extra :D

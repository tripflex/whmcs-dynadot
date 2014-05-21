WHMCS Dynadot Registrar Module
======================

###### Author: Myles McNamara (get@smyl.es)
###### Current Release: 2.0

The only Dynadot WHMCS module I could find was old, outdated, and really didn't even work anymore.  Because the only thing that worked was to register a new domain name, I decided to go ahead and create a new module myself.

### Features

Feature | Status | Comments
--- | --- | ---
Register | `released` | Register domain name
Renew | `released` | Renew domain name
Get Nameservers | `released` | Get current nameservers for output in client area
Set Nameservers | `released` | Set nameservers from client area
Create Nameserver | `planned` | Ability to create custom nameserver
WHOIS Contact | `planned` | Ability to create, edit, or remove custom WHOIS contact
Set WHOIS | `planned` | Ability to set WHOIS details on domain
Set Privacy | `planned` | Ability to add WHOIS privacy to domain
Set Forwarding | `planned` | Ability to set forwarding on domain
Delete Domain | `planned` | Remove and refund domain when in grace period (minus fees)
Transfer | `not possible` | Dynadot's API does not support (which is lame)

## Installation

All of these files need to be placed inside a directory called `dynadot` inside the `/modules/registrars` directory of your WHMCS installation.  The ultimate installation should be `/modules/registrars/dynadot`.

You can use git to clone the repo:

``` bash
git clone https://github.com/tripflex/whmcs-dynadot.git dynadot
```

Or [download the lastest release](https://github.com/tripflex/whmcs-dynadot/releases/) and unzip to the `/modules/registrars` directory.

## Setup
You must first configure the module with your API key from Dyandot, go to `Setup > Products/Services > Domain Registrars` and enable the Dynadot module.  You then need to click on `Configure` and enter your API key.

[Get your Dynadot API key here](https://www.dynadot.com/account/domain/setting/api.html)

## Troubleshooting

I purposely put plenty of debugging inside of this module, so if you go to `Utilities > Logs > Module Log` from the menu in your WHMCS installation, once you enable debug logging you will see plenty of debug output from the module.

If you still have issues, find a bug, or want to suggest a feature/enhancement, [post it here on GitHub](https://github.com/tripflex/whmcs-dynadot/issues).

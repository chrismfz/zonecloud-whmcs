# ZoneCloud.io Domain Search -- WHMCS Addon Module

## Overview

**ZoneCloud Domain Search** is a WHMCS Admin Addon Module that
integrates your ZoneCloud Controller directly into WHMCS.

It allows administrators to:

-   Search domains/zones stored in ZoneCloud
-   View zone ownership and server information
-   See real-time zone statistics (Total / Active / Excluded)
-   Use WHMCS Intelligent Search to find domains across your DNS cluster

This module is designed for hosting providers using **ZoneCloud DNS
Clustering**.

------------------------------------------------------------------------

## Features

### Admin Area Domain Search

Search for any domain stored in ZoneCloud and display:

-   Server ID
-   Zone name
-   Zone owner
-   Insert date
-   Server name

Supports:

-   Exact match
-   Substring match (contains)

------------------------------------------------------------------------

### Zone Statistics Dashboard

Displays live statistics from ZoneCloud:

-   Total Zones
-   Active Zones
-   Excluded Zones

Pulled from:

-   `/api/zones/list_all`
-   `/api/zones/list`
-   `/api/zones/list_excluded`

------------------------------------------------------------------------

### WHMCS Intelligent Search Integration

Hooks into WHMCS `IntelligentSearch` (WHMCS 7.7+ compatible).

When searching inside WHMCS admin:

-   Queries ZoneCloud API
-   Returns matching zones
-   Displays:
    -   Zone name
    -   Owner
    -   Server name
-   Uses non-blocking 2-second timeout to avoid slowing down WHMCS

------------------------------------------------------------------------

## Requirements

-   WHMCS 7.7+
-   ZoneCloud Controller
-   Valid ZoneCloud API token
-   PHP with cURL enabled

------------------------------------------------------------------------

## Installation

1.  Upload the module directory to:

```{=html}

```
    /modules/addons/zoneclouddomainsearch/

2.  Go to:

```{=html}

```
    WHMCS Admin → Setup → Addon Modules

3.  Activate **ZoneCloud Domain Search**
4.  Configure module settings

------------------------------------------------------------------------

## Configuration Options

  ------------------------------------------------------------------------
  Setting                        Description
  ------------------------------ -----------------------------------------
  ZoneCloud Controller URL       Full URL to your controller
                                 (e.g. `https://controller.example.com`)

  Token                          ZoneCloud API token

  Exact match                    Enable exact match instead of substring
                                 search
  ------------------------------------------------------------------------

------------------------------------------------------------------------

## API Endpoints Used

The module communicates with:

    GET /api/find-zone-info/{domain}/{exact|contains}
    GET /api/zones/list_all
    GET /api/zones/list
    GET /api/zones/list_excluded

Authentication:

    Authorization: <API_TOKEN>

------------------------------------------------------------------------

## Performance Considerations

-   2-second connection timeout for API calls
-   Intelligent Search will fail gracefully if the controller is
    unreachable
-   JSON response validation included

------------------------------------------------------------------------

## Security Notes

-   API token is stored using WHMCS addon configuration storage
-   Token is sent via HTTP Authorization header
-   Ensure HTTPS is used on your ZoneCloud controller

------------------------------------------------------------------------

## Author

NixPal OU
Zonecloud.io
Nixpal.com

------------------------------------------------------------------------

## License

Proprietary / Internal Use for Zonecloud.io -  GPL for this Addon Module

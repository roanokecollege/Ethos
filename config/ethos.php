<?php
  return [
    "api_key" => env("ETHOS_API_KEY"),
    "proxy_url" => env("ETHOS_PROXY_URL"),
    "api_header" => env("ETHOS_API_HEADER", "application/vnd.hedtech.integration.v6+json")
  ];

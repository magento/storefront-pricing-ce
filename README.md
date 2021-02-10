# Overview
Provides Read API through gRPC to serve Pricing related requests

## Installation
Pricing Service can be installed in 2 ways:
 - Monolithic installation: just copy files to your Magento root folder. This is for development purposes only, do not use in production. 
 - Standalone installation: recommended approach, install Pricing Service as a standalone installation 

### Standalone Project Installation
1. Add Magento authentication keys to access the Magento Commerce repository 
* with auth.json: copy the contents of `auth.json.dist` to new `auth.json` file and replace placeholders with your credentials  
* with environment variable: specify environment variable `COMPOSER_AUTH` according to [documentation](https://getcomposer.org/doc/03-cli.md#composer-auth)
2. Run `bash ./dev/tools/make_standalone_app.sh`
3. Run `composer install`
4. Run `bin/command storefront:pricing:init` with all required arguments to provide setup DB connection.  
5. Run `bin/command storefront:pricing:db-upgrade` to create required tables and default Pricebook.

## GRPC up (local php)
1. Run `bin/command storefront:grpc:init \\Magento\\PricingStorefrontApi\\Api\\PriceBookServiceProxyServer`
2. Run `./vendor/bin/grpc-server`

### Contributing
Contributions are welcomed! Read the [Contributing Guide](./CONTRIBUTING.md) for more information.

### Licensing
This project is licensed under the OSL-3.0 License. See [LICENSE](./LICENSE.md) for more information.

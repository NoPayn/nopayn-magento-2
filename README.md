<p align="center">
  <img src="view/adminhtml/web/images/logo.png" alt="GingerPay Logo" width="200"/>
</p>

# Nopayn Payment plugin for Magento 2

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version"></a>
  <a href="#"><img src="https://img.shields.io/badge/Magento-2.4.6+-brightgreen.svg" alt="Magento 2.4.6+"></a>
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.2+-orange.svg" alt="PHP 8.2+"></a>
  <a href="#"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></a>
</p>

## Table of Contents
- [About](#about)
- [Features](#features)
- [Version](#version)
- [Requirements](#requirements)
- [Supported Payment Methods](#supported-payment-methods)
- [Installation](#installation)
- [Configuration](#configuration)
- [License](#license)

## About
This is the official NoPayn plugin for Magento 2.

NoPayn Payments helps entrepreneurs with the best, smartest and most efficient payment systems. Both in your physical store and online in your webshop. With a wide range of payment methods you can serve every customer.

## Features

The ideal online payment page for your webshop:

| Feature | Description |
|---------|-------------|
| 💳 Payment Methods | Wide range of payment methods to serve all customers |
| 🎨 Customization | Payment page entirely in the style of your website, making transactions less likely to be terminated |
| 📊 Reporting | Download your reports in the formats CAMT.053, MT940, MT940S & COD |
| 📱 Dashboard | One clear dashboard for all your payment, revenue and administrative functions |
| 🌐 Multilingual | Available in 21 languages: English, German, French, Spanish, Italian, Dutch, Portuguese, Swedish, Danish, Norwegian, Finnish, Polish, Czech, Slovak, Hungarian, Romanian, Greek, Bulgarian, Latvian, Lithuanian, Estonian |

## Version

| Version | Release Date |
|---------|-------------|
| 1.0.0 | Initial Release |

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | v8.2 to v8.4 |
| Magento | v2.4.6 to v2.4.8 |

## Supported Payment Methods

| Payment Method | Description |
|----------------|-------------|
| Credit Card | Accept payments with various credit and debit cards |
| Google Pay | Fast, simple checkout with Google Pay |
| Apple Pay | Seamless payments with Apple devices |
| Swish | Popular mobile payment system in Sweden |
| MobilePay | Mobile payment solution for Nordic countries |

## Installation

### Manual Installation

1. Go to `app/code` folder 
2. Unzip `nopayn.zip` attached to release 
3. After that run the Magento® upgrade and clean the caches:
   ```bash
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```
4. If Magento® is running in production mode you also need to redeploy the static content:
   ```bash
   php bin/magento setup:static-content:deploy
   ```
5. After the installation: Go to your Magento® admin portal and open 'Stores' > 'Configuration' > 'Payment Methods' > 'NoPayn Payments'.

## Configuration

After installation, you can configure the plugin by navigating to:
Magento Admin Panel > Stores > Configuration > Payment Methods > NoPayn Payments

## License

This project is licensed under the MIT License - see the LICENSE file for details.

---

<p align="center">
  Made with ❤️ by <a href="https://nopayn.io/">NoPayn</a>
</p>

# Changelog

All notable changes to `laravel-myanmar-payments` will be documented in this file

## 1.0.0 - 2022-11-14

- initial release
- Wave Money and 2c2p are provided

## 1.0.1

- JWT Token parser added for 2c2p 

### 1.0.5

- Response validator for Wave Money supported

### 1.0.6

- Optional userDefined fields for 2c2p are supported
  - The format of the config file was changed, and you will need to do following things.
    - delete config/laravel-myanmar-payments.php
    - run 
       ```
       php artisan vendor:publish --tag="laravel-myanmar-payments"
      ```

### Unreleased

- Removed 2C2P support
  - The `2c2p` channel, its config block and the `firebase/php-jwt` dependency are gone
  - Remove the `2C2P_*` variables from your .env
- Added Yoma MMQR support via the `yoma_mmqr` channel
  - Checkout an order, generate its MMQR, enquire the payment status and verify callback signatures
  - Add the `YOMA_MMQR_*` variables to your .env, then re-publish the config
     ```
     php artisan vendor:publish --tag="laravel-myanmar-payments"
    ```

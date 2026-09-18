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

### 2.2.6

- Removed 2C2P support
  - The `2c2p` channel, its config block and the `firebase/php-jwt` dependency are gone
  - Remove the `2C2P_*` variables from your .env
- Added Yoma MMQR support via the `yoma_mmqr` channel
  - Checkout an order, generate its MMQR, enquire the payment status and verify callback signatures
  - Add the `YOMA_MMQR_*` variables to your .env, then re-publish the config
     ```
     php artisan vendor:publish --tag="laravel-myanmar-payments"
    ```

### 2.2.7

- Yoma MMQR callbacks are verified with the dedicated webhook hash key Yoma issues, not the client secret
  - Add `YOMA_MMQR_WEBHOOK_HASHKEY` to your .env and re-publish the config. It is a separate credential from `YOMA_MMQR_WEBHOOK_SECRET`, and `verifySignature()` now throws when it is missing
- Yoma MMQR callback fields are read from the JSON body Yoma posts, which was previously ignored

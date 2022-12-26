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

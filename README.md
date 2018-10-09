# CakePHP 3.x cache engine for Redis Cluster

[![Latest Version](https://img.shields.io/packagist/v/riesenia/cakephp-rediscluster.svg?style=flat-square)](https://packagist.org/packages/riesenia/cakephp-rediscluster)
[![Total Downloads](https://img.shields.io/packagist/dt/riesenia/cakephp-rediscluster.svg?style=flat-square)](https://packagist.org/packages/riesenia/cakephp-rediscluster)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

PHP library providing basic shopping cart functionality.

## Installation

Install the latest version using `composer require riesenia/cakephp-rediscluster`

Or add to your *composer.json* file as a requirement:

```json
{
    "require": {
        "riesenia/cakephp-rediscluster": "~1.0"
    }
}
```

## Usage

Use as cache engine. For more information see [CakePHP Caching](https://book.cakephp.org/3.0/en/core-libraries/caching.html).

Example configuration:

```php
Cache::config('redis', [
    'className' => 'Riesenia/RedisCluster.RedisCluster',
    'server' => ['10.10.10.10:6379', '10.10.10.20:6379', '10.10.10.30:6379']
]);
```

## Slave failover / distribution

Slave failover / distibution can be configured by `failover` configuration key. See [related chapter](https://github.com/phpredis/phpredis/blob/master/cluster.markdown#automatic-slave-failover--distribution) in phpredis cluster readme.

Possible values are:

- *none* (default)
- *error*
- *distribute*
- *slaves*

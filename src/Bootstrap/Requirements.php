<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

final class Requirements
{
    public const MINIMUM_PHP_VERSION       = '8.1';
    public const MINIMUM_WORDPRESS_VERSION = '7.1';

    public function evaluate(string $phpVersion, string $wordpressVersion): RequirementResult
    {
        $errors = array();

        if (version_compare($phpVersion, self::MINIMUM_PHP_VERSION, '<')) {
            $errors[] = sprintf('PHP %s or newer is required.', self::MINIMUM_PHP_VERSION);
        }

        if (version_compare($wordpressVersion, self::MINIMUM_WORDPRESS_VERSION, '<')) {
            $errors[] = sprintf('WordPress %s or newer is required.', self::MINIMUM_WORDPRESS_VERSION);
        }

        return new RequirementResult($errors);
    }

    public function evaluateCurrentEnvironment(): RequirementResult
    {
        global $wp_version;

        return $this->evaluate(PHP_VERSION, is_string($wp_version) ? $wp_version : '0.0.0');
    }
}

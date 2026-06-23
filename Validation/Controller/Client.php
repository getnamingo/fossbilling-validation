<?php

/**
 * FOSSBilling.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   Apache-2.0
 *
 * Copyright FOSSBilling 2022
 * This software may contain code previously used in the BoxBilling project.
 * Copyright BoxBilling, Inc 2011-2021
 *
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

namespace Box\Mod\Validation\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    /**
     * Methods maps client areas urls to corresponding methods
     * Always use your module prefix to avoid conflicts with other modules
     * in future.
     *
     * @param \Box_App $app - returned by reference
     */
    public function register(\Box_App &$app): void
    {
        $app->get('/validation', 'get_index', [], static::class);
    }

    public function get_index(\Box_App $app)
    {
        // Access GET parameters and sanitize the token
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        if (isset($token)) {
            // Look up token in database
            $validation = $this->di['db']->getRow(
                'SELECT * FROM domain_contact_validation WHERE validation_token = :token LIMIT 1',
                ['token' => $token]
            );

            // If token is found and not yet validated, update database and display success message
            if ($validation && (int) $validation['is_validated'] === 0) {
                $validationLog = json_encode([
                    'timestamp' => date('Y-m-d H:i:s'),
                    'event' => 'validated',
                    'method' => 'email',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $this->di['db']->exec(
                    'UPDATE domain_contact_validation
                     SET is_validated = 1,
                         validation_checked_at = NOW(),
                         validation_log = ?,
                         updated_at = NOW()
                     WHERE id = ?',
                    [$validationLog, $validation['id']]
                );

                $message = 'Contact information validated successfully!';
            } else {
                // If token is not found or already validated, display error message
                $message = 'Error: Invalid or already validated validation token.';
            }
        } else {
            $message = 'Please provide a validation token.';
        }

        return $app->render('mod_validation_index', [
            'message' => $message,
        ]);
    }
}
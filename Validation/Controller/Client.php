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
            $client = $this->di['db']->getAll('SELECT * FROM client WHERE custom_1 = :token', ['token' => $token]);
            
            // If token is found and not yet validated, update database and display success message
            if ($client && $client['custom_2'] == 0) {
                $contact_id = $client['id'];
                $this->di['db']->exec( 'UPDATE client SET custom_2 = 1 WHERE id = ?' , [$contact_id] );
                $message = 'Contact information validated successfully!';
            }
            // If token is not found or already validated, display error message
            else {
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
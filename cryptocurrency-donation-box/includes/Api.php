<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * CdbbcMetaApi
 */
final class CdbbcMetaApi
{
    /**
     * @var string
     */
    const BASE_URL = 'https://metalocker.service.metaplugins.io';

    /**
     * Register REST routes
     *
     * @see https://developer.wordpress.org/reference/functions/register_rest_route/
     */
    public static function registerRoutes($namespace)
    {
        register_rest_route($namespace, 'key', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => __CLASS__ . '::getPublicKey',
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    /**
     * Get registration status
     *
     * @return bool
     */
    public static function getActivationStatus($plugin)
    {
        $resp = self::request('/v1/plugin/status', 'GET', [], self::getAuthToken($plugin));

        if ($resp['status'] === 400 || !$resp['body']) {
            return false;
        } else {
            $body = json_decode($resp['body']);
            return $body->status;
        }
    }

    /**
     * Do registration
     *
     * @param string $plugin The slug of the plugin.
     * @param string $email The user email.
     * @return bool
     */
    public static function registerSite($plugin, $email)
    {
        $resp = self::request('/v1/auth/register', 'POST', ['email' => $email], self::getAuthToken($plugin));

        if ($resp['status'] === 200) {
            $body = json_decode($resp['body']);
            return $body->status;
        } else {
            return false;
        }
    }

    /**
     * Get the public key
     */
    public static function getPublicKey($request)
    {
        $public_key = get_option('meta_public_key');

        if (!$public_key) {
            return new WP_Error('not_found', 'Public key not found!', ['status' => 404]);
        }

        return rest_ensure_response(['publicKey' => trim(preg_replace('/\s+/', ' ', $public_key))]);
    }

    /**
     * Setup keypair
     */
    public static function setupKeypair($force = false)
    {
        if (get_option('meta_public_key') && get_option('meta_private_key') && !$force) {
            return;
        }

        if (!function_exists('openssl_pkey_new')) {
            throw new Exception('OpenSSL extension is not installed!');
        } else {
            $rsa_key = openssl_pkey_new([
                'digest_alg' => 'sha256',
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            if (!$rsa_key) {
                throw new Exception(
                    sprintf(
                        /* translators: %s is an error message from OpenSSL. */
                        __('Unable to set up the private key. %s. Please try activating the plugin again!', 'cryptocurrency-donation-box'),
                        esc_html(openssl_error_string())
                    )
                );
            }
            

            $public_key = openssl_pkey_get_details($rsa_key)['key'];

            openssl_pkey_export($rsa_key, $private_key);

            if (!update_option('meta_public_key', trim($public_key)) || !update_option('meta_private_key', trim($private_key))) {
                throw new Exception('Failed to setup SSL keypair!');
            }
        }
    }

    /**
     * Authorize the Bearer JWT
     */
    public static function authorizeKey($key)
    {
        $site_url = get_site_url();
        $public_key = self::getServerPubKey();

        if (!$public_key) {
            return false;
        }

        try {
            $jwt_token = JWT::decode($key, new Key($public_key, 'RS256'));
        } catch (Exception $e) {
            $jwt_token = false;
        }

        if ($jwt_token->website !== $site_url) {
            return false;
        }

        return $jwt_token;
    }

    /**
     * Retrieve the JWT from the authorization header.
     *
     * @param array $request
     * @return string
     */
    public static function getAuthKey($request)
    {
        $authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? wp_unslash($_SERVER['HTTP_AUTHORIZATION']) : '';
    
        if (!empty($authorization) && preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
            return sanitize_text_field($matches[1]); // Ensure the token is sanitized
        }
    
        return '';
    }

    /**
     * Retrieve public key of the central server
     *
     * @return bool|string
     */
    public static function getServerPubKey()
    {
        $resp = self::request('/v1/key/public', 'GET', []);

        if ($resp['status'] !== 200) {
            return false;
        } else {
            $body = json_decode($resp['body']);
            return trim($body->publicKey);
        }
    }

       /**
     * Retrieve default Infura Id from database
     */
    public static function getInfuraId()
    {
        $site_url=  get_site_url();
        $headers = ["Origin: $site_url"];
        $resp = self::request('/v1/key/blockchain', 'GET', [], self::getAuthToken($plugin),  60,$headers);
        
        if ($resp['status'] !== 200) {
            return false;
        } else {
            $body = json_decode($resp['body']);
           return $body->key;
        }
    }
    /**
     * Get generated private key
     *
     * @return string
     */
    public static function getPrivateKey()
    {
        return wp_unslash(get_option('meta_private_key'));
    }

    /**
     * Do a CURL request
     *
     * @param string $endpoint Endpoint path. Relative to the BASE_URL.
     * @param string $method
     * @param array $params
     * @return array
     */
    public static function request($endpoint, $method, array $params = [], $auth = false, $timeout = 60, $passedHeaders = [])
    {
        $url = self::BASE_URL . $endpoint;
        $headers = [
            'Accept'     => 'application/json',
            'User-Agent' => 'MetaPlugins',
        ];
    
        $headers = array_merge($headers, $passedHeaders);

        if ($auth) {
            $headers['Authorization'] = 'Bearer ' . $auth;
        }

        $args = [
            'method'  => $method, // GET, POST, PUT, DELETE
            'timeout' => $timeout,
            'headers' => $headers,
        ];
        if (!empty($params) && is_array($params)) {
            if ($method === 'GET') {
                // For GET requests, ensure params are correctly formatted in the query string
                $url = add_query_arg($params, $url);
            } else {
                // For POST/PUT requests, encode params as JSON
                $args['body'] = wp_json_encode($params);
                $args['headers']['Content-Type'] = 'application/json';
            }
        }
        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'body'   => $response->get_error_message(),
            ];
        }
    
        // Get the HTTP status code and response body
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);    
        return [
            'status' => $code,
            'body'   => $body,
        ];
    }
    
    
    

    /**
     * Create a Bearer token for authorization
     *
     * @param string $plugin Slug of the plugin.
     * @return string
     */
    public static function getAuthToken($plugin)
    {
        $transient = get_transient('donation_auth_token');
        if (empty($transient)) {
            $time = new DateTimeImmutable();

            $claims = [               
                'iat' => $time->modify('-2 minutes')->getTimestamp(),
                'exp' => $time->modify('+2 hour')->getTimestamp(),
                'slug' => '/wp-json/' . $plugin,
                'website' => get_site_url(),
                'name' => $plugin,
                'ver' => CDBBC_VERSION,
            ];
            self::setupKeypair();
            $token = JWT::encode($claims, self::getPrivateKey(), 'RS256');

            set_transient('donation_auth_token', $token, 120 * MINUTE_IN_SECONDS);
            return $token;
        } else {
            return $transient;
        }
    }
}

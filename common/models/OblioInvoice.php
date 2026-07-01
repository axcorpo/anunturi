<?php

namespace common\models;

use Yii;
use yii\helpers\Json;

/**
 * OblioInvoice model for integration with Oblio API
 * 
 * This model extends the Invoice model and provides methods to interact
 * with the Oblio REST API for invoice management.
 * 
 * API Documentation: https://www.oblio.eu/api
 */
class OblioInvoice extends Invoice
{
    const API_BASE_URL = 'https://www.oblio.eu/api';
    
    // Cache key for access token
    const TOKEN_CACHE_KEY = 'oblio_access_token';
    
    // API rate limits
    const RATE_LIMIT_DOCUMENT_GENERATION = 30; // requests per 100 seconds
    const RATE_LIMIT_OTHER_REQUESTS = 30; // requests per 10 seconds
    
    /**
     * Get Oblio API credentials from settings
     * 
     * @return array|null Array with 'client_id' and 'client_secret' or null if not configured
     */
    protected static function getApiCredentials()
    {
        $clientId = Yii::$app->settings->get('oblioClientId', 'general');
        $clientSecret = Yii::$app->settings->get('oblioClientSecret', 'general');
        
        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }
        
        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
    }
    
    /**
     * Authorize and get access token from Oblio API
     * 
     * Uses OAuth 2.0 for authorization. The access token expires after 3600 seconds (1 hour).
     * Token is cached to avoid unnecessary API calls.
     * 
     * @param bool $forceRefresh Force token refresh even if cached token exists
     * @return array|null Array with token data or null on failure
     * @throws \Exception
     */
    public static function authorize($forceRefresh = false)
    {
        // Check cache first
        if (!$forceRefresh) {
            $cachedToken = Yii::$app->cache->get(self::TOKEN_CACHE_KEY);
            if ($cachedToken !== false) {
                return $cachedToken;
            }
        }
        
        $credentials = self::getApiCredentials();
        if ($credentials === null) {
            throw new \Exception('Oblio API credentials not configured in settings.');
        }
        
        $url = self::API_BASE_URL . '/authorize/token';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($credentials));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to authorize with Oblio API. HTTP Code: ' . $httpCode);
        }
        
        $data = Json::decode($response);
        
        if (isset($data['access_token'])) {
            // Cache token for slightly less than expiration time to be safe
            $cacheTime = isset($data['expires_in']) ? (int)$data['expires_in'] - 60 : 3540;
            Yii::$app->cache->set(self::TOKEN_CACHE_KEY, $data, $cacheTime);
            
            return $data;
        }
        
        throw new \Exception('Invalid response from Oblio API authorization.');
    }
    
    /**
     * Get valid access token
     * 
     * Returns cached token or requests a new one if needed
     * 
     * @return string Access token
     * @throws \Exception
     */
    protected static function getAccessToken()
    {
        $tokenData = self::authorize();
        return $tokenData['access_token'];
    }
    
    /**
     * Make API request to Oblio
     * 
     * @param string $endpoint API endpoint (e.g., '/docs/invoice')
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array|null $data Request data
     * @param bool $isJson Whether to send data as JSON or form-urlencoded
     * @return array Response data
     * @throws \Exception
     */
    protected static function makeApiRequest($endpoint, $method = 'GET', $data = null, $isJson = true)
    {
        $accessToken = self::getAccessToken();
        $url = self::API_BASE_URL . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
        ];
        
        if ($method === 'GET' && $data !== null) {
            $url .= '?' . http_build_query($data);
        } else {
            if ($isJson && $data !== null) {
                $headers[] = 'Content-Type: application/json';
                $postData = Json::encode($data);
            } elseif ($data !== null) {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $postData = http_build_query($data);
            }
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = Json::decode($response);
        
        if ($httpCode !== 200) {
            $errorMessage = isset($result['statusMessage']) ? $result['statusMessage'] : 'Unknown error';
            throw new \Exception('Oblio API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
        
        return $result;
    }
    
    /**
     * Get list of companies from Oblio nomenclature
     * 
     * @return array List of companies
     * @throws \Exception
     */
    public static function getCompanies()
    {
        return self::makeApiRequest('/nomenclature/companies', 'GET');
    }
    
    /**
     * Get VAT rates for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @return array List of VAT rates
     * @throws \Exception
     */
    public static function getVatRates($cif)
    {
        return self::makeApiRequest('/nomenclature/vat_rates', 'GET', ['cif' => $cif]);
    }
    
    /**
     * Get clients for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @param array $filters Optional filters: name, clientCif, offset
     * @return array List of clients
     * @throws \Exception
     */
    public static function getClients($cif, $filters = [])
    {
        $params = array_merge(['cif' => $cif], $filters);
        return self::makeApiRequest('/nomenclature/clients', 'GET', $params);
    }
    
    /**
     * Get products for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @param array $filters Optional filters: name, code, management, workStation, offset
     * @return array List of products
     * @throws \Exception
     */
    public static function getProducts($cif, $filters = [])
    {
        $params = array_merge(['cif' => $cif], $filters);
        return self::makeApiRequest('/nomenclature/products', 'GET', $params);
    }
    
    /**
     * Get document series for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @return array List of series
     * @throws \Exception
     */
    public static function getSeries($cif)
    {
        return self::makeApiRequest('/nomenclature/series', 'GET', ['cif' => $cif]);
    }
    
    /**
     * Get available languages for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @return array List of languages
     * @throws \Exception
     */
    public static function getLanguages($cif)
    {
        return self::makeApiRequest('/nomenclature/languages', 'GET', ['cif' => $cif]);
    }
    
    /**
     * Get management/warehouses for a company
     * 
     * @param string $cif Company CIF/Tax ID
     * @return array List of managements
     * @throws \Exception
     */
    public static function getManagement($cif)
    {
        return self::makeApiRequest('/nomenclature/management', 'GET', ['cif' => $cif]);
    }
    
    /**
     * Create proforma invoice in Oblio
     * 
     * @param array $data Proforma data as per Oblio API specification
     * @return array Response with seriesName, number, link
     * @throws \Exception
     */
    public static function createProforma($data)
    {
        return self::makeApiRequest('/docs/proforma', 'POST', $data, true);
    }
    
    /**
     * Create notice (delivery note) in Oblio
     * 
     * @param array $data Notice data as per Oblio API specification
     * @return array Response with seriesName, number, link
     * @throws \Exception
     */
    public static function createNotice($data)
    {
        return self::makeApiRequest('/docs/notice', 'POST', $data, true);
    }
    
    /**
     * Create invoice in Oblio
     * 
     * @param array $data Invoice data as per Oblio API specification
     * Example structure:
     * [
     *     'cif' => 'RO37311090',
     *     'client' => [...],
     *     'issueDate' => '2024-01-15',
     *     'dueDate' => '2024-01-30',
     *     'seriesName' => 'FCT',
     *     'products' => [...],
     *     // ... other parameters
     * ]
     * 
     * @return array Response with seriesName, number, link
     * @throws \Exception
     */
    public static function createInvoice($data)
    {
        return self::makeApiRequest('/docs/invoice', 'POST', $data, true);
    }
    
    /**
     * Get invoice details from Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Invoice data
     * @throws \Exception
     */
    public static function getInvoice($cif, $seriesName, $number)
    {
        $params = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/invoice', 'GET', $params);
    }
    
    /**
     * Get proforma details from Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Proforma data
     * @throws \Exception
     */
    public static function getProforma($cif, $seriesName, $number)
    {
        $params = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/proforma', 'GET', $params);
    }
    
    /**
     * Get notice details from Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Notice data
     * @throws \Exception
     */
    public static function getNotice($cif, $seriesName, $number)
    {
        $params = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/notice', 'GET', $params);
    }
    
    /**
     * Collect payment for invoice
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @param array $collectData Collection data
     * Example structure:
     * [
     *     'type' => 'Ordin de plata',
     *     'documentNumber' => 'OP 7001',
     *     'value' => 428.40,
     *     'issueDate' => '2024-01-15',
     *     'mentions' => 'Payment note'
     * ]
     * 
     * @return array Response with document data and collects array
     * @throws \Exception
     */
    public static function collectInvoice($cif, $seriesName, $number, $collectData)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
            'collect' => $collectData,
        ];
        return self::makeApiRequest('/docs/invoice/collect', 'PUT', $data, false);
    }
    
    /**
     * Cancel invoice in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function cancelInvoice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/invoice/cancel', 'PUT', $data, false);
    }
    
    /**
     * Cancel proforma in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function cancelProforma($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/proforma/cancel', 'PUT', $data, false);
    }
    
    /**
     * Cancel notice in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function cancelNotice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/notice/cancel', 'PUT', $data, false);
    }
    
    /**
     * Restore invoice in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function restoreInvoice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/invoice/restore', 'PUT', $data, false);
    }
    
    /**
     * Restore proforma in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function restoreProforma($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/proforma/restore', 'PUT', $data, false);
    }
    
    /**
     * Restore notice in Oblio
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function restoreNotice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/notice/restore', 'PUT', $data, false);
    }
    
    /**
     * Delete invoice in Oblio
     * 
     * Note: Can only delete the last document in a series
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function deleteInvoice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/invoice', 'DELETE', $data, false);
    }
    
    /**
     * Delete proforma in Oblio
     * 
     * Note: Can only delete the last document in a series
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function deleteProforma($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/proforma', 'DELETE', $data, false);
    }
    
    /**
     * Delete notice in Oblio
     * 
     * Note: Can only delete the last document in a series
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with status message
     * @throws \Exception
     */
    public static function deleteNotice($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/notice', 'DELETE', $data, false);
    }
    
    /**
     * List invoices with optional filters
     * 
     * @param string $cif Company CIF/Tax ID
     * @param array $filters Optional filters
     * Available filters:
     * - seriesName: Filter by series name
     * - number: Filter by document number
     * - id: Filter by ID
     * - draft: -1 (ignore), 0 (not draft), 1 (draft)
     * - client: Array with cif, email, phone, or code
     * - canceled: -1 (ignore), 0 (not canceled), 1 (canceled)
     * - issuedAfter: Start date in YYYY-MM-DD format
     * - issuedBefore: End date in YYYY-MM-DD format
     * - withProducts: Include products in result (boolean)
     * - withEinvoiceStatus: Include e-invoice status (boolean)
     * - orderBy: Order by field (id, issueDate, number)
     * - orderDir: ASC or DESC
     * - limitPerPage: Results per page (max 100)
     * - offset: Pagination offset (multiples of 100)
     * 
     * @return array List of invoices
     * @throws \Exception
     */
    public static function listInvoices($cif, $filters = [])
    {
        $params = array_merge(['cif' => $cif], $filters);
        return self::makeApiRequest('/docs/invoice/list', 'GET', $params);
    }
    
    /**
     * Send invoice to SPV (e-Factura system)
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return array Response with send status
     * Response codes:
     * - -1: e-Invoice not sent to SPV
     * - 0: e-Invoice arrived at SPV and is being processed
     * - 1: e-Invoice successfully sent to SPV
     * - 2: e-Invoice has errors and was not sent to SPV
     * 
     * @throws \Exception
     */
    public static function sendToSpv($cif, $seriesName, $number)
    {
        $data = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        return self::makeApiRequest('/docs/einvoice', 'POST', $data, false);
    }
    
    /**
     * Download SPV archive for an invoice
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $seriesName Document series name
     * @param string $number Document number
     * @return string Binary content of the archive
     * @throws \Exception
     */
    public static function downloadSpvArchive($cif, $seriesName, $number)
    {
        $accessToken = self::getAccessToken();
        $params = [
            'cif' => $cif,
            'seriesName' => $seriesName,
            'number' => $number,
        ];
        $url = self::API_BASE_URL . '/docs/einvoice?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('Failed to download SPV archive. HTTP Code: ' . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * Create webhook subscription
     * 
     * @param string $cif Company CIF/Tax ID
     * @param string $topic Event topic to subscribe to
     * Available topics:
     * - stock
     * - Invoice/SaveDraft
     * - Proforma/SaveDraft
     * - Notice/SaveDraft
     * - TaxReceipt/SaveDraft
     * - Invoice/Update
     * - Proforma/Update
     * - Notice/Update
     * - Invoice/Cancel
     * - Proforma/Cancel
     * - Notice/Cancel
     * - TaxReceipt/Cancel
     * - Collect/Inserted
     * 
     * @param string $endpoint URL endpoint that will receive webhook notifications
     * @return array Response with webhook ID
     * @throws \Exception
     */
    public static function createWebhook($cif, $topic, $endpoint)
    {
        $data = [
            'cif' => $cif,
            'topic' => $topic,
            'endpoint' => $endpoint,
        ];
        return self::makeApiRequest('/webhooks', 'POST', $data, true);
    }
    
    /**
     * List all webhooks
     * 
     * @return array List of webhooks
     * @throws \Exception
     */
    public static function listWebhooks()
    {
        return self::makeApiRequest('/webhooks', 'GET');
    }
    
    /**
     * Delete webhook
     * 
     * @param string $webhookId Webhook ID
     * @return array Response with status message
     * @throws \Exception
     */
    public static function deleteWebhook($webhookId)
    {
        return self::makeApiRequest('/webhooks/' . $webhookId, 'DELETE');
    }
    
    /**
     * Build client data array for Oblio API
     * 
     * @param array $clientData Client information
     * @return array Formatted client data
     */
    public static function buildClientData($clientData)
    {
        return [
            'cif' => $clientData['cif'] ?? '',
            'name' => $clientData['name'] ?? '',
            'rc' => $clientData['rc'] ?? '',
            'code' => $clientData['code'] ?? '',
            'address' => $clientData['address'] ?? '',
            'state' => $clientData['state'] ?? '',
            'city' => $clientData['city'] ?? '',
            'country' => $clientData['country'] ?? '',
            'iban' => $clientData['iban'] ?? '',
            'bank' => $clientData['bank'] ?? '',
            'email' => $clientData['email'] ?? '',
            'phone' => $clientData['phone'] ?? '',
            'contact' => $clientData['contact'] ?? '',
            'vatPayer' => $clientData['vatPayer'] ?? true,
            'save' => $clientData['save'] ?? 0,
            'autocomplete' => $clientData['autocomplete'] ?? 0,
        ];
    }
    
    /**
     * Build product data array for Oblio API
     * 
     * @param array $productData Product information
     * @return array Formatted product data
     */
    public static function buildProductData($productData)
    {
        return [
            'name' => $productData['name'] ?? '',
            'code' => $productData['code'] ?? '',
            'description' => $productData['description'] ?? '',
            'price' => $productData['price'] ?? 0,
            'measuringUnit' => $productData['measuringUnit'] ?? 'buc',
            'currency' => $productData['currency'] ?? 'RON',
            'exchangeRate' => $productData['exchangeRate'] ?? null,
            'vatName' => $productData['vatName'] ?? 'Normala',
            'vatPercentage' => $productData['vatPercentage'] ?? 19,
            'vatIncluded' => $productData['vatIncluded'] ?? 1,
            'quantity' => $productData['quantity'] ?? 1,
            'management' => $productData['management'] ?? null,
            'productType' => $productData['productType'] ?? 'Serviciu',
            'nameTranslation' => $productData['nameTranslation'] ?? null,
            'measuringUnitTranslation' => $productData['measuringUnitTranslation'] ?? null,
            'save' => $productData['save'] ?? 1,
        ];
    }
    
    /**
     * Build discount data array for Oblio API
     * 
     * @param string $name Discount name
     * @param float $discount Discount value
     * @param string $discountType 'procentual' or 'valoric'
     * @param int $discountAllAbove Apply to all products above (0 or 1)
     * @return array Formatted discount data
     */
    public static function buildDiscountData($name, $discount, $discountType = 'valoric', $discountAllAbove = 0)
    {
        return [
            'name' => $name,
            'discount' => $discount,
            'discountType' => $discountType,
            'discountAllAbove' => $discountAllAbove,
        ];
    }
    
    /**
     * Build complete invoice data for Oblio API
     * 
     * Helper method to build a complete invoice structure
     * 
     * @param string $cif Company CIF/Tax ID
     * @param array $client Client data
     * @param array $products Array of products
     * @param array $options Additional invoice options
     * @return array Complete invoice data ready for API submission
     */
    public static function buildInvoiceData($cif, $client, $products, $options = [])
    {
        return [
            'cif' => $cif,
            'client' => self::buildClientData($client),
            'issueDate' => $options['issueDate'] ?? date('Y-m-d'),
            'dueDate' => $options['dueDate'] ?? date('Y-m-d', strtotime('+30 days')),
            'deliveryDate' => $options['deliveryDate'] ?? null,
            'collectDate' => $options['collectDate'] ?? null,
            'seriesName' => $options['seriesName'] ?? 'FCT',
            'disableAutoSeries' => $options['disableAutoSeries'] ?? 0,
            'number' => $options['number'] ?? null,
            'language' => $options['language'] ?? 'RO',
            'precision' => $options['precision'] ?? 2,
            'currency' => $options['currency'] ?? 'RON',
            'exchangeRate' => $options['exchangeRate'] ?? null,
            'products' => $products,
            'issuerName' => $options['issuerName'] ?? '',
            'issuerId' => $options['issuerId'] ?? '',
            'noticeNumber' => $options['noticeNumber'] ?? '',
            'internalNote' => $options['internalNote'] ?? '',
            'deputyName' => $options['deputyName'] ?? '',
            'deputyIdentityCard' => $options['deputyIdentityCard'] ?? '',
            'deputyAuto' => $options['deputyAuto'] ?? '',
            'selesAgent' => $options['selesAgent'] ?? '',
            'mentions' => $options['mentions'] ?? '',
            'workStation' => $options['workStation'] ?? 'Sediu',
            'sendEmail' => $options['sendEmail'] ?? 0,
            'useStock' => $options['useStock'] ?? 1,
            'spvExtern' => $options['spvExtern'] ?? 0,
            'collect' => $options['collect'] ?? null,
            'referenceDocument' => $options['referenceDocument'] ?? null,
            'orderNumber' => $options['orderNumber'] ?? '',
            'contractNumber' => $options['contractNumber'] ?? '',
            'receptionNotice' => $options['receptionNotice'] ?? '',
            'projectNumber' => $options['projectNumber'] ?? '',
            'buyerIdentifier' => $options['buyerIdentifier'] ?? '',
            'clientAccountReference' => $options['clientAccountReference'] ?? '',
        ];
    }
    
    /**
     * Create invoice from current model instance
     * 
     * Converts current Invoice model to Oblio format and creates it via API
     * 
     * @param string $cif Company CIF/Tax ID
     * @param array $additionalData Additional data to merge
     * @return array Response from Oblio API
     * @throws \Exception
     */
    public function sendToOblio($cif, $additionalData = [])
    {
        // Build invoice data from current model
        // This is a basic implementation - customize based on your Invoice model structure
        
        $invoiceData = [
            'cif' => $cif,
            'seriesName' => $this->document_series ?? 'FCT',
            'issueDate' => $this->issued_at ?? date('Y-m-d'),
            'dueDate' => $this->due_at ?? date('Y-m-d', strtotime('+30 days')),
            // Add more fields based on your Invoice model structure
        ];
        
        $invoiceData = array_merge($invoiceData, $additionalData);
        
        return self::createInvoice($invoiceData);
    }
}


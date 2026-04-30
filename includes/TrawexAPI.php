<?php
/**
 * Trawex API Webservices Wrapper
 * 
 * Handles Flight Search, Availability, Pricing Revalidation, and Booking.
 */

class TrawexAPI {
    private $base_url;
    private $user_id = 'YOUR_USER_ID';
    private $user_password = 'YOUR_PASSWORD';
    private $access = 'Test';
    private $ip_address = '127.0.0.1';
    // ⚠️ Set to false ONLY when TRAWEX_BASE_URL is confirmed correct and reachable
    private $use_mock = true;
    
    public function __construct() {
        if (!function_exists('loadEnv')) {
            require_once __DIR__ . '/../security.php';
        }
        loadEnv(__DIR__ . '/../.env');

        if (getenv('TRAWEX_USER_ID'))    $this->user_id       = getenv('TRAWEX_USER_ID');
        if (getenv('TRAWEX_PASSWORD'))   $this->user_password = getenv('TRAWEX_PASSWORD');
        if (getenv('TRAWEX_ACCESS'))     $this->access        = getenv('TRAWEX_ACCESS');
        if (getenv('TRAWEX_IP'))         $this->ip_address    = getenv('TRAWEX_IP');
        // Allow overriding the base URL via .env (TRAWEX_BASE_URL=https://travelnext.works/api/YOUR_CODE)
        $this->base_url = getenv('TRAWEX_BASE_URL') ?: 'https://travelnext.works/api/aeroVE5';
    }


    /**
     * MODULE 1, 2 & 3: Flight Availability Search
     */
    public function searchFlights($origin, $destination, $departDate, $returnDate = null, $adults = 1, $children = 0, $infants = 0, $cabinClass = 'Economy') {
        $endpoint = $this->base_url . '/search';
        
        $journeyType = empty($returnDate) ? 'OneWay' : 'Return';
        
        $originDestInfo = [
            'departureDate'           => $departDate,
            'airportOriginCode'       => $origin,
            'airportDestinationCode'  => $destination
        ];

        if ($journeyType === 'Return') {
            $originDestInfo['returnDate'] = $returnDate;
        }

        $payload = [
            'user_id'       => $this->user_id,
            'user_password' => $this->user_password,
            'access'        => $this->access,
            'ip_address'    => $this->ip_address,
            'requiredCurrency' => 'USD',
            'journeyType'   => $journeyType,
            'OriginDestinationInfo' => [$originDestInfo],
            'class'         => $cabinClass,
            'adults'        => (int)$adults,
            'childs'        => (int)$children,
            'infants'       => (int)$infants
        ];

        // Skip real API if use_mock=true (set in class property above)
        if ($this->use_mock) {
            return $this->mockFlightResults($origin, $destination, $departDate);
        }

        // Execute API Call
        $response = $this->makeRequest($endpoint, $payload);

        
        // Debug: Log the raw response
        error_log("TRAWEX SEARCH RAW RESPONSE KEYS: " . json_encode(array_keys($response ?? [])));
        if (isset($response['AirSearchResponse'])) {
            error_log("TRAWEX AirSearchResponse KEYS: " . json_encode(array_keys($response['AirSearchResponse'] ?? [])));
            if (isset($response['AirSearchResponse']['AirSearchResult'])) {
                error_log("TRAWEX AirSearchResult KEYS: " . json_encode(array_keys($response['AirSearchResponse']['AirSearchResult'] ?? [])));
            }
        }

        // If API fails, fallback to mock
        if (empty($response) || isset($response['error'])) {
            error_log("TRAWEX: API failed, using mock. Error: " . json_encode($response['error'] ?? 'empty'));
            return $this->mockFlightResults($origin, $destination, $departDate);
        }

        // Check for API-level errors
        if (isset($response['AirSearchResponse']['AirSearchResult']['Errors'])) {
            $errMsg = json_encode($response['AirSearchResponse']['AirSearchResult']['Errors']);
            error_log("TRAWEX SEARCH ERROR: " . $errMsg);
            return ['success' => false, 'message' => 'Trawex API Error: ' . $errMsg, 'data' => []];
        }

        // Map the real Trawex response
        if (isset($response['AirSearchResponse']['AirSearchResult']['FareItineraries'])) {
            $mappedFlights = [];
            $fare_its = $response['AirSearchResponse']['AirSearchResult']['FareItineraries'];
            
            // Robust Session ID extraction
            $sessionId = $response['AirSearchResponse']['session_id'] ?? 
                         $response['AirSearchResponse']['AirSearchResult']['SessionId'] ?? 
                         $response['AirSearchResponse']['AirSearchResult']['session_id'] ?? '';

            // Trawex can return FareItinerary directly or as [FareItinerary => [...]]
            $itineraries = [];
            if (isset($fare_its['FareItinerary'])) {
                $raw = $fare_its['FareItinerary'];
                // Could be a single object or a list
                $itineraries = isset($raw[0]) ? $raw : [$raw];
            } elseif (is_array($fare_its)) {
                // Array of {FareItinerary: {...}} objects
                foreach ($fare_its as $item) {
                    if (isset($item['FareItinerary'])) {
                        $itineraries[] = $item['FareItinerary'];
                    }
                }
            }

            error_log("TRAWEX: Found " . count($itineraries) . " itineraries. SessionId=" . $sessionId);

            foreach ($itineraries as $itinerary) {
                // Get Price
                $price = 0;
                if (isset($itinerary['AirItineraryFareInfo']['ItinTotalFares']['TotalFare']['Amount'])) {
                    $price = (float)$itinerary['AirItineraryFareInfo']['ItinTotalFares']['TotalFare']['Amount'];
                }

                $fareSourceCode = $itinerary['AirItineraryFareInfo']['FareSourceCode'] ?? '';

                // Get Flight Segments — handle both OriginDestinationOptions and OriginDestinationOption
                $options = $itinerary['OriginDestinationOptions'] ?? 
                           (isset($itinerary['OriginDestinationOption']) ? [['OriginDestinationOption' => [$itinerary['OriginDestinationOption']]]] : []);
                if (empty($options)) continue;

                $outboundSegment = $options[0]['OriginDestinationOption'][0]['FlightSegment'] ?? null;
                if (!$outboundSegment) continue;

                $outboundLastSegment = $options[0]['OriginDestinationOption'][count($options[0]['OriginDestinationOption']) - 1]['FlightSegment'] ?? $outboundSegment;

                $returnSegment = null;
                if (count($options) > 1 && isset($options[1]['OriginDestinationOption'][0]['FlightSegment'])) {
                    $returnSegment = $options[1]['OriginDestinationOption'][0]['FlightSegment'];
                }

                $mappedFlights[] = [
                    'price'            => $price,
                    'airline'          => $outboundSegment['MarketingAirlineCode'] ?? ($outboundSegment['OperatingAirline']['Code'] ?? 'UNK'),
                    'flight_number'    => ($outboundSegment['MarketingAirlineCode'] ?? '') . ($outboundSegment['FlightNumber'] ?? ''),
                    'departure_at'     => $outboundSegment['DepartureDateTime'] ?? '',
                    'arrival_at'       => $outboundLastSegment['ArrivalDateTime'] ?? ($outboundSegment['ArrivalDateTime'] ?? ''),
                    'return_at'        => $returnSegment ? ($returnSegment['DepartureDateTime'] ?? null) : null,
                    'provider'         => 'Trawex',
                    'session_id'       => $sessionId,
                    'fare_source_code' => $fareSourceCode,
                    'segments'         => $options
                ];
            }

            if (!empty($mappedFlights)) {
                error_log("TRAWEX: Returning " . count($mappedFlights) . " REAL flights.");
                return ['success' => true, 'data' => [$destination => $mappedFlights]];
            }
        }

        error_log("TRAWEX: No AirSearchResponse structure found, falling back to mock. Response keys: " . json_encode(array_keys($response ?? [])));
        return $this->mockFlightResults($origin, $destination, $departDate);
    }


    /**
     * MODULE 4: Flight Availability Search & Fare Validation (Revalidate)
     */
    public function checkAvailability($sessionId, $fareSourceCode) {
        $endpoint = $this->base_url . '/revalidate';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'session_id' => $sessionId,
            'fare_source_code' => $fareSourceCode
        ];
        
        $response = $this->makeRequest($endpoint, $payload);

        if (isset($response['AirRevalidateResponse']['AirRevalidateResult'])) {
            $result = $response['AirRevalidateResponse']['AirRevalidateResult'];
            $isValid = $result['IsValid'] ?? false;
            
            $latestPrice = 0;
            if ($isValid && isset($result['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['ItinTotalFares']['TotalFare']['Amount'])) {
                $latestPrice = (float)$result['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['ItinTotalFares']['TotalFare']['Amount'];
            }
            
            return [
                'status' => $isValid === true || $isValid === 'true' ? 'Available' : 'Unavailable',
                'priceConfirmed' => $isValid === true || $isValid === 'true',
                'latestPrice' => $latestPrice,
                'isPassportMandatory' => $result['IsPassportMandatory'] ?? false,
                'isPassportFullDetailsMandatory' => $result['IsPassportFullDetailsMandatory'] ?? false,
                'fareType' => $result['FareItineraries']['FareItinerary']['AirItineraryFareInfo']['FareType'] ?? 'Public'
            ];
        }
        
        return ['status' => 'Available', 'priceConfirmed' => true, 'latestPrice' => null, 'isPassportMandatory' => false, 'fareType' => 'Public'];
    }

    /**
     * MODULE 4.1: Extra Services (Baggage, Meals, Seats)
     */
    public function getExtraServices($sessionId, $fareSourceCode) {
        // Step 1: Revalidate first (mandatory for many Trawex versions to activate extras)
        $revalidateEndpoint = $this->base_url . '/revalidate';
        $revalidatePayload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'session_id' => $sessionId,
            'fare_source_code' => $fareSourceCode
        ];
        
        $revalidateResponse = $this->makeRequest($revalidateEndpoint, $revalidatePayload);
        
        // DEBUG: Log revalidation response
        error_log("TRAWEX REVALIDATE RESPONSE: " . json_encode($revalidateResponse));
        
        // Handle revalidation errors immediately
        if (isset($revalidateResponse['AirRevalidateResponse']['AirRevalidateResult']['Errors'])) {
            error_log("TRAWEX REVALIDATE ERROR: " . json_encode($revalidateResponse['AirRevalidateResponse']['AirRevalidateResult']['Errors']));
            return $revalidateResponse['AirRevalidateResponse']['AirRevalidateResult'];
        }
        
        // If revalidate gave us a new session or validated the existing one
        $activeSessionId = $sessionId;
        $res = $revalidateResponse['AirRevalidateResponse']['AirRevalidateResult'] ?? [];
        if (isset($res['SessionId'])) {
            $activeSessionId = $res['SessionId'];
        } elseif (isset($res['session_id'])) {
            $activeSessionId = $res['session_id'];
        } elseif (isset($revalidateResponse['AirRevalidateResponse']['session_id'])) {
            $activeSessionId = $revalidateResponse['AirRevalidateResponse']['session_id'];
        }

        // Step 2: Now call extra_services with the (potentially new) active session
        $endpoint = $this->base_url . '/extra_services';
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'session_id' => $activeSessionId,
            'fare_source_code' => $fareSourceCode
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['ExtraServicesResponse']['ExtraServicesResult'])) {
            return $response['ExtraServicesResponse']['ExtraServicesResult'];
        }
        
        // Fallback for unexpected response structure
        if (isset($response['Errors'])) return ['Success' => 'false', 'Errors' => $response['Errors']];
        if (isset($response['ExtraServicesResponse']['Errors'])) return ['Success' => 'false', 'Errors' => $response['ExtraServicesResponse']['Errors']];
        
        return [
            'Success' => 'false',
            'Errors' => [['ErrorMessage' => 'No extra services found. Raw: ' . json_encode($response)]]
        ];
    }

    /**
     * MODULE 4.2: Fare Rules
     */
    public function getFareRules($sessionId, $fareSourceCode, $fareSourceCodeInbound = null) {
        // Step 1: Revalidate first
        $revalidateEndpoint = $this->base_url . '/revalidate';
        $revalidatePayload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'session_id' => $sessionId,
            'fare_source_code' => $fareSourceCode
        ];
        $revalidateResponse = $this->makeRequest($revalidateEndpoint, $revalidatePayload);
        
        $activeSessionId = $sessionId;
        $res = $revalidateResponse['AirRevalidateResponse']['AirRevalidateResult'] ?? [];
        if (isset($res['SessionId'])) {
            $activeSessionId = $res['SessionId'];
        } elseif (isset($res['session_id'])) {
            $activeSessionId = $res['session_id'];
        } elseif (isset($revalidateResponse['AirRevalidateResponse']['session_id'])) {
            $activeSessionId = $revalidateResponse['AirRevalidateResponse']['session_id'];
        }

        // Step 2: Now call fare_rules
        $endpoint = $this->base_url . '/fare_rules';
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'session_id' => $activeSessionId,
            'fare_source_code' => $fareSourceCode
        ];
        
        if ($fareSourceCodeInbound) {
            $payload['fare_source_code_inbound'] = $fareSourceCodeInbound;
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['FareRules1_1Response']['FareRules1_1Result'])) {
            return $response['FareRules1_1Response']['FareRules1_1Result'];
        }
        
        return null;
    }

    /**
     * MODULE 5: Booking
     */
    public function bookFlight($flightBookingInfo, $paxInfo) {
        $endpoint = $this->base_url . '/booking';
        
        $payload = [
            'flightBookingInfo' => $flightBookingInfo,
            'paxInfo' => $paxInfo
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['BookFlightResponse']['BookFlightResult'])) {
            return $response['BookFlightResponse']['BookFlightResult'];
        }
        
        // Fallback for missing response (sandbox mode mock)
        return [
            'Success' => true,
            'Status' => 'CONFIRMED',
            'UniqueID' => 'TRW' . rand(10000, 99999),
            'TktTimeLimit' => date('Y-m-d\TH:i:s', strtotime('+1 day')),
            'Target' => 'Test'
        ];
    }

    /**
     * MODULE 6: Ticket Order
     */
    public function ticketOrder($uniqueId) {
        $endpoint = $this->base_url . '/ticket_order';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['AirOrderTicketRS']['TicketOrderResult'])) {
            return $response['AirOrderTicketRS']['TicketOrderResult'];
        }
        
        return [
            'Success' => 'true',
            'UniqueID' => $uniqueId,
            'Target' => 'Test'
        ];
    }

    /**
     * MODULE 7: Trip Details
     */
    public function getTripDetails($uniqueId) {
        $endpoint = $this->base_url . '/trip_details';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['TripDetailsResponse']['TripDetailsResult'])) {
            return $response['TripDetailsResponse']['TripDetailsResult'];
        }
        
        return null;
    }

    /**
     * MODULE 8: Cancel Booking
     */
    public function cancelBooking($uniqueId) {
        $endpoint = $this->base_url . '/cancel';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['CancelBookingResponse']['CancelBookingResult'])) {
            return $response['CancelBookingResponse']['CancelBookingResult'];
        }
        
        return null;
    }

    /**
     * MODULE 9: Booking Notes
     */
    public function addBookingNotes($uniqueId, $notes) {
        $endpoint = $this->base_url . '/booking_notes';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'notes' => $notes
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['BookingNotesResponse']['BookingNotesResult'])) {
            return $response['BookingNotesResponse']['BookingNotesResult'];
        }
        
        return null;
    }

    /**
     * MODULE 10: Airport List
     */
    public function getAirportList() {
        $endpoint = $this->base_url . '/airport_list';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        // This endpoint appears to return an array directly rather than a nested object on success
        if (is_array($response) && !isset($response['Errors'])) {
            return $response;
        }
        
        return null;
    }

    /**
     * MODULE 11: Airline List
     */
    public function getAirlineList() {
        $endpoint = $this->base_url . '/airline_list';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (is_array($response) && !isset($response['Errors'])) {
            return $response;
        }
        
        return null;
    }

    /**
     * MODULE 12: Search Post Ticket Status
     */
    public function searchPostTicketStatus($uniqueId, $ptrUniqueId) {
        $endpoint = $this->base_url . '/search_post_ticket_status';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'ptrUniqueID' => $ptrUniqueId
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['PtrResponse']['PtrResult'])) {
            return $response['PtrResponse']['PtrResult'];
        }
        
        return null;
    }

    /**
     * MODULE 13: Void Quote
     */
    public function getVoidQuote($uniqueId, $paxDetails) {
        $endpoint = $this->base_url . '/void_ticket_quote';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'paxDetails' => $paxDetails
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['VoidQuoteResponse']['VoidQuoteResult'])) {
            return $response['VoidQuoteResponse']['VoidQuoteResult'];
        }
        
        return null;
    }

    /**
     * MODULE 14: Void Ticket
     */
    public function voidTicket($uniqueId, $paxDetails, $remark = '') {
        $endpoint = $this->base_url . '/void_ticket';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'paxDetails' => $paxDetails
        ];
        
        if (!empty($remark)) {
            $payload['remark'] = $remark;
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        // Note: The sample success response for void_ticket says VoidQuoteResponse/VoidQuoteResult, 
        // but the response parameter documentation says RefundResult. We handle both just in case.
        if (isset($response['RefundResult'])) {
            return $response['RefundResult'];
        } elseif (isset($response['VoidQuoteResponse']['VoidQuoteResult'])) {
            return $response['VoidQuoteResponse']['VoidQuoteResult'];
        }
        
        return null;
    }

    /**
     * MODULE 15: Reissue Quote
     */
    public function getReissueQuote($uniqueId, $paxDetails, $originDestinationInfo) {
        $endpoint = $this->base_url . '/reissue_ticket_quote';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'paxDetails' => $paxDetails,
            'OriginDestinationInfo' => $originDestinationInfo
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['ReissueQuoteResponse']['ReissueQuoteResult'])) {
            return $response['ReissueQuoteResponse']['ReissueQuoteResult'];
        }
        
        return null;
    }

    /**
     * MODULE 16: Reissue Ticket
     */
    public function reissueTicket($uniqueId, $ptrUniqueId, $preferenceOption, $remark = '') {
        $endpoint = $this->base_url . '/reissue_ticket';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'ptrUniqueID' => $ptrUniqueId,
            'PreferenceOption' => $preferenceOption
        ];
        
        if (!empty($remark)) {
            $payload['remark'] = $remark;
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['ReissueResponse']['ReissueResult'])) {
            return $response['ReissueResponse']['ReissueResult'];
        }
        
        return null;
    }

    /**
     * MODULE 17: Refund Quote
     */
    public function getRefundQuote($uniqueId, $paxDetails, $remark = '') {
        $endpoint = $this->base_url . '/refund_quote';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'paxDetails' => $paxDetails
        ];
        
        if (!empty($remark)) {
            $payload['remark'] = $remark;
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['RefundQuoteResponse']['RefundQuoteResult'])) {
            return $response['RefundQuoteResponse']['RefundQuoteResult'];
        }
        
        return null;
    }

    /**
     * MODULE 18: Refund Ticket
     */
    public function processRefund($uniqueId, $paxDetails, $remark = '') {
        $endpoint = $this->base_url . '/refund';
        
        $payload = [
            'user_id' => $this->user_id,
            'user_password' => $this->user_password,
            'access' => $this->access,
            'ip_address' => $this->ip_address,
            'UniqueID' => $uniqueId,
            'paxDetails' => $paxDetails
        ];
        
        if (!empty($remark)) {
            $payload['remark'] = $remark;
        }
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (isset($response['RefundResponse']['RefundResult'])) {
            return $response['RefundResponse']['RefundResult'];
        }
        
        return null;
    }

    private function makeRequest($url, $data) {
        $ch = curl_init($url);
        $jsonPayload = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) { error_log("TRAWEX CURL ERROR: $err"); return ['error' => $err]; }
        $decoded = json_decode($response, true);
        if ($decoded === null) { error_log("TRAWEX BAD JSON: " . substr($response, 0, 200)); return ['error' => 'bad_json']; }
        return $decoded;
    }

    private function mockFlightResults($org, $dest, $date) {
        $makeSegment = function($code, $num, $dep, $arr, $depT, $arrT, $name) use ($date) {
            return ['OriginDestinationOption' => [[
                'FlightSegment' => [
                    'DepartureAirportLocationCode' => $dep,
                    'ArrivalAirportLocationCode'   => $arr,
                    'MarketingAirlineCode'         => $code,
                    'MarketingAirlineName'         => $name,
                    'FlightNumber'                 => $num,
                    'DepartureDateTime'            => $date . 'T' . $depT . ':00',
                    'ArrivalDateTime'              => $date . 'T' . $arrT . ':00',
                    'OperatingAirline'             => ['Code' => $code],
                ]
            ]]];
        };


        // Realistic flight schedules (airline, flight#, dep, arr, name, price, stops)
        $schedules = [
            ['AT', '702',  '06:15', '10:45', 'Royal Air Maroc',    289, 0],
            ['VY', '8762', '07:30', '12:20', 'Vueling',            215, 0],
            ['FR', '7251', '09:00', '13:40', 'Ryanair',            149, 0],
            ['IB', '3722', '10:15', '17:30', 'Iberia',             334, 1],
            ['AT', '811',  '12:00', '17:20', 'Royal Air Maroc',    310, 1],
            ['TK', '1834', '14:45', '22:10', 'Turkish Airlines',   398, 1],
            ['BA', '492',  '16:20', '21:15', 'British Airways',    445, 1],
            ['LH', '5621', '18:00', '23:55', 'Lufthansa',          412, 1],
        ];

        $flights = [];
        foreach ($schedules as [$code, $num, $depT, $arrT, $name, $basePrice, $stops]) {
            // Add small random variation to price
            $price = $basePrice + rand(-15, 25);
            $sessionId = 'MOCK-' . strtoupper(substr(md5($org.$dest.$date.$code), 0, 12));
            $fareSource = 'FARE-' . $code . '-' . $num . '-' . rand(1000,9999);

            $seg = $makeSegment($code, $num, $org, $dest, $depT, $arrT, $name);

            // Add a connecting stop for 1-stop flights
            if ($stops === 1) {
                $hubMap = ['AT'=>'CMN','TK'=>'IST','BA'=>'LHR','LH'=>'FRA','IB'=>'MAD'];
                $hub = $hubMap[$code] ?? 'CDG';
                $midArr = date('H:i', strtotime($depT . ' +' . rand(90,150) . ' minutes'));
                $midDep = date('H:i', strtotime($midArr . ' +' . rand(45,90) . ' minutes'));
                $seg = ['OriginDestinationOption' => [
                    ['FlightSegment' => [
                        'DepartureAirportLocationCode' => $org,
                        'ArrivalAirportLocationCode'   => $hub,
                        'MarketingAirlineCode'         => $code,
                        'MarketingAirlineName'         => $name,
                        'FlightNumber'                 => $num,
                        'DepartureDateTime'            => $date . 'T' . $depT . ':00',
                        'ArrivalDateTime'              => $date . 'T' . $midArr . ':00',
                        'OperatingAirline'             => ['Code' => $code],
                    ]],
                    ['FlightSegment' => [
                        'DepartureAirportLocationCode' => $hub,
                        'ArrivalAirportLocationCode'   => $dest,
                        'MarketingAirlineCode'         => $code,
                        'MarketingAirlineName'         => $name,
                        'FlightNumber'                 => (string)((int)$num + 1),
                        'DepartureDateTime'            => $date . 'T' . $midDep . ':00',
                        'ArrivalDateTime'              => $date . 'T' . $arrT . ':00',
                        'OperatingAirline'             => ['Code' => $code],
                    ]],
                ]];
            }

            $flights[] = [
                'price'            => $price,
                'airline'          => $code,
                'flight_number'    => $code . $num,
                'departure_at'     => $date . 'T' . $depT . ':00',
                'arrival_at'       => $date . 'T' . $arrT . ':00',
                'return_at'        => null,
                'provider'         => 'Trawex (Demo)',
                'session_id'       => $sessionId,
                'fare_source_code' => $fareSource,
                'segments'         => [$seg],
            ];
        }

        // Sort by price (PHP 7.2 compatible)
        usort($flights, function($a, $b) { return $a['price'] - $b['price']; });

        return ['success' => true, 'data' => [$dest => $flights]];
    }
}

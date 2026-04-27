<?php

class AliExpressAPI {
    private $appKey;
    private $appSecret;
    private $accessToken;
    private $endpoint = "https://api-sg.aliexpress.com/sync";

    public function __construct($appKey, $appSecret, $accessToken = null) {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }

    public function setAccessToken($token) {
        $this->accessToken = $token;
    }

    private function executeRequest($method, $apiParams = []) {
        $sysParams = [
            "method" => $method,
            "app_key" => $this->appKey,
            "timestamp" => date("Y-m-d H:i:s"),
            "format" => "json",
            "v" => "2.0",
            "sign_method" => "md5"
        ];
        
        if ($this->accessToken) {
            $sysParams['session'] = $this->accessToken;
        }

        $allParams = array_merge($sysParams, $apiParams);
        ksort($allParams);

        $stringToBeSigned = $this->appSecret;
        foreach ($allParams as $k => $v) {
            if (is_string($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        $stringToBeSigned .= $this->appSecret;
        $allParams["sign"] = strtoupper(md5($stringToBeSigned));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($allParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // ── Performance: timeouts, HTTP/2, compression, keepalive ──
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);   // fail fast if unreachable
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);          // max 15s total
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0); // HTTP/2
        curl_setopt($ch, CURLOPT_ENCODING, '');         // accept gzip/deflate
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($ch, CURLOPT_TCP_FASTOPEN, true);
        $res = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return ["error" => curl_error($ch)];
        }
        
        curl_close($ch);
        
        return json_decode($res, true) ?? ["error" => "Invalid JSON response", "raw" => substr($res, 0, 500)];
    }

    public function getFeedNames() {
        return $this->executeRequest("aliexpress.ds.feedname.get");
    }

    public function getFeedItemIds($feedName, $country = "MA", $pageNo = 1, $pageSize = 20) {
        return $this->executeRequest("aliexpress.ds.feed.itemids.get", [
            "feed_name" => $feedName,
            "ship_to_country" => $country,
            "page_no" => (string)$pageNo,
            "page_size" => (string)$pageSize
        ]);
    }

    public function getProductDetails($productId, $country = "MA", $currency = "MAD", $language = "EN") {
        return $this->executeRequest("aliexpress.ds.product.get", [
            "product_id" => (string)$productId,
            "ship_to_country" => $country,
            "target_currency" => $currency,
            "target_language" => $language
        ]);
    }

    public function getProductReviews($productId, $pageNo = 1, $pageSize = 10) {
        return $this->executeRequest("aliexpress.ds.evaluation.get", [
            "product_id" => (string)$productId,
            "page_no"    => (string)$pageNo,
            "page_size"  => (string)$pageSize,
        ]);
    }
}

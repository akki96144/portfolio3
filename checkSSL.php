<?php

include_once '/var/www/Library/AutoLoad.php';
include_once '/var/www/batch.e-wallet.jp/data/define.php';

/**
 * SSL証明書の有効期限を取得する
 * @param string $host ドメイン名
 * @return array
 */
function getSSLExpiryDate($host) {
    // SSL接続の設定を行う
    $context = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,    // 証明書を取得する
            "verify_peer" => false,        // 証明書の検証を行わない
            "verify_peer_name" => false    // CNの検証を行わない
        ]
    ]);

    // サーバーに接続
    $client = stream_socket_client("ssl://$host:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    // 接続失敗の場合、エラーメッセージを返す
    if (!$client) {
        return [
            "type" => "fail",
            "message" => "$host との接続に失敗しました。 (エラー番号: $errno) (エラーメッセージ: $errstr)"
        ];
    }

    // SSL証明書の情報を取得
    $params = stream_context_get_params($client);
    $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);

    // SSL証明書の有効期限を日本時間に変換
    $expiryDate = new DateTime("@{$cert["validTo_time_t"]}");
    $expiryDate->setTimezone(new DateTimeZone("Asia/Tokyo"));

    // SSL証明書の有効期限切れの場合
    if ($expiryDate < new DateTime()) {
        return [
            "type" => "expired",
            "expiryDate" => $expiryDate,
            "message" => "$host のSSL証明書は有効期限切れです: " . $expiryDate->format("Y-m-d H:i:s")
        ];
    }

    // SSL証明書の有効期限を返す
    return [
        "type" => "valid",
        "expiryDate" => $expiryDate,
        "message" => "$host のSSL有効期限: " . $expiryDate->format("Y-m-d H:i:s")
    ];
}

// 読み込むドメインリストのファイルパス
$domainFilePath = "/Users/maedaakihiro/prac/ポートフォリオ3/domains.txt";
$outputFile = "/Users/maedaakihiro/prac/ポートフォリオ3/sslResult.txt";

// ファイルが存在しない場合は処理を中止
if (!file_exists($domainFilePath)) {
    die("エラー: ドメインリストのファイルが見つかりません: $domainFilePath\n");
}

// 出力ファイルが存在しない場合は新規作成
if (!file_exists($outputFile)) {
    file_put_contents($outputFile, ""); // 空のファイルを作成
}

$date = date("Y-m-d H:i:s");
$header = "SSL証明書有効期限チェックの結果です。(更新日: $date)";

// 結果を分類するための配列
$failedConnections = [];
$expiredCertificates = [];
$validCertificates = [];

// ドメインリストを読み込む
$domains = file($domainFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($domains as $domain) {
    $domain = trim($domain);
    $sslInfo = getSSLExpiryDate($domain);
    $message = $sslInfo["message"];

    // 結果を分類
    if ($sslInfo["type"] === "fail") {
        $failedConnections[] = $message;
    } elseif ($sslInfo["type"] === "expired") {
        $expiredCertificates[] = $message;
    } elseif ($sslInfo["type"] === "valid") {
        $validCertificates[] = ['message' => $message, 'expiry' => $sslInfo["expiryDate"]->getTimestamp()];
    }
}

// 有効な証明書を有効期限が近い順にソート
usort($validCertificates, function ($a, $b) {
    return $a['expiry'] - $b['expiry'];
});

// ソート後の結果を統合
$sortedResults = array_merge(
    [$header],
    $failedConnections,
    $expiredCertificates,
    array_column($validCertificates, 'message')
);

// 並び替えた結果をファイルに書き込み
file_put_contents($outputFile, implode("\n", $sortedResults) . "\n\n", FILE_APPEND);


echo "SSL証明書のチェックが完了しました。結果は $outputFile に保存されました。\n";
